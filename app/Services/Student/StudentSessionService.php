<?php

namespace App\Services\Student;

use App\Models\Student;
use App\Models\TeacherSession;
use App\Models\TeacherLiveRequest;
use App\Services\LiveSession\LiveSessionRoomService;
use App\Services\Realtime\RealtimeUpdateService;
use App\Services\Wallet\WalletService;
use Illuminate\Pagination\LengthAwarePaginator;
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
        private readonly WalletService $wallets,
    ) {
    }

    /**
     * List sessions for student.
     *
     * @return LengthAwarePaginator<int, TeacherSession>
     */
    public function list(Student $student, int $perPage = 10): LengthAwarePaginator
    {
        $this->roomService->syncOwnedStudentSessions($student);
        $this->cancelExpiredUpcomingSessions($student);

        return $student->sessions()
            ->with(['teacher', 'subject', 'complaints', 'files', 'walletTransactions'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
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

        $updatedSession = $session->fresh(['teacher', 'student', 'subject', 'complaints', 'files', 'walletTransactions']);
        $this->realtime->broadcastSessionParticipants($updatedSession);

        return $updatedSession;
    }

    /**
     * Cancel an upcoming session by the student.
     */
    public function cancel(Student $student, int $sessionId, ?string $reason = null): TeacherSession
    {
        $session = $student->sessions()->findOrFail($sessionId);

        if ($session->status !== 'upcoming') {
            throw ValidationException::withMessages([
                'session' => 'يمكن إلغاء الجلسات القادمة فقط.',
            ]);
        }

        $session->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason ?: 'تم إلغاء الجلسة من قبل الطالب.',
        ]);

        $this->wallets->refundHeldSessionAmount($session);
        $this->cancelRelatedLiveRequest($session);

        $updatedSession = $session->fresh(['teacher', 'student', 'subject', 'complaints', 'files', 'walletTransactions']);
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
                    $this->wallets->refundHeldSessionAmount($session);

                    $session->update([
                        'status' => 'cancelled',
                        'cancellation_reason' => $session->cancellation_reason ?: 'تم إلغاء الجلسة تلقائيًا لانتهاء وقتها دون إتمام.',
                    ]);
                }
            });
    }

    private function cancelRelatedLiveRequest(TeacherSession $session): void
    {
        TeacherLiveRequest::query()
            ->where('teacher_session_id', $session->id)
            ->where('status', 'accepted')
            ->update([
                'status' => 'cancelled',
                'responded_at' => now(),
            ]);
    }
}
