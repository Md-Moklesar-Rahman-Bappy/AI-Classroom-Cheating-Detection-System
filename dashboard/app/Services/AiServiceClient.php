<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiServiceException extends \RuntimeException
{
    public function __construct(string $message, public int $statusCode = 500, public ?array $details = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
    }
}

class AiServiceClient
{
    private string $baseUrl;

    private string $token;

    private int $timeout;

    private int $connectTimeout;

    private int $retryAttempts;

    private int $retryDelayMs;

    public function __construct(?string $baseUrl = null, ?string $token = null, ?int $timeout = null)
    {
        $this->baseUrl = rtrim($baseUrl ?? config('ai.ai_service.base_url'), '/');
        $this->token = $token ?? config('ai.ai_service.token');
        $this->timeout = $timeout ?? config('ai.ai_service.timeout');
        $this->connectTimeout = config('ai.ai_service.connect_timeout');
        $this->retryAttempts = config('ai.ai_service.retry_attempts');
        $this->retryDelayMs = config('ai.ai_service.retry_delay_ms');
    }

    private function client(string $correlationId): PendingRequest
    {
        $client = Http::baseUrl($this->baseUrl)
            ->timeout($this->timeout)
            ->connectTimeout($this->connectTimeout)
            ->withHeaders([
                'X-Correlation-Id' => $correlationId,
                'Accept' => 'application/json',
            ])
            ->retry($this->retryAttempts, $this->retryDelayMs, function ($exception, $request) {
                // only retry safe operations (GET, health) and not POST with file
                $method = $request->method();

                return in_array($method, ['GET']) && $exception instanceof ConnectionException;
            });
        if ($this->token && $this->token !== 'dev-token-change-me') {
            $client = $client->withToken($this->token);
        }

        return $client;
    }

    private function redact(string $message): string
    {
        return preg_replace("/(token|password|secret|key)=[^&\s]+/i", '$1=[REDACTED]', $message) ?? $message;
    }

    public function healthCheck(?string $correlationId = null): array
    {
        $correlationId = $correlationId ?? (string) Str::uuid();
        try {
            $response = $this->client($correlationId)->get('/api/v1/health');
            if ($response->failed()) {
                throw new AiServiceException($this->redact('Health check failed: '.$response->body()), $response->status());
            }

            return $response->json();
        } catch (AiServiceException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('AI health check failed', ['correlation_id' => $correlationId, 'error' => $this->redact($e->getMessage())]);
            throw new AiServiceException('AI service unavailable', 503, null, $e);
        }
    }

