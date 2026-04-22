<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate student booking form.
 */
class StoreStudentBookingRequest extends FormRequest
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
            'booking_mode' => ['required', Rule::in(['instant', 'scheduled'])],
            'subject_name' => ['required', 'string', 'max:255'],
            'teacher_id' => ['nullable', 'integer', Rule::exists('teachers', 'id')],
            'teacher_availability_id' => ['nullable', 'integer', Rule::exists('teacher_availabilities', 'id')],
            'scheduled_slot_at' => ['nullable', 'date'],
            'preferred_day_of_week' => ['nullable', 'integer', 'between:0,6'],
            'preferred_starts_at' => ['nullable', 'date_format:H:i'],
            'duration_hours' => ['required', 'integer', 'min:1', 'max:4'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
