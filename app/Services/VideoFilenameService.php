<?php

namespace App\Services;

use Illuminate\Support\Str;

class VideoFilenameService
{
    public function generatePath(string $resi, string $date, ?string $basePath = null): string
    {
        $sanitized = $this->sanitize($resi);

        $basePath = $basePath ?? storage_path('app/packing-videos');

        try {
            $dateObj = new \DateTime($date);
            $year = $dateObj->format('Y');
            $month = $dateObj->format('m');
            $day = $dateObj->format('d');
        } catch (\Exception $e) {
            $year = now()->format('Y');
            $month = now()->format('m');
            $day = now()->format('d');
        }

        $dir = "{$basePath}/{$year}/{$month}/{$day}";

        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $extension = '.mp4';
        $filename = $sanitized . $extension;
        $counter = 2;

        while (file_exists($dir . '/' . $filename)) {
            $filename = $sanitized . '_' . $counter . $extension;
            $counter++;
        }

        return "{$dir}/{$filename}";
    }

    private function sanitize(string $input): string
    {
        $sanitized = preg_replace('/[^a-zA-Z0-9\-]/', '', $input);
        return !empty($sanitized) ? $sanitized : 'unknown_' . Str::random(8);
    }
}
