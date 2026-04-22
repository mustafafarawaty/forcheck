<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\TeacherLoginRequest;
use App\Http\Requests\Teacher\TeacherRegisterRequest;
use App\Services\Teacher\TeacherAuthService;
use App\Services\Teacher\TeacherSubjectService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

/**
 * Handles teacher authentication and registration screens.
 */
class TeacherAuthController extends Controller
{
    public function __construct(
        private readonly TeacherAuthService $authService,
        private readonly TeacherSubjectService $subjectService,
    ) {
    }

    /**
     * Show login form.
     */
    public function showLogin(): View
    {
        return view('teacher.pages.auth.login');
    }

    /**
     * Attempt teacher login.
     */
    public function login(TeacherLoginRequest $request): RedirectResponse
    {
        $teacher = $this->authService->attemptLogin(
            $request->validated('phone'),
            $request->validated('password'),
        );

        if (! $teacher) {
            return back()
                ->withInput($request->safe()->only('phone'))
                ->withErrors(['phone' => 'بيانات الدخول غير صحيحة.']);
        }

        $request->session()->regenerate();
        $request->session()->put('teacher_id', $teacher->id);

        return redirect()->route('teacher.dashboard');
    }

    /**
     * Show registration form.
     */
    public function showRegister(): View
    {
        return view('teacher.pages.auth.register', [
            'educationStages' => $this->subjectService::stageLabels(),
        ]);
    }

    /**
     * Store a new teacher and log them in.
     */
    public function register(TeacherRegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['certificate_path'] = Storage::disk('public')->putFile('teacher-certificates', $request->file('certificate'));
        unset($validated['certificate'], $validated['password_confirmation']);

        $teacher = $this->authService->register($validated);

        $request->session()->regenerate();
        $request->session()->put('teacher_id', $teacher->id);

        return redirect()->route('teacher.dashboard')->with('status', 'تم إنشاء الحساب بنجاح.');
    }

    /**
     * Logout the teacher.
     */
    public function logout(): RedirectResponse
    {
        session()->forget('teacher_id');
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('teacher.login');
    }
}
