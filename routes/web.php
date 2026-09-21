<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\RecordingStation;
use App\Models\Recording;
use App\Http\Controllers\Api\RecordingUploadController;

Route::middleware(['auth'])->group(function () {
    Route::get('/', RecordingStation::class)->name('recording.station');

    Route::post('/api/recordings/upload', RecordingUploadController::class)->name('recordings.upload');

    Route::get('/recordings/{recording}/play', function (Recording $recording) {
        if ($recording->status !== 'completed' || !$recording->final_path) {
            abort(404);
        }

        $path = storage_path('app/' . $recording->final_path);
        if (!file_exists($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type' => 'video/mp4',
            'Accept-Ranges' => 'bytes',
        ]);
    })->name('recordings.play');
});
