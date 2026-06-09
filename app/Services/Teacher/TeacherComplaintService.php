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

        if (! empty($data['attachment'])) {
            $data['attachment_path'] = $data['attachment']->store("complaints/teachers/{$teacher->id}", 'public');
        }

        unset($data['attachment']);

        return $teacher->complaints()->create([
            ...$data,
            'submitted_by' => 'teacher',
            'status' => $data['status'] ?? 'pending',
            'submitted_at' => now(),
        ]);
    }
}
