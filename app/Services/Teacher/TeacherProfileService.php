<?php

namespace App\Services\Teacher;

use App\Models\Teacher;
use App\Repositories\TeacherRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Handles teacher profile updates.
 */
class TeacherProfileService
{
    public function __construct(
        private readonly TeacherRepository $teachers,
    ) {
    }

    /**
     * Update teacher profile and optional files.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Teacher $teacher, array $data): Teacher
    {
        return DB::transaction(function () use ($teacher, $data): Teacher {
            if (
                ($data['education_stage'] ?? $teacher->education_stage) === 'secondary'
                && $teacher->subjects()->where('level', 'university')->exists()
            ) {
                throw ValidationException::withMessages([
                    'education_stage' => 'لا يمكن تحويل المرحلة التعليمية إلى ثانوي قبل إزالة المواد الجامعية الحالية.',
                ]);
            }

            if (! empty($data['avatar'])) {
                if ($teacher->avatar_path) {
                    Storage::disk('public')->delete($teacher->avatar_path);
                }

                $data['avatar_path'] = Storage::disk('public')->putFile('teacher-avatars', $data['avatar']);
            }

            if (! empty($data['certificate'])) {
                if ($teacher->certificate_path) {
                    Storage::disk('public')->delete($teacher->certificate_path);
                }

                $data['certificate_path'] = Storage::disk('public')->putFile('teacher-certificates', $data['certificate']);
            }

            if (isset($data['education_stage'])) {
                $data['education_levels'] = $data['education_stage'] === 'university'
                    ? ['primary', 'middle', 'secondary', 'university']
                    : ['primary', 'middle', 'secondary'];
            }

            unset($data['avatar'], $data['certificate']);

            return $this->teachers->update($teacher, $data);
        });
    }
}
