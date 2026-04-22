<?php

namespace App\Services\Teacher;

use App\Models\Teacher;
use App\Models\TeacherComplaint;
use Illuminate\Validation\ValidationException;

/**
 * Handles complaint creation and listing.
 */
class TeacherComplaintService
{
    /**
     * Store a complaint while ensuring session ownership.
     *
     * @param  array<string, mixed>  $data
     */
    public function store(Teacher $teacher, array $data): TeacherComplaint
    {
        if (! empty($data['teacher_session_id']) && ! $teacher->sessions()->whereKey($data['teacher_session_id'])->exists()) {
            throw ValidationException::withMessages([
                'teacher_session_id' => 'الجلسة المحددة لا تعود لهذا الأستاذ.',
            ]);
        }

        return $teacher->complaints()->create([
            ...$data,
            'submitted_at' => now(),
        ]);
    }
}