    public function createRecordedJob(string $filePath, string $originalFilename, ?string $correlationId = null, ?string $mimeType = null, ?int $fileSize = null, ?string $checksum = null, ?string $modelVersion = null, ?array $config = null, ?int $dashboardJobId = null): array
    {
        $correlationId = $correlationId ?? (string) Str::uuid();
        $stream = null;
        try {
            if (! file_exists($filePath)) {
                throw new AiServiceException('Local video file not found', 404);
            }
            $mimeType = $mimeType ?? mime_content_type($filePath) ?: 'video/mp4';
            $fileSize = $fileSize ?? filesize($filePath);
            if ($fileSize === 0) {
                throw new AiServiceException('Empty file', 422);
            }
            if ($checksum === null) {
                $checksum = hash_file('sha256', $filePath);
            }
            $stream = fopen($filePath, 'r');
            if ($stream === false) {
                throw new AiServiceException('Failed to open video file', 500);
            }
            $response = $this->client($correlationId)
                ->timeout(300)
                ->attach('file', $stream, $originalFilename, ['Content-Type' => $mimeType])
                ->post('/api/v1/jobs/recorded', [
                    'original_filename' => $originalFilename,
                    'mime_type' => $mimeType,
                    'file_size' => (string) $fileSize,
                    'file_checksum' => $checksum,
                    'model_version' => $modelVersion ?? 'yolo11n.pt',
                    'config' => $config ? json_encode($config) : null,
                    'correlation_id' => $correlationId,
                    'dashboard_job_id' => $dashboardJobId ? (string) $dashboardJobId : null,
                ]);
            if (is_resource($stream)) {
                fclose($stream);
                $stream = null;
            }
            if ($response->failed()) {
                $body = $this->redact($response->body());
                // Also redact paths
                $body = preg_replace('/[A-Z]:\\\\[^\\s"]+/', '[REDACTED_PATH]', $body) ?? $body;
                $body = preg_replace('/\/[^\\s"]+\/(storage|video_assets)[^\s"]*/', '[REDACTED_PATH]', $body) ?? $body;
                Log::warning('AI create job failed', ['correlation_id' => $correlationId, 'status' => $response->status(), 'body' => $body]);
                if ($response->status() === 401) {
                    throw new AiServiceException('AI authentication failed', 401);
                }
                if ($response->status() === 422) {
                    throw new AiServiceException('Invalid video: '.$body, 422);
                }
                if ($response->status() === 413) {
                    throw new AiServiceException('File too large: '.$body, 413);
                }
                throw new AiServiceException('AI job creation failed: '.$body, $response->status());
            }
            $data = $response->json();
            // Do not log absolute paths
            Log::info('AI job created', ['correlation_id' => $correlationId, 'job_id' => $data['job_id'] ?? $data['remote_job_id'] ?? null]);

            return $data;
        } catch (AiServiceException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $msg = $this->redact($e->getMessage());
            $msg = preg_replace('/[A-Z]:\\\\[^\\s"]+/', '[REDACTED_PATH]', $msg) ?? $msg;
            Log::error('AI create job exception', ['correlation_id' => $correlationId, 'error' => $msg]);
            throw new AiServiceException('AI service unavailable during upload', 503, null, $e);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    public function getJob(string $jobId, ?string $correlationId = null): array
    {
        $correlationId = $correlationId ?? (string) Str::uuid();
        try {
            $response = $this->client($correlationId)->get("/api/v1/jobs/{$jobId}");
            if ($response->failed()) {
                if ($response->status() === 404) {
                    throw new AiServiceException('Job not found', 404);
                }
                throw new AiServiceException($this->redact($response->body()), $response->status());
            }

            return $response->json();
        } catch (AiServiceException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new AiServiceException('AI service unavailable', 503, null, $e);
        }
    }

    public function getEvents(string $jobId, ?string $correlationId = null): array
    {
        $correlationId = $correlationId ?? (string) Str::uuid();
        $response = $this->client($correlationId)->get("/api/v1/jobs/{$jobId}/events");
        if ($response->failed()) {
            throw new AiServiceException($this->redact($response->body()), $response->status());
        }

        return $response->json();
    }

    public function getMetrics(string $jobId, ?string $correlationId = null): array
    {
        $correlationId = $correlationId ?? (string) Str::uuid();
        $response = $this->client($correlationId)->get("/api/v1/jobs/{$jobId}/metrics");
        if ($response->failed()) {
            throw new AiServiceException($this->redact($response->body()), $response->status());
        }

        return $response->json();
    }

    public function cancelJob(string $jobId, ?string $correlationId = null): array
    {
        $correlationId = $correlationId ?? (string) Str::uuid();
        $response = $this->client($correlationId)->post("/api/v1/jobs/{$jobId}/cancel");
        if ($response->failed()) {
            throw new AiServiceException($this->redact($response->body()), $response->status());
        }

        return $response->json();
    }

    public function retryJob(string $jobId, ?string $correlationId = null): array
    {
        $correlationId = $correlationId ?? (string) Str::uuid();
        $response = $this->client($correlationId)->post("/api/v1/jobs/{$jobId}/retry");
        if ($response->failed()) {
            throw new AiServiceException($this->redact($response->body()), $response->status());
        }

        return $response->json();
    }

    public function createLiveSession(array $payload, ?string $correlationId = null): array
    {
        $correlationId = $correlationId ?? (string) Str::uuid();
        $response = $this->client($correlationId)->post('/api/v1/live/start', $payload);
        if ($response->failed()) {
            $body = $this->redact($response->body());
            if ($response->status() === 401) {
                throw new AiServiceException('AI authentication failed', 401);
            }
            if ($response->status() === 409) {
                throw new AiServiceException('Already monitoring: '.$body, 409);
            }
            if ($response->status() === 422) {
                throw new AiServiceException('Invalid live source: '.$body, 422);
            }
            throw new AiServiceException('Live start failed: '.$body, $response->status());
        }

        return $response->json();
    }

    public function stopLiveSession(string $sessionId, ?string $correlationId = null): array
    {
        $correlationId = $correlationId ?? (string) Str::uuid();
        $response = $this->client($correlationId)->post("/api/v1/live/{$sessionId}/stop");
        if ($response->failed() && $response->status() !== 404) {
            throw new AiServiceException($this->redact($response->body()), $response->status());
        }

        return $response->json() ?? ['session_id' => $sessionId, 'status' => 'stopped'];
    }

    public function getLiveHealth(string $sessionId, ?string $correlationId = null): array
    {
        $correlationId = $correlationId ?? (string) Str::uuid();
        $response = $this->client($correlationId)->get("/api/v1/live/{$sessionId}/health");
        if ($response->failed()) {
            throw new AiServiceException($this->redact($response->body()), $response->status());
        }

        return $response->json();
    }

    public function getLiveEvents(string $sessionId, ?string $correlationId = null): array
    {
        $correlationId = $correlationId ?? (string) Str::uuid();
        $response = $this->client($correlationId)->get("/api/v1/live/{$sessionId}/events");
        if ($response->failed()) {
            throw new AiServiceException($this->redact($response->body()), $response->status());
        }

        return $response->json();
    }

    public function proxyLivePreview(string $sessionId, ?string $correlationId = null)
    {
        $correlationId = $correlationId ?? (string) Str::uuid();
        $response = $this->client($correlationId)->withOptions(['stream' => true])->get("/api/v1/live/{$sessionId}/preview");
        if ($response->failed()) {
            throw new AiServiceException($this->redact($response->body()), $response->status());
        }

        return response($response->body(), 200)->header('Content-Type', $response->header('Content-Type') ?? 'multipart/x-mixed-replace; boundary=frame');
    }
}
