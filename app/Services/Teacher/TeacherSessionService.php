<?php

namespace App\Services\Teacher;

use App\Models\Teacher;
use App\Models\TeacherSession;
use App\Repositories\TeacherSessionRepository;
use App\Services\LiveSession\LiveSessionRoomService;
use App\Services\Realtime\RealtimeUpdateService;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Collection;
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
    ) {
    }

    /**
     * Retrieve teacher sessions.
     *
     * @return Collection<int, TeacherSession>
     */
    public function list(Teacher $teacher): Collection
    {
        $this->roomService->syncOwnedTeacherSessions($teacher);
        $this->cancelExpiredUpcomingSessions($teacher);

        return $this->sessions->forTeacher($teacher);
    }

    /**
     * Cancel an upcoming session and persist the reason.
     */
    public function cancel(Teacher $teacher, int $sessionId, string $reason): TeacherSession
    {
        $session = $this->sessions->ownedByTeacherOrFail($teacher, $sessionId);

        if ($session->status !== 'upcoming') {
            throw ValidationException::withMessages([
                'cancellation_reason' => 'يمكن إلغاء الجلسات القادمة فقط.',
            ]);
        }

        $session->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
        ]);

        $updatedSession = $session->fresh(['teacher', 'student', 'subject', 'complaints', 'files']);
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
                    $session->update([
                        'status' => 'cancelled',
                        'cancellation_reason' => $session->cancellation_reason ?: 'تم إلغاء الجلسة تلقائيًا لانتهاء وقتها دون إتمام.',
                    ]);
                }
            });
    }
}
