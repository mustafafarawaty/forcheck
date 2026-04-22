<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Services\Teacher\TeacherDashboardService;
use App\Traits\ResolvesTeacherAuthentication;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Teacher dashboard controller.
 */
class TeacherDashboardController extends Controller
{
    use ResolvesTeacherAuthentication;

    public function __construct(
        private readonly TeacherDashboardService $dashboardService,
    ) {
    }

    /**
     * Show the teacher dashboard.
     */
    public function __invoke(Request $request): View
    {
        $teacher = $this->authenticatedTeacher($request);
        $data = $this->dashboardService->build($teacher);

        return view('teacher.pages.dashboard', $data);
    }
}
