<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\Student\StudentDashboardService;
use App\Services\Student\StudentDirectoryService;
use App\Traits\ResolvesStudentAuthentication;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Student dashboard controller.
 */
class StudentDashboardController extends Controller
{
    use ResolvesStudentAuthentication;

    public function __construct(
        private readonly StudentDashboardService $dashboardService,
        private readonly StudentDirectoryService $directoryService,
    ) {
    }

    /**
     * Show the student dashboard.
     */
    public function __invoke(Request $request): View
    {
        $student = $this->authenticatedStudent($request);
        $data = $this->dashboardService->build($student);

        return view('student.pages.dashboard', $data + [
            'subjectOptions' => $this->directoryService->availableSubjectNames($student),
            'dayOptions' => $this->directoryService->dayLabels(),
            'hourOptions' => $this->directoryService->hourOptions(),
            'durationOptions' => $this->directoryService->durationOptions(),
        ]);
    }
}
