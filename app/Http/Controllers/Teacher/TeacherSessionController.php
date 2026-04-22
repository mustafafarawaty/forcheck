<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\CancelTeacherSessionRequest;
use App\Services\Teacher\TeacherSessionService;
use App\Traits\ResolvesTeacherAuthentication;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Handles teacher sessions and cancellation.
 */
class TeacherSessionController extends Controller
{
    use ResolvesTeacherAuthentication;

    public function __construct(
        private readonly TeacherSessionService $sessionService,
    ) {
    }

    /**
     * Show sessions page.
     */
    public function index(Request $request): View
    {
        $teacher = $this->authenticatedTeacher($request);
        $sessions = $this->sessionService->list($teacher);
        $selectedSessionId = (int) ($request->query('selected_session_id') ?: old('selected_session_id', 0));
        $selectedSession = $sessions->firstWhere('id', $selectedSessionId) ?: $sessions->first();

        return view('teacher.pages.sessions.index', [
            'sessions' => $sessions,
            'selectedSession' => $selectedSession,
        ]);
    }

    /**
     * Cancel an upcoming session.
     */
    public function cancel(CancelTeacherSessionRequest $request, int $sessionId): RedirectResponse
    {
        $teacher = $this->authenticatedTeacher($request);
        $this->sessionService->cancel($teacher, $sessionId, $request->validated('cancellation_reason'));

        return back()->with('status', 'تم إلغاء الجلسة.');
    }
}
