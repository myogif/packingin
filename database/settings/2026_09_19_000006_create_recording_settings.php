<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('recording.storage_path', 'recordings');
        $this->migrator->add('recording.default_format', 'mp4');
        $this->migrator->add('recording.naming_pattern', '{date}_{time}');
        $this->migrator->add('recording.auto_convert_to_mp4', true);
        $this->migrator->add('recording.max_recording_duration_seconds', 1800);
    }
};
