<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class RecordPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-video-camera';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.record-page';

    protected static ?string $navigationLabel = 'Record';

    protected static ?string $title = 'Record Video';

    protected static ?int $navigationSort = 1;

    public function getTitle(): string | Htmlable
    {
        return static::$title;
    }
}
