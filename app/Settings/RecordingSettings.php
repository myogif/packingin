<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class RecordingSettings extends Settings
{
    public string $storage_path;
    public string $default_format;
    public string $naming_pattern;
    public bool $auto_convert_to_mp4;
    public int $max_recording_duration_seconds;

    public static function group(): string
    {
        return 'recording';
    }
}
