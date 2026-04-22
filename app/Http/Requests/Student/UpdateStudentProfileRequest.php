<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate student profile updates.
 */
class UpdateStudentProfileRequest extends FormRequest
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
        $studentId = session('student_id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required',
                'regex:/^(09\d{8}|\+9639\d{8}|9639\d{8})$/',
                Rule::unique('students', 'phone')->ignore($studentId),
            ],
            'study_level' => ['required', 'string', Rule::in(['primary', 'middle', 'secondary', 'university'])],
            'about' => ['nullable', 'string', 'max:1500'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }
}
