<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class VideoStorageService
{
    public function storeTemporary(UploadedFile $file, string $resi): string
    {
        $extension = $file->getClientOriginalExtension();
        if (empty($extension)) {
            $mime = $file->getMimeType();
            if (Str::contains($mime, 'mp4')) {
                $extension = 'mp4';
            } elseif (Str::contains($mime, 'webm')) {
                $extension = 'webm';
            } else {
                $extension = 'tmp';
            }
        }

        $filename = "{$resi}_" . time() . "_{$extension}";

        return $file->storeAs('recording-temp', $filename, 'local');
    }
}
