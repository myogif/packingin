<?php

return [
    'settings' => [
        \App\Settings\RecordingSettings::class,
    ],
    'migrations_paths' => [
        database_path('settings'),
    ],
    'cache' => [
        'store' => null,
        'prefix' => null,
    ],
    'repository' => \Spatie\LaravelSettings\SettingsRepositories\DatabaseSettingsRepository::class,
    'global_cache_prefix' => null,
];
