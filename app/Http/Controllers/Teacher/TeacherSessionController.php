<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\TeacherSession;
use App\Http\Requests\Teacher\CancelTeacherSessionRequest;
use App\Services\Realtime\RealtimeUpdateService;
use App\Services\Teacher\TeacherSessionService;
use App\Traits\ResolvesTeacherAuthentication;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Handles teacher sessions and cancellation.
 */
class TeacherSessionController extends Controller
{
    use ResolvesTeacherAuthentication;

    public function __construct(
        private readonly TeacherSessionService $sessionService,
        private readonly RealtimeUpdateService $realtime,
    ) {
    }

    /**
     * Show sessions page.
     */
    public function index(Request $request): View
    {
        $teacher = $this->authenticatedTeacher($request);
        $sessions = $this->sessionService->list($teacher);

        if (Schema::hasColumn('teacher_sessions', 'teacher_read_at')) {
            foreach ($sessions as $session) {
                if (is_null($session->teacher_read_at)) {
                    $session->forceFill(['teacher_read_at' => now()])->save();
                }
            }
        }

        return view('teacher.pages.sessions.index', [
            'sessions' => $sessions,
        ]);
    }

    /**
     * Show a single teacher session details page.
     */
    public function show(Request $request, TeacherSession $session): View
    {
        $teacher = $this->authenticatedTeacher($request);
        $session = $this->sessionService->findOwned($teacher, $session->id);

        abort_unless($session->teacher_id === $teacher->id, 403);

        return view('teacher.pages.sessions.show', [
            'session' => $session,
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

    public function complaint(Request $request, TeacherSession $session): RedirectResponse
    {
        $teacher = $this->authenticatedTeacher($request);

        abort_unless($session->teacher_id === $teacher->id, 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'attachment' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($request->hasFile('attachment')) {
            $validated['attachment_path'] = $request->file('attachment')->store("complaints/sessions/{$session->id}", 'public');
        }

        unset($validated['attachment']);

        $session->complaints()->create([
            ...$validated,
            'teacher_id' => $teacher->id,
            'student_id' => $session->student_id,
            'submitted_by' => 'teacher',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $this->realtime->broadcastSessionParticipants($session);

        return back()->with('status', 'تم إرسال الشكوى بانتظار مراجعة الإدارة.');
    }
}

