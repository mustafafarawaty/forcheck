<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\UpdateStudentProfileRequest;
use App\Services\Student\StudentDirectoryService;
use App\Services\Student\StudentProfileService;
use App\Traits\ResolvesStudentAuthentication;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Handles student profile editing.
 */
class StudentProfileController extends Controller
{
    use ResolvesStudentAuthentication;

    public function __construct(
        private readonly StudentDirectoryService $directoryService,
        private readonly StudentProfileService $profileService,
    ) {
    }

    /**
     * Show profile form.
     */
    public function edit(Request $request): View
    {
        return view('student.pages.profile.edit', [
            'student' => $this->authenticatedStudent($request),
            'studyLevels' => $this->directoryService->levelLabels(),
        ]);
    }

    /**
     * Persist profile changes.
     */
    public function update(UpdateStudentProfileRequest $request): RedirectResponse
    {
        $student = $this->authenticatedStudent($request);
        $payload = $request->validated();

        if ($request->hasFile('avatar')) {
            $payload['avatar'] = $request->file('avatar');
        }

        $this->profileService->update($student, $payload);

        return back()->with('status', 'تم تحديث بيانات الطالب بنجاح.');
    }
}
