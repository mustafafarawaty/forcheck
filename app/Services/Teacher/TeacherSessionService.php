<?php

namespace App\Services\Teacher;

use App\Models\Teacher;
use App\Models\TeacherSession;
use App\Models\TeacherLiveRequest;
use App\Repositories\TeacherSessionRepository;
use App\Services\LiveSession\LiveSessionRoomService;
use App\Services\Realtime\RealtimeUpdateService;
use App\Services\Wallet\WalletService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Handles teacher session listing and cancellation logic.
 */
class TeacherSessionService
{
    public function __construct(
        private readonly TeacherSessionRepository $sessions,
        private readonly LiveSessionRoomService $roomService,
        private readonly RealtimeUpdateService $realtime,
        private readonly WalletService $wallets,
    ) {
    }

    /**
     * Retrieve teacher sessions.
     *
     * @return LengthAwarePaginator<int, TeacherSession>
     */
    public function list(Teacher $teacher, int $perPage = 10): LengthAwarePaginator
    {
        $this->roomService->syncOwnedTeacherSessions($teacher);
        $this->cancelExpiredUpcomingSessions($teacher);

        return $this->sessions->forTeacher($teacher, $perPage);
    }

    /**
     * Resolve a single owned session with fresh lifecycle state.
     */
    public function findOwned(Teacher $teacher, int $sessionId): TeacherSession
    {
        $this->roomService->syncOwnedTeacherSessions($teacher);
        $this->cancelExpiredUpcomingSessions($teacher);

        return $this->sessions->ownedByTeacherOrFail($teacher, $sessionId);
    }

    /**
     * Cancel an upcoming session and persist the reason.
     */
    public function cancel(Teacher $teacher, int $sessionId, ?string $reason = null): TeacherSession
    {
        $session = $this->sessions->ownedByTeacherOrFail($teacher, $sessionId);

        if ($session->status !== 'upcoming') {
            throw ValidationException::withMessages([
                'cancellation_reason' => 'يمكن إلغاء الجلسات القادمة فقط.',
            ]);
        }

        $session->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason ?: 'تم إلغاء الجلسة من قبل الأستاذ.',
        ]);

        $this->wallets->refundHeldSessionAmount($session);
        $this->cancelRelatedLiveRequest($session);

        $updatedSession = $session->fresh(['teacher', 'student', 'subject', 'complaints', 'files', 'walletTransactions']);
        $this->realtime->broadcastSessionParticipants($updatedSession);

        return $updatedSession;
    }

    /**
     * Cancel sessions that passed without completion.
     */
    private function cancelExpiredUpcomingSessions(Teacher $teacher): void
    {
        $teacher->sessions()
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
