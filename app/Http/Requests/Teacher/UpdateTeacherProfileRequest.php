<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate teacher profile updates.
 */
class UpdateTeacherProfileRequest extends FormRequest
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
        $teacherId = session('teacher_id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required',
                'regex:/^(09\d{8}|\+9639\d{8}|9639\d{8})$/',
                Rule::unique('teachers', 'phone')->ignore($teacherId),
            ],
            'education_stage' => ['required', 'string', Rule::in(['secondary', 'university'])],
            'about' => ['nullable', 'string', 'max:1500'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
