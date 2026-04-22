<?php

namespace App\Http\Requests\LiveSession;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate explicit session ending actions.
 */
class EndLiveSessionRequest extends FormRequest
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
            'confirm_end' => ['nullable', 'boolean'],
        ];
    }
}
