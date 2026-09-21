<?php

namespace App\Services;

use App\Models\Recording;
use Illuminate\Http\UploadedFile;

class RecordingStorageService
{
    public function store(UploadedFile $file, array $metadata): Recording
    {
        // Add additional metadata values
        $metadata['filename'] = $file->getClientOriginalName();
        $metadata['file_size_bytes'] = $file->getSize();
        $metadata['mime_type'] = $file->getMimeType();

        // Ensure title is set
        if (empty($metadata['title'])) {
            $metadata['title'] = 'Recording ' . now()->format('Y-m-d H:i:s');
        }

        // Create recording model
        $recording = Recording::create($metadata);

        // Add media to recordings disk
        $recording->addMedia($file)
            ->toMediaCollection('recordings', 'recordings');

        return $recording;
    }
}
