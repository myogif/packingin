<?php

namespace App\Filament\Resources\RecordingResource\Pages;

use App\Filament\Resources\RecordingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRecording extends ViewRecord
{
    protected static string $resource = RecordingResource::class;

    protected static string $view = 'filament.resources.recording-resource.pages.view-recording';
}