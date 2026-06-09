<?php

namespace App\Providers;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherComplaint;
use App\Models\TeacherSession;
use App\Models\WalletTransaction;
use App\Services\LiveSession\LiveSessionRoomService;
use App\Services\Student\StudentDirectoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureViteForCurrentRequest();

        View::composer('teacher.*', function ($view): void {
            $teacherId = session('teacher_id');
            $teacher = $teacherId ? Teacher::query()->with('subjects')->find($teacherId) : null;
            $roomService = app(LiveSessionRoomService::class);

            $monthlyProfit = 0;
            $activeSessionPayload = null;

            if ($teacher) {
                $monthlyProfit = (float) $teacher->sessions()
                    ->where('status', 'completed')
                    ->where('scheduled_at', '>=', Carbon::now()->startOfMonth())
                    ->sum('teacher_earning_amount');

                $activeSession = $roomService->joinCandidateForTeacher($teacher);
                $activeSessionPayload = $activeSession
                    ? $roomService->quickJoinPayload($activeSession, 'teacher')
                    : null;
            }

            $view->with('currentTeacher', $teacher);
            $view->with('currentTeacherMonthlyProfit', $monthlyProfit);
            $view->with('teacherActiveSessionPayload', $activeSessionPayload);
            // teacher unread counts (wallet, complaints)
            $teacherUnread = [
                'wallet' => 0,
                'complaints' => 0,
                'sessions' => 0,
            ];

            if ($teacher) {
                if (Schema::hasColumn('wallet_transactions', 'teacher_read_at')) {
                    $teacherUnread['wallet'] = WalletTransaction::query()
                        ->where('teacher_id', $teacher->id)
                        ->whereNull('teacher_read_at')
                        ->count();
                }

                if (Schema::hasColumn('teacher_complaints', 'teacher_read_at')) {
                    $teacherUnread['complaints'] = TeacherComplaint::query()
                        ->whereNull('teacher_read_at')
                        ->where(function ($query) use ($teacher): void {
                            $query->where('teacher_id', $teacher->id)
                                ->orWhereHas('session', fn ($q) => $q->where('teacher_id', $teacher->id));
                        })
                        ->count();
                }

                if (Schema::hasColumn('teacher_sessions', 'teacher_read_at')) {
                    $teacherUnread['sessions'] = TeacherSession::query()
                        ->where('teacher_id', $teacher->id)
                        ->whereIn('status', ['upcoming', 'in_progress'])
                        ->whereNull('teacher_read_at')
                        ->count();
                } else {
                    $teacherUnread['sessions'] = TeacherSession::query()
                        ->where('teacher_id', $teacher->id)
                        ->whereIn('status', ['upcoming', 'in_progress'])
                        ->count();
                }
            }

            $view->with('teacherUnreadCounts', $teacherUnread);
        });

        View::composer('student.*', function ($view): void {
            $studentId = session('student_id');
            $student = $studentId ? Student::query()->find($studentId) : null;
            $directoryService = app(StudentDirectoryService::class);
            $roomService = app(LiveSessionRoomService::class);

            $monthlyHours = 0;
            $activeSessionPayload = null;
            $unreadCounts = [
                'wallet' => 0,
                'complaints' => 0,
            ];

            if ($student) {
                $monthlyHours = round(
                    $student->sessions()
                        ->where('status', 'completed')
                        ->where('scheduled_at', '>=', Carbon::now()->startOfMonth())
                        ->get()
                        ->sum(function ($session): int {
                            if (! $session->scheduled_at || ! $session->ended_at) {
                                return 0;
                            }

                            return $session->scheduled_at->diffInMinutes($session->ended_at);
                        }) / 60,
                    1
                );

                $activeSession = $roomService->joinCandidateForStudent($student);
                $activeSessionPayload = $activeSession
                    ? $roomService->quickJoinPayload($activeSession, 'student')
                    : null;

                $unreadCounts['wallet'] = WalletTransaction::query()
                    ->where('student_id', $student->id)
                    ->whereNull('student_read_at')
                    ->count();

                $unreadCounts['complaints'] = TeacherComplaint::query()
                    ->whereNull('student_read_at')
                    ->where(function ($query) use ($student): void {
                        $query->where('student_id', $student->id)
                            ->orWhereHas('session', fn ($sessionQuery) => $sessionQuery->where('student_id', $student->id));
                    })
                    ->count();
            }

            $view->with('currentStudent', $student);
            $view->with('currentStudentMonthlyHours', $monthlyHours);
            $view->with('studentSubjectOptions', $student ? $directoryService->availableSubjectNames($student) : collect());
            $view->with('studentDayOptions', $directoryService->dayLabels());
            $view->with('studentHourOptions', $directoryService->hourOptions());
            $view->with('studentDurationOptions', $directoryService->durationOptions());
            $view->with('studentActiveSessionPayload', $activeSessionPayload);
            $view->with('studentUnreadCounts', $unreadCounts);
        });

        // admin unread counts
        View::composer('admin.*', function ($view): void {
            $adminUnread = [
                'wallet' => 0,
                'complaints' => 0,
                'sessions' => 0,
            ];

            $adminUnread['wallet'] = WalletTransaction::query()
                ->where('status', 'pending')
                ->count();

            if (Schema::hasColumn('teacher_complaints', 'admin_read_at')) {
                $adminUnread['complaints'] = TeacherComplaint::query()
                    ->whereNull('admin_read_at')
                    ->count();
            }

            if (Schema::hasColumn('teacher_sessions', 'admin_read_at')) {
                $adminUnread['sessions'] = TeacherSession::query()
                    ->whereIn('status', ['upcoming', 'in_progress'])
                    ->whereNull('admin_read_at')
                    ->count();
            } else {
                $adminUnread['sessions'] = TeacherSession::query()
                    ->whereIn('status', ['upcoming', 'in_progress'])
                    ->count();
            }

            $view->with('adminUnreadCounts', $adminUnread);
        });
    }

    protected function configureViteForCurrentRequest(): void
    {
        $hotFile = public_path('hot');
        $manifestFile = public_path('build/manifest.json');

        if (! is_file($hotFile) || ! is_file($manifestFile)) {
            return;
        }

        if ($this->shouldUseBuiltAssets(request())) {
            Vite::useHotFile(storage_path('framework/vite.hot.disabled'));
        }
    }

    protected function shouldUseBuiltAssets(Request $request): bool
    {
        return ! in_array($request->getHost(), ['127.0.0.1', 'localhost', '::1'], true);
    }
}
