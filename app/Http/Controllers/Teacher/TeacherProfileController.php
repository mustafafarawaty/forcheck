<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\UpdateTeacherProfileRequest;
use App\Services\Teacher\TeacherProfileService;
use App\Services\Teacher\TeacherSubjectService;
use App\Traits\ResolvesTeacherAuthentication;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Handles teacher profile editing.
 */
class TeacherProfileController extends Controller
{
    use ResolvesTeacherAuthentication;

    public function __construct(
        private readonly TeacherProfileService $profileService,
        private readonly TeacherSubjectService $subjectService,
    ) {
    }

    /**
     * Show profile form.
     */
    public function edit(Request $request): View
    {
        return view('teacher.pages.profile.edit', [
            'teacher' => $this->authenticatedTeacher($request),
            'educationStages' => $this->subjectService::stageLabels(),
        ]);
    }

    /**
     * Persist profile changes.
     */
    public function update(UpdateTeacherProfileRequest $request): RedirectResponse
    {
        $teacher = $this->authenticatedTeacher($request);
        $payload = $request->validated();

        if ($request->hasFile('avatar')) {
            $payload['avatar'] = $request->file('avatar');
        }

        if ($request->hasFile('certificate')) {
            $payload['certificate'] = $request->file('certificate');
        }

        $this->profileService->update($teacher, $payload);

        return back()->with('status', 'تم تحديث بيانات الأستاذ بنجاح.');
    }
}
