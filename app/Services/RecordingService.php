<?php

namespace App\Services;

use App\Models\Recording;

class RecordingService
{
    public function createSession(string $resi, string $platform): Recording
    {
        return Recording::create([
            'resi' => $resi,
            'platform' => $platform,
            'status' => 'queued',
            'recorded_at' => now(),
        ]);
    }
}

