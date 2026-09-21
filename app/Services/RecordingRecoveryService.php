<?php

namespace App\Services;

use App\Jobs\ProcessRecordingJob;
use App\Models\Recording;

class RecordingRecoveryService
{
    public function recover(): int
    {
        $recordings = Recording::query()
            ->whereIn('status', ['queued', 'processing'])
            ->whereNotNull('source_path')
            ->get();

        foreach ($recordings as $recording) {
            ProcessRecordingJob::dispatch($recording);
        }

        return $recordings->count();
    }
}
