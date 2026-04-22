<?php

namespace App\Services\Realtime;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherSession;

/**
 * Builds opaque public channel names for dashboard and room updates.
 */
class RealtimeChannelService
{
    /**
     * Teacher dashboard channel.
     */
    public function teacherDashboardChannel(Teacher|int $teacher): string
    {
        $teacherId = $teacher instanceof Teacher ? $teacher->id : $teacher;

        return 'teacher-dashboard.'.$this->signature("teacher:{$teacherId}");
    }

    /**
     * Student dashboard channel.
     */
    public function studentDashboardChannel(Student|int $student): string
    {
        $studentId = $student instanceof Student ? $student->id : $student;

        return 'student-dashboard.'.$this->signature("student:{$studentId}");
    }

    /**
     * Live room channel for a session.
     */
    public function liveSessionChannel(TeacherSession|int $session): string
    {
        $sessionId = $session instanceof TeacherSession ? $session->id : $session;

        return 'live-session.'.$this->signature("session:{$sessionId}");
    }

    /**
     * Stable HMAC signature used as the public channel suffix.
     */
    private function signature(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key'));
    }
}
