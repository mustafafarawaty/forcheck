<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StoreTeacherComplaintRequest;
use App\Services\Teacher\TeacherComplaintService;
use App\Traits\ResolvesTeacherAuthentication;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Handles teacher complaints.
 */
class TeacherComplaintController extends Controller
{
    use ResolvesTeacherAuthentication;

    public function __construct(
        private readonly TeacherComplaintService $complaintService,
    ) {
    }

    /**
     * Show teacher complaints page.
     */
    public function index(Request $request): View
    {
        $teacher = $this->authenticatedTeacher($request);

        return view('teacher.pages.complaints.index', [
            'complaints' => $teacher->complaints()->with('session.subject')->latest()->get(),
            'sessions' => $teacher->sessions()->with('subject')->latest()->get(),
        ]);
    }

    /**
     * Store a new complaint.
     */
    public function store(StoreTeacherComplaintRequest $request): RedirectResponse
    {
        $teacher = $this->authenticatedTeacher($request);
        $this->complaintService->store($teacher, $request->validated());

        return back()->with('status', 'تم إرسال الشكوى.');
    }
}
