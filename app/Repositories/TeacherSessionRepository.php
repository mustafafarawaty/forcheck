<?php

namespace App\Repositories;

use App\Models\Teacher;
use App\Models\TeacherSession;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Encapsulates querying teacher sessions.
 */
class TeacherSessionRepository
{
    /**
     * Fetch sessions for the teacher with related subject and complaints.
     *
     * @return LengthAwarePaginator<int, TeacherSession>
     */
    public function forTeacher(Teacher $teacher, int $perPage = 10): LengthAwarePaginator
    {
        return $teacher->sessions()
            ->with(['subject', 'complaints', 'student', 'files', 'walletTransactions'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Resolve an owned session or fail.
     */
    public function ownedByTeacherOrFail(Teacher $teacher, int $sessionId): TeacherSession
    {
        return $teacher->sessions()
            ->with(['subject', 'complaints', 'student', 'files', 'walletTransactions'])
            ->findOrFail($sessionId);
    }
}
