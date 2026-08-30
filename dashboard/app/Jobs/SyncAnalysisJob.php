<?php

namespace App\Jobs;

use App\Models\AnalysisJob;
use App\Services\AiServiceClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $analysisJobId) {}

    public function handle(AiServiceClient $client): void
    {
        $job = AnalysisJob::find($this->analysisJobId);
        if (! $job || ! $job->remote_job_id) {
            return;
        }
        try {
            $remote = $client->getJob($job->remote_job_id, $job->correlation_id ?? '');
            $job->update(['remote_status' => $remote['status'] ?? null, 'remote_progress' => $remote['progress_percent'] ?? null, 'progress_percent' => $remote['progress_percent'] ?? $job->progress_percent]);
            if ($remote['status'] === 'completed' && $job->status !== 'completed') {
                // Trigger full sync via Process job
                ProcessAnalysisJob::dispatch($job->id, $job->correlation_id);
            }
        } catch (\Throwable $e) {
        }
    }
}
