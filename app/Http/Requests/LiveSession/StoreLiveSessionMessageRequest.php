<?php

namespace App\Http\Requests\LiveSession;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate live session chat messages.
 */
class StoreLiveSessionMessageRequest extends FormRequest
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
            'message' => ['required', 'string', 'min:1', 'max:2000'],
        ];
    }
}
