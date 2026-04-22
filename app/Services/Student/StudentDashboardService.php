<?php

namespace App\Services\Student;

use App\Models\Student;
use App\Models\TeacherSession;
use App\Services\LiveSession\LiveSessionRoomService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Aggregates student dashboard metrics and chart payloads.
 */
class StudentDashboardService
{
    public function __construct(
        private readonly LiveSessionRoomService $roomService,
    ) {
    }

    /**
     * Build dashboard payload for the student.
     *
     * @return array<string, mixed>
     */
    public function build(Student $student): array
    {
        $this->roomService->syncOwnedStudentSessions($student);

        $sessions = $student->sessions()
            ->with(['teacher', 'subject'])
            ->orderByDesc('scheduled_at')
            ->get();
        $sessions = $this->expireUpcomingSessions($sessions);

        $currentMonth = $sessions->filter(
            fn ($session) => $session->scheduled_at?->greaterThanOrEqualTo(now()->startOfMonth())
        );

        $completedSessions = $currentMonth->where('status', 'completed');
        $totalMinutes = (int) $completedSessions->sum(function ($session): int {
            if (! $session->scheduled_at || ! $session->ended_at) {
                return 0;
            }

            return $session->scheduled_at->diffInMinutes($session->ended_at);
        });
        $activeSession = $this->roomService->joinCandidateForStudent($student);

        return [
            'stats' => [
                'sessions_count' => $currentMonth->count(),
                'hours_count' => round($totalMinutes / 60, 1),
                'subjects_count' => $sessions->pluck('subject.name')->filter()->unique()->count(),
                'upcoming_count' => $sessions->where('status', 'upcoming')->count(),
            ],
            'subjects' => $sessions->pluck('subject.name')->filter()->unique()->values(),
            'upcomingSessions' => $sessions->where('status', 'upcoming')->take(4)->values(),
            'liveRequests' => $student->liveRequests()->with(['teacher', 'subject'])->latest()->take(4)->get(),
            'chart' => $this->chartData($sessions),
            'activeSession' => $activeSession,
            'activeSessionPayload' => $activeSession
                ? $this->roomService->quickJoinPayload($activeSession, 'student')
                : null,
        ];
    }

    /**
     * Mark expired upcoming sessions as cancelled.
     *
     * @param  Collection<int, TeacherSession>  $sessions
     * @return Collection<int, TeacherSession>
     */
    private function expireUpcomingSessions(Collection $sessions): Collection
    {
        $now = Carbon::now();

        return $sessions->map(function (TeacherSession $session) use ($now): TeacherSession {
            if ($session->status !== 'upcoming' || ! $session->scheduled_at) {
                return $session;
            }

            $sessionEnd = $session->ended_at
                ?? $session->scheduled_at->copy()->addHours((int) ($session->duration_hours ?: 1));

            if ($sessionEnd->lessThanOrEqualTo($now)) {
                $session->update([
                    'status' => 'cancelled',
                    'cancellation_reason' => $session->cancellation_reason ?: 'تم إلغاء الجلسة تلقائيًا لانتهاء وقتها دون إتمام.',
                ]);

                return $session->fresh(['teacher', 'subject']);
            }

            return $session;
        });
    }

    /**
     * Prepare chart ranges for week, month and year.
     *
     * @param  Collection<int, \App\Models\TeacherSession>  $sessions
     * @return array<string, array<string, mixed>>
     */
    private function chartData(Collection $sessions): array
    {
        return [
            'week' => $this->aggregateRange($sessions, now()->startOfWeek(), 7, 'day'),
            'month' => $this->aggregateRange($sessions, now()->startOfMonth(), 6, 'week'),
            'year' => $this->aggregateRange($sessions, now()->startOfYear(), 6, 'month'),
        ];
    }

    /**
     * Aggregate session counts and hours.
     *
     * @return array<string, mixed>
     */
    private function aggregateRange(Collection $sessions, Carbon $start, int $steps, string $mode): array
    {
        $labels = [];
        $hours = [];
        $counts = [];

        for ($index = 0; $index < $steps; $index++) {
            $periodStart = match ($mode) {
                'day' => $start->copy()->addDays($index),
                'week' => $start->copy()->addWeeks($index),
                default => $start->copy()->addMonths($index),
            };

            $periodEnd = match ($mode) {
                'day' => $periodStart->copy()->endOfDay(),
                'week' => $periodStart->copy()->endOfWeek(),
                default => $periodStart->copy()->endOfMonth(),
            };

            $bucket = $sessions->filter(
                fn ($session) => $session->scheduled_at?->betweenIncluded($periodStart, $periodEnd)
            );

            $labels[] = match ($mode) {
                'day' => $periodStart->translatedFormat('D'),
                'week' => 'أ' . ($index + 1),
                default => $periodStart->translatedFormat('M'),
            };

            $counts[] = $bucket->count();
            $hours[] = round(
                $bucket->sum(function ($session): int {
                    if (! $session->scheduled_at || ! $session->ended_at) {
                        return 0;
                    }

                    return $session->scheduled_at->diffInMinutes($session->ended_at);
                }) / 60,
                1
            );
        }

        return [
            'labels' => $labels,
            'sessions' => $counts,
            'hours' => $hours,
            'totalHours' => array_sum($hours) . ' ساعة',
            'totalSessions' => array_sum($counts) . ' جلسة',
            'trend' => '+' . max(1, (int) round(array_sum($counts) / max(1, $steps))) . '%',
        ];
    }
}
