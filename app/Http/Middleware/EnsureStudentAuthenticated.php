<?php

namespace App\Http\Middleware;

use App\Models\Student;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protect student routes using a session-based guard.
 */
class EnsureStudentAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $studentId = $request->session()->get('student_id');

        if (! $studentId) {
            return redirect()->route('student.login');
        }

        $student = Student::find($studentId);

        if (! $student) {
            $request->session()->forget('student_id');

            return redirect()->route('student.login');
        }

        if ($student->isDisabled()) {
            $request->session()->forget('student_id');

            return redirect()
                ->route('student.login')
                ->withErrors(['phone' => 'الحساب معطل من قبل الإدارة. يرجى التواصل مع الدعم لمراجعة الحالة.']);
        }

        $request->attributes->set('authenticatedStudent', $student);

        return $next($request);
    }
}
