<?php

namespace App\Http\Middleware;

use App\Models\Teacher;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protect teacher routes using a lightweight session-based guard.
 */
class EnsureTeacherAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $teacherId = $request->session()->get('teacher_id');

        if (! $teacherId) {
            return redirect()->route('teacher.login');
        }

        $teacher = Teacher::find($teacherId);

        if (! $teacher) {
            $request->session()->forget('teacher_id');

            return redirect()->route('teacher.login');
        }

        $request->attributes->set('authenticatedTeacher', $teacher);

        return $next($request);
    }
}
