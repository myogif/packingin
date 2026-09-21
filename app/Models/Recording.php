<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recording extends Model
{
    use HasFactory;

    protected $fillable = [
        'resi',
        'platform',
        'status',
        'source_path',
        'final_path',
        'duration',
        'file_size',
        'mime_type',
        'recorded_at',
        'processed_at',
        'error_message',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'processed_at' => 'datetime',
    ];
}
