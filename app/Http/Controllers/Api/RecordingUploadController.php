<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecordingUploadRequest;
use App\Services\RecordingService;
use App\Services\VideoStorageService;
use App\Jobs\ProcessRecordingJob;
use Illuminate\Http\JsonResponse;

class RecordingUploadController extends Controller
{
    public function __construct(
        protected RecordingService $recordingService,
        protected VideoStorageService $storageService
    ) {}

    public function __invoke(RecordingUploadRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Store the temp file
        $file = $request->file('video');
        $tempPath = $this->storageService->storeTemporary($file, $validated['resi']);

        // Create recording session in DB
        $recording = $this->recordingService->createSession($validated['resi'], $validated['platform']);

        // Update recording with initial values
        $recording->update([
            'source_path' => $tempPath,
            'duration' => $validated['duration'] ?? null,
            'mime_type' => $validated['mime_type'] ?? null,
        ]);

        // Dispatch Job synchronously
        ProcessRecordingJob::dispatchSync($recording);

        // Get latest status after processing
        $recording->refresh();

        return response()->json([
            'message' => 'Recording uploaded and processed successfully',
            'recording' => [
                'id' => $recording->id,
                'resi' => $recording->resi,
                'status' => $recording->status,
            ]
        ], 201);
    }
}
