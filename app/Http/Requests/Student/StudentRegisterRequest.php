<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate student registration data.
 */
class StudentRegisterRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'regex:/^(09\d{8}|\+9639\d{8}|9639\d{8})$/', 'unique:students,phone'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'study_level' => ['required', 'string', Rule::in(['primary', 'middle', 'secondary', 'university'])],
            'about' => ['nullable', 'string', 'max:1500'],
        ];
    }
}
