<?php

namespace App\Services;

use App\Models\Recording;
use Illuminate\Support\Facades\Log;

class VideoConversionService
{
    public function __construct(
        private VideoFilenameService $filenameService,
        private FfmpegService $ffmpegService,
    ) {}

    public function convert(Recording $recording, string $date): bool
    {
        try {
            $sourceAbsolute = storage_path('app/' . $recording->source_path);

            if (!$recording->source_path || !file_exists($sourceAbsolute)) {
                throw new \Exception('Source file not found at: ' . $sourceAbsolute);
            }

            // Path inside storage/app/packing-videos...
            $relativeBasePath = 'packing-videos';
            $absoluteBasePath = storage_path('app/' . $relativeBasePath);

            $outputAbsolutePath = $this->filenameService->generatePath($recording->resi, $date, $absoluteBasePath);

            // Get relative path for database
            $outputRelativePath = str_replace(storage_path('app/') . '', '', $outputAbsolutePath);

            $outputDir = dirname($outputAbsolutePath);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            if (!$this->ffmpegService->convert($sourceAbsolute, $outputAbsolutePath)) {
                throw new \Exception('FFmpeg conversion failed');
            }

            if (!file_exists($outputAbsolutePath)) {
                throw new \Exception('Output file was not created');
            }

            $duration = $this->ffmpegService->getDuration($outputAbsolutePath);
            $fileSize = filesize($outputAbsolutePath);
            $mimeType = mime_content_type($outputAbsolutePath) ?: 'video/mp4';

            $recording->update([
                'final_path' => ltrim($outputRelativePath, '/'),
                'duration' => $duration,
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
                'status' => 'completed',
                'processed_at' => now(),
            ]);

            $this->cleanupSourceFile($sourceAbsolute);

            return true;
        } catch (\Exception $e) {
            Log::error('Video conversion failed', [
                'recording_id' => $recording->id,
                'error' => $e->getMessage(),
            ]);

            // Handled by Job failed() method, but we can also set here
            throw $e;
        }
    }

    private function cleanupSourceFile(string $path): void
    {
        if (file_exists($path)) {
            @unlink($path);
        }
    }
}
