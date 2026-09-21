<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordingUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'video' => ['required', 'file', 'max:2097152'], // max 2GB
            'resi' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9\-]+$/'],
            'platform' => ['required', 'string', 'max:255'],
            'duration' => ['nullable', 'integer', 'min:0'],
            'mime_type' => ['nullable', 'string', 'max:100'],
        ];
    }
}
