<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherSession;
use Carbon\Carbon;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(): View
    {
        $sessions = TeacherSession::query()->get();

        return view('admin.pages.dashboard', [
            'stats' => [
                'students_count' => Student::query()->count(),
                'teachers_count' => Teacher::query()->count(),
                'sessions_count' => $sessions->count(),
                'active_sessions_count' => $sessions->whereIn('status', ['upcoming', 'in_progress'])->count(),
            ],
            'chart' => $this->chartData($sessions),
        ]);
    }

    private function chartData($sessions): array
    {
        return [
            'week' => $this->aggregateRange($sessions, now()->startOfWeek(), 7, 'D'),
            'month' => $this->aggregateRange($sessions, now()->startOfMonth(), 6, 'W'),
            'year' => $this->aggregateRange($sessions, now()->startOfYear(), 6, 'M'),
        ];
    }

    private function aggregateRange($sessions, Carbon $start, int $steps, string $mode): array
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
            $bucket = $sessions->filter(fn ($session) => $session->scheduled_at?->betweenIncluded($periodStart, $periodEnd));

            $labels[] = match ($mode) {
                'D' => $periodStart->translatedFormat('D'),
                'W' => 'أ' . ($index + 1),
                default => $periodStart->translatedFormat('M'),
            };
            $profits[] = (float) $bucket->where('status', 'completed')->sum('admin_commission_amount');
            $counts[] = $bucket->count();
        }

        return [
            'labels' => $labels,
            'profits' => $profits,
            'sessions' => $counts,
            'totalProfit' => number_format(array_sum($profits), 0) . ' ل.س',
            'totalSessions' => array_sum($counts) . ' جلسة',
            'trend' => '+' . max(1, (int) round(array_sum($counts) / max(1, $steps))) . '%',
        ];
    }
}
