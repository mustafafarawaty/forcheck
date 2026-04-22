<?php

namespace App\Repositories;

use App\Models\Teacher;
use App\Models\TeacherSession;
use Illuminate\Database\Eloquent\Collection;

/**
 * Encapsulates querying teacher sessions.
 */
class TeacherSessionRepository
{
    /**
     * Fetch sessions for the teacher with related subject and complaints.
     *
     * @return Collection<int, TeacherSession>
     */
    public function forTeacher(Teacher $teacher): Collection
    {
        return $teacher->sessions()
            ->with(['subject', 'complaints', 'student', 'files'])
            ->orderByDesc('scheduled_at')
            ->get();
    }

    /**
     * Resolve an owned session or fail.
     */
    public function ownedByTeacherOrFail(Teacher $teacher, int $sessionId): TeacherSession
    {
        return $teacher->sessions()
            ->with(['subject', 'complaints', 'student', 'files'])
            ->findOrFail($sessionId);
    }
}
