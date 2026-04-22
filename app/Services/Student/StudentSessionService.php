<?php

namespace App\Services\Student;

use App\Models\Student;
use App\Models\TeacherSession;
use App\Services\LiveSession\LiveSessionRoomService;
use App\Services\Realtime\RealtimeUpdateService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Handles student session listing and confirmation.
 */
class StudentSessionService
{
    public function __construct(
        private readonly LiveSessionRoomService $roomService,
        private readonly RealtimeUpdateService $realtime,
    ) {
    }

    /**
     * List sessions for student.
     *
     * @return Collection<int, TeacherSession>
     */
    public function list(Student $student): Collection
    {
        $this->roomService->syncOwnedStudentSessions($student);
        $this->cancelExpiredUpcomingSessions($student);

        return $student->sessions()
            ->with(['teacher', 'subject', 'complaints', 'files'])
            ->orderByDesc('scheduled_at')
            ->get();
    }

    /**
     * Confirm attendance for upcoming session.
     */
    public function confirmAttendance(Student $student, int $sessionId): TeacherSession
    {
        $session = $student->sessions()->findOrFail($sessionId);

        if ($session->status !== 'upcoming') {
            throw ValidationException::withMessages([
                'session' => 'يمكن تأكيد حضور الجلسات القادمة فقط.',
            ]);
        }

        $session->update([
            'student_confirmed_at' => now(),
        ]);

        $updatedSession = $session->fresh(['teacher', 'student', 'subject', 'complaints', 'files']);
        $this->realtime->broadcastSessionParticipants($updatedSession);

        return $updatedSession;
    }

    /**
     * Cancel an upcoming session by the student.
     */
    public function cancel(Student $student, int $sessionId): TeacherSession
    {
        $session = $student->sessions()->findOrFail($sessionId);

        if ($session->status !== 'upcoming') {
            throw ValidationException::withMessages([
                'session' => 'يمكن إلغاء الجلسات القادمة فقط.',
            ]);
        }

        $session->update([
            'status' => 'cancelled',
            'cancellation_reason' => $session->cancellation_reason ?: 'تم إلغاء الجلسة من قبل الطالب.',
        ]);

        $updatedSession = $session->fresh(['teacher', 'student', 'subject', 'complaints', 'files']);
        $this->realtime->broadcastSessionParticipants($updatedSession);

        return $updatedSession;
    }

    /**
     * Cancel overdue sessions automatically.
     */
    private function cancelExpiredUpcomingSessions(Student $student): void
    {
        $student->sessions()
            ->where('status', 'upcoming')
            ->get()
            ->each(function (TeacherSession $session): void {
                if (! $session->scheduled_at) {
                    return;
                }

                $sessionEnd = $session->ended_at
                    ?? $session->scheduled_at->copy()->addHours((int) ($session->duration_hours ?: 1));

                if ($sessionEnd->lessThanOrEqualTo(Carbon::now())) {
                    $session->update([
                        'status' => 'cancelled',
                        'cancellation_reason' => $session->cancellation_reason ?: 'تم إلغاء الجلسة تلقائيًا لانتهاء وقتها دون إتمام.',
                    ]);
                }
            });
    }
}
