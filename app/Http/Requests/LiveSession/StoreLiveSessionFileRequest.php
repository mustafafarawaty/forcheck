<?php

namespace App\Http\Requests\LiveSession;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate files uploaded during a live session.
 */
class StoreLiveSessionFileRequest extends FormRequest
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
            'file' => ['required', 'file', 'max:20480'],
        ];
    }
}
