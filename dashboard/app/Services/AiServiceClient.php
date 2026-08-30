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

    public function createRecordedJob(string $filePath, string $originalFilename, ?string $correlationId = null): array
    {
        $correlationId = $correlationId ?? (string) Str::uuid();
        try {
            $response = $this->client($correlationId)
                ->timeout(300) // allow long processing but controller should not wait, queued job will handle
                ->attach('file', file_get_contents($filePath), $originalFilename)
                ->post('/api/v1/jobs/recorded');
            if ($response->failed()) {
                $body = $this->redact($response->body());
                Log::warning('AI create job failed', ['correlation_id' => $correlationId, 'status' => $response->status(), 'body' => $body]);
                if ($response->status() === 401) {
                    throw new AiServiceException('AI authentication failed', 401);
                }
                if ($response->status() === 422) {
                    throw new AiServiceException('Invalid video: '.$body, 422);
                }
                throw new AiServiceException('AI job creation failed: '.$body, $response->status());
            }
            $data = $response->json();
            Log::info('AI job created', ['correlation_id' => $correlationId, 'job_id' => $data['job_id'] ?? null]);

            return $data;
        } catch (AiServiceException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('AI create job exception', ['correlation_id' => $correlationId, 'error' => $this->redact($e->getMessage())]);
            throw new AiServiceException('AI service unavailable during upload', 503, null, $e);
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
