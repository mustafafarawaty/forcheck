<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate teacher login credentials.
 */
class TeacherLoginRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'regex:/^(09\d{8}|\+9639\d{8}|9639\d{8})$/'],
            'password' => ['required', 'string', 'min:6'],
        ];
    }
}
