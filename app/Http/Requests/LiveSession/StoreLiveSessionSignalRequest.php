<?php

namespace App\Http\Requests\LiveSession;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate persisted polling-based WebRTC signals.
 */
class StoreLiveSessionSignalRequest extends FormRequest
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
            'signal_type' => ['required', 'string', 'in:offer,answer,candidate,bye'],
            'payload' => ['required', 'array'],
        ];
    }
}
