<?php

namespace App\Http\Requests\LiveSession;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate uploaded recorded media from a live session.
 */
class StoreLiveSessionRecordingRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'recording' => ['required_without:recording_finalize', 'file', 'max:1048576'],
            'recording_chunk_index' => ['nullable', 'integer', 'min:0'],
            'recording_is_last_chunk' => ['nullable', 'boolean'],
            'recording_finalize' => ['nullable', 'boolean'],
        ];
    }
}
