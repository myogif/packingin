<?php

namespace App\Filament\Pages;

use App\Settings\RecordingSettings;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;

class RecordingSettingsPage extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $settings = RecordingSettings::class;

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $title = 'Recording Settings';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('storage_path')
                    ->label('Storage Path')
                    ->disabled()
                    ->helperText('Configured in filesystems.php'),
                Select::make('default_format')
                    ->label('Default Format')
                    ->options([
                        'mp4' => 'MP4',
                        'webm' => 'WebM',
                    ]),
                TextInput::make('naming_pattern')
                    ->label('Naming Pattern')
                    ->helperText('e.g. {date}_{time}'),
                Toggle::make('auto_convert_to_mp4')
                    ->label('Auto-convert WebM to MP4')
                    ->helperText('Requires FFmpeg to be installed.'),
                TextInput::make('max_recording_duration_seconds')
                    ->label('Max Recording Duration (seconds)')
                    ->numeric(),
            ]);
    }
}
