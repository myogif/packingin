<?php

namespace App\Jobs;

use App\Models\Recording;
use App\Services\VideoConversionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessRecordingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const MAX_ATTEMPTS = 3;

    public function __construct(
        public Recording $recording,
    ) {
        $this->tries = self::MAX_ATTEMPTS;
    }

    public function handle(VideoConversionService $videoConversionService): void
    {
        try {
            $this->recording->update(['status' => 'processing']);

            $date = $this->recording->recorded_at?->format('Y-m-d') ?? now()->format('Y-m-d');

            if (!$videoConversionService->convert($this->recording, $date)) {
                throw new \Exception('Video conversion failed');
            }
        } catch (\Exception $e) {
            Log::error('ProcessRecordingJob failed', [
                'recording_id' => $this->recording->id,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            if ($this->attempts() >= self::MAX_ATTEMPTS) {
                $this->recording->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'processed_at' => now(),
                ]);

                return;
            }

            throw $e;
        }
    }
}
