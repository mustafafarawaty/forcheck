<?php

namespace App\Providers;

use App\Models\Student;
use App\Models\Teacher;
use App\Services\LiveSession\LiveSessionRoomService;
use App\Services\Student\StudentDirectoryService;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

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
                    ->sum('price');

                $activeSession = $roomService->joinCandidateForTeacher($teacher);
                $activeSessionPayload = $activeSession
                    ? $roomService->quickJoinPayload($activeSession, 'teacher')
                    : null;
            }

            $view->with('currentTeacher', $teacher);
            $view->with('currentTeacherMonthlyProfit', $monthlyProfit);
            $view->with('teacherActiveSessionPayload', $activeSessionPayload);
        });

        View::composer('student.*', function ($view): void {
            $studentId = session('student_id');
            $student = $studentId ? Student::query()->find($studentId) : null;
            $directoryService = app(StudentDirectoryService::class);
            $roomService = app(LiveSessionRoomService::class);

            $monthlyHours = 0;
            $activeSessionPayload = null;

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
            }

            $view->with('currentStudent', $student);
            $view->with('currentStudentMonthlyHours', $monthlyHours);
            $view->with('studentSubjectOptions', $student ? $directoryService->availableSubjectNames($student) : collect());
            $view->with('studentDayOptions', $directoryService->dayLabels());
            $view->with('studentHourOptions', $directoryService->hourOptions());
            $view->with('studentDurationOptions', $directoryService->durationOptions());
            $view->with('studentActiveSessionPayload', $activeSessionPayload);
        });
    }
}
