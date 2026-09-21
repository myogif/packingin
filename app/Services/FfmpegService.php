<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class FfmpegService
{
    protected string $ffmpegPath;
    protected string $ffprobePath;

    public function __construct()
    {
        $this->ffmpegPath = config('ffmpeg.binary', '/usr/bin/ffmpeg');
        $this->ffprobePath = config('ffmpeg.ffprobe_binary', '/usr/bin/ffprobe');
    }

    public function convert(string $inputPath, string $outputPath): bool
    {
        if (!file_exists($this->ffmpegPath)) {
            Log::warning("FFmpeg binary not found at {$this->ffmpegPath}. Conversion skipped.");
            return false;
        }

        $command = sprintf(
            '%s -y -i %s -c:v libx264 -c:a aac -movflags +faststart %s 2>&1',
            escapeshellcmd($this->ffmpegPath),
            escapeshellarg($inputPath),
            escapeshellarg($outputPath)
        );

        exec($command, $output, $resultCode);

        if ($resultCode !== 0) {
            Log::error('FFmpeg conversion failed: ' . implode("\n", $output));
            return false;
        }

        return true;
    }

    public function getDuration(string $filePath): ?int
    {
        if (!file_exists($this->ffprobePath)) {
            return null;
        }

        $command = sprintf(
            '%s -v quiet -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s 2>&1',
            escapeshellcmd($this->ffprobePath),
            escapeshellarg($filePath)
        );

        exec($command, $output, $resultCode);

        if ($resultCode !== 0) {
            Log::error('FFprobe duration extraction failed: ' . implode("\n", $output));
            return null;
        }

        return isset($output[0]) ? (int) round((float) $output[0]) : null;
    }
}
