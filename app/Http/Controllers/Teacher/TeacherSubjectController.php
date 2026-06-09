<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StoreTeacherSubjectRequest;
use App\Services\AppSettingsService;
use App\Services\Teacher\TeacherSubjectService;
use App\Traits\ResolvesTeacherAuthentication;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Handles teacher subjects.
 */
class TeacherSubjectController extends Controller
{
    use ResolvesTeacherAuthentication;

    public function __construct(
        private readonly TeacherSubjectService $subjectService,
        private readonly AppSettingsService $settings,
    ) {
    }

    /**
     * Show teacher subjects page.
     */
    public function index(Request $request): View
    {
        $teacher = $this->authenticatedTeacher($request);

        return view('teacher.pages.subjects.index', [
            'subjects' => $teacher->subjects()->latest()->get(),
            'allowedLevels' => $this->subjectService->allowedLevelsForTeacher($teacher),
            'adminCommissionPercentage' => $this->settings->adminCommissionPercentage(),
        ]);
    }

    /**
     * Store a new teacher subject.
     */
    public function store(StoreTeacherSubjectRequest $request): RedirectResponse
    {
        $teacher = $this->authenticatedTeacher($request);
        $this->subjectService->store($teacher, $request->validated());

        return back()->with('status', 'تمت إضافة المادة.');
    }
}
