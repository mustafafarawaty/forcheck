<?php

namespace App\Http\Middleware;

use App\Models\Student;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

        try {
            $student = Student::find($studentId);
        } catch (\Throwable $e) {
            Log::error('EnsureStudentAuthenticated: database query failed', [
                'student_id' => $studentId,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('student.login');
        }

        if (! $student) {
            $request->session()->forget('student_id');

            return redirect()->route('student.login');
        }

        $request->attributes->set('authenticatedStudent', $student);

        return $next($request);
    }
}
