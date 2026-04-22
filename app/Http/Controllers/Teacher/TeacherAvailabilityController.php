<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StoreTeacherAvailabilityRequest;
use App\Services\Teacher\TeacherAvailabilityService;
use App\Traits\ResolvesTeacherAuthentication;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Handles teacher availability slots.
 */
class TeacherAvailabilityController extends Controller
{
    use ResolvesTeacherAuthentication;

    public function __construct(
        private readonly TeacherAvailabilityService $availabilityService,
    ) {
    }

    /**
     * Show availability screen.
     */
    public function index(Request $request): View
    {
        $teacher = $this->authenticatedTeacher($request);

        return view('teacher.pages.availability.index', [
            'subjects' => $teacher->subjects()->orderBy('name')->get(),
            'availabilityWindows' => $this->availabilityService->summarizedWindows($teacher),
        ]);
    }

    /**
     * Store a new availability.
     */
    public function store(StoreTeacherAvailabilityRequest $request): RedirectResponse
    {
        $teacher = $this->authenticatedTeacher($request);
        $this->availabilityService->store($teacher, $request->validated());

        return back()->with('status', 'تمت إضافة الموعد.');
    }
}
