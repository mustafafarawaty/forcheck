<?php

namespace App\Services\Teacher;

use App\Models\Teacher;
use App\Models\TeacherSession;
use App\Services\LiveSession\LiveSessionRoomService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Aggregates teacher dashboard metrics and chart data.
 */
class TeacherDashboardService
{
    public function __construct(
        private readonly LiveSessionRoomService $roomService,
    ) {
    }

    /**
     * Build dashboard payload for the teacher.
     *
     * @return array<string, mixed>
     */
    public function build(Teacher $teacher): array
    {
        $this->roomService->syncOwnedTeacherSessions($teacher);

        $sessions = $teacher->sessions()->with('subject')->orderByDesc('scheduled_at')->get();
        $sessions = $this->expireUpcomingSessions($sessions);
        $now = Carbon::now();
        $monthStart = $now->copy()->startOfMonth();

        $monthSessions = $sessions->filter(fn ($session) => $session->scheduled_at?->greaterThanOrEqualTo($monthStart));
        $completedMonthSessions = $monthSessions->where('status', 'completed');
        $todaySessions = $sessions
            ->filter(fn ($session) => $session->scheduled_at?->isToday())
            ->sortBy('scheduled_at')
            ->values();
        $countdownSession = $sessions
            ->where('status', 'upcoming')
            ->filter(function (TeacherSession $session) use ($now): bool {
                if (! $session->scheduled_at) {
                    return false;
                }

                $minutes = $now->diffInMinutes($session->scheduled_at, false);

                return $minutes >= 0 && $minutes <= 60;
            })
            ->sortBy('scheduled_at')
            ->first();
        $activeSession = $this->roomService->joinCandidateForTeacher($teacher);

        return [
            'stats' => [
                'sessions_count' => $monthSessions->count(),
                'students_count' => $monthSessions->pluck('student_name')->unique()->count(),
                'monthly_profit' => (float) $completedMonthSessions->sum('price'),
                'upcoming_count' => $sessions->where('status', 'upcoming')->count(),
            ],
            'today_sessions' => $todaySessions,
            'countdownSession' => $countdownSession,
            'subjects' => $teacher->subjects()->latest()->get(),
            'complaints' => $teacher->complaints()->latest()->take(5)->get(),
            'liveRequests' => $teacher->liveRequests()
                ->with(['student', 'subject'])
                ->where('status', 'pending')
                ->latest('requested_at')
                ->take(5)
                ->get(),
            'chart' => $this->chartData($sessions),
            'activeSession' => $activeSession,
            'activeSessionPayload' => $activeSession
                ? $this->roomService->quickJoinPayload($activeSession, 'teacher')
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

                return $session->fresh();
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
            'week' => $this->aggregateRange($sessions, now()->startOfWeek(), 7, 'D'),
            'month' => $this->aggregateRange($sessions, now()->startOfMonth(), 6, 'W'),
            'year' => $this->aggregateRange($sessions, now()->startOfYear(), 6, 'M'),
        ];
    }

    /**
     * Aggregate sessions and profit for a simple range.
     *
     * @return array<string, mixed>
     */
    private function aggregateRange(Collection $sessions, Carbon $start, int $steps, string $mode): array
    {
        $labels = [];
        $profits = [];
        $counts = [];

        for ($index = 0; $index < $steps; $index++) {
            $periodStart = match ($mode) {
                'D' => $start->copy()->addDays($index),
                'W' => $start->copy()->addWeeks($index),
                default => $start->copy()->addMonths($index),
            };

            $periodEnd = match ($mode) {
                'D' => $periodStart->copy()->endOfDay(),
                'W' => $periodStart->copy()->endOfWeek(),
                default => $periodStart->copy()->endOfMonth(),
            };

            $bucket = $sessions->filter(
                fn ($session) => $session->scheduled_at?->betweenIncluded($periodStart, $periodEnd)
            );

            $labels[] = match ($mode) {
                'D' => $periodStart->translatedFormat('D'),
                'W' => 'أ' . ($index + 1),
                default => $periodStart->translatedFormat('M'),
            };
            $profits[] = (float) $bucket->where('status', 'completed')->sum('price');
            $counts[] = $bucket->count();
        }

        return [
            'labels' => $labels,
            'profits' => $profits,
            'sessions' => $counts,
            'totalProfit' => number_format(array_sum($profits), 0) . ' ر.س',
            'totalSessions' => array_sum($counts) . ' جلسة',
            'trend' => '+' . max(1, (int) round(array_sum($counts) / max(1, $steps))) . '%',
        ];
    }
}
