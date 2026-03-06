<?php

namespace CceoDeveloper\Catchr\Listeners;

use CceoDeveloper\Catchr\Support\Jobs\JobRunStore;
use CceoDeveloper\Catchr\Support\Jobs\QueueJobMeta;
use CceoDeveloper\Catchr\Support\Jobs\QueueReporter;
use Illuminate\Support\Facades\Config;
use Throwable;

class TrackJobFailed
{
    public function handle(\Illuminate\Queue\Events\JobFailed $event): void
    {
        try {
            if (!Config::get('catchr.queue.report_failed', true)) {
                return;
            }

            $meta = QueueJobMeta::extract($event->job, $event->connectionName);

            $durationMs = (new JobRunStore())->markFailed($meta, $event->exception);

            (new QueueReporter())->report(
                event: 'queue.failed',
                jobMeta: [
                    'connection' => $meta['connection'],
                    'queue' => $meta['queue'],
                    'job_name' => $meta['job_name'],
                    'job_id' => $meta['job_id'],
                    'uuid' => $meta['uuid'],
                    'attempts' => $meta['attempts'],
                    'max_tries' => $meta['max_tries'],
                    'timeout' => $meta['timeout'],
                    'duration_ms' => $durationMs,
                    'status' => 'failed',
                ],
                exception: $event->exception
            );
        } catch (Throwable $ignored) {
            @error_log('[Catchr] Failed to track job failed: ' . get_class($ignored) . ' - ' . $ignored->getMessage());
        }
    }
}