<?php

namespace App\Services\Student;

use App\Models\Student;
use App\Repositories\StudentRepository;
use Illuminate\Support\Facades\Storage;

/**
 * Handles student profile updates.
 */
class StudentProfileService
{
    public function __construct(
        private readonly StudentRepository $students,
    ) {
    }

    /**
     * Update student profile data and optional avatar.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Student $student, array $data): Student
    {
        if (! empty($data['avatar'])) {
            if ($student->avatar_path) {
                Storage::disk('public')->delete($student->avatar_path);
            }

            $data['avatar_path'] = Storage::disk('public')->putFile('student-avatars', $data['avatar']);
        }

        unset($data['avatar']);

        return $this->students->update($student, $data);
    }
}
