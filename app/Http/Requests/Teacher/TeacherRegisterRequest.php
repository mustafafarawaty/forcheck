<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate teacher registration data and uploaded certificate file.
 */
class TeacherRegisterRequest extends FormRequest
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
            'phone' => ['required', 'regex:/^(09\d{8}|\+9639\d{8}|9639\d{8})$/', 'unique:teachers,phone'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'education_stage' => ['required', 'string', Rule::in(['secondary', 'university'])],
            'certificate' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'about' => ['nullable', 'string', 'max:1500'],
        ];
    }
}
