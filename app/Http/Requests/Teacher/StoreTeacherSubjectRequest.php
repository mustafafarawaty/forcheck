<?php

namespace App\Http\Requests\Teacher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate adding a teacher subject.
 */
class StoreTeacherSubjectRequest extends FormRequest
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
            'level' => ['required', 'string', Rule::in(['primary', 'middle', 'secondary', 'university'])],
            'hourly_rate_syp' => ['required', 'integer', 'min:0', 'max:99999999'],
        ];
    }
}
