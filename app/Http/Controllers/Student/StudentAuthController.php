<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StudentLoginRequest;
use App\Http\Requests\Student\StudentRegisterRequest;
use App\Services\Student\StudentAuthService;
use App\Services\Student\StudentDirectoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Handles student authentication screens and actions.
 */
class StudentAuthController extends Controller
{
    public function __construct(
        private readonly StudentAuthService $authService,
        private readonly StudentDirectoryService $directoryService,
    ) {
    }

    /**
     * Show login form.
     */
    public function showLogin(): View
    {
        return view('student.pages.auth.login');
    }

    /**
     * Attempt student login.
     */
    public function login(StudentLoginRequest $request): RedirectResponse
    {
        $student = $this->authService->attemptLogin(
            $request->validated('phone'),
            $request->validated('password'),
        );

        if (! $student) {
            return back()
                ->withInput($request->safe()->only('phone'))
                ->withErrors(['phone' => 'بيانات الدخول غير صحيحة.']);
        }

        if ($student->isDisabled()) {
            return back()
                ->withInput($request->safe()->only('phone'))
                ->withErrors(['phone' => 'الحساب معطل من قبل الإدارة. يرجى التواصل مع الدعم لمراجعة الحالة.']);
        }

        $request->session()->regenerate();
        $request->session()->put('student_id', $student->id);

        return redirect()->route('student.dashboard');
    }

    /**
     * Show registration form.
     */
    public function showRegister(): View
    {
        return view('student.pages.auth.register', [
            'studyLevels' => $this->directoryService->levelLabels(),
        ]);
    }

    /**
     * Register a new student and log them in.
     */
    public function register(StudentRegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        unset($validated['password_confirmation']);

        $student = $this->authService->register($validated);

        $request->session()->regenerate();
        $request->session()->put('student_id', $student->id);

        return redirect()->route('student.dashboard')->with('status', 'تم إنشاء حساب الطالب بنجاح.');
    }

    /**
     * Logout the student.
     */
    public function logout(): RedirectResponse
    {
        session()->forget('student_id');
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('student.login');
    }
}
