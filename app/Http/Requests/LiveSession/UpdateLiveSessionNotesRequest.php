<?php

namespace App\Http\Requests\LiveSession;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate teacher notes saved during the session.
 */
class UpdateLiveSessionNotesRequest extends FormRequest
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
            'teacher_private_notes' => ['nullable', 'string', 'max:6000'],
            'student_summary_notes' => ['nullable', 'string', 'max:6000'],
        ];
    }
}
