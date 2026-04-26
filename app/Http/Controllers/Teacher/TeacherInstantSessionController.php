<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Services\Teacher\TeacherInstantSessionService;
use App\Traits\ResolvesTeacherAuthentication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Handles teacher instant session availability and request actions.
 */
class TeacherInstantSessionController extends Controller
{
    use ResolvesTeacherAuthentication;

    public function __construct(
        private readonly TeacherInstantSessionService $instantSessionService,
    ) {
    }

    /**
     * Toggle live instant session availability.
     */
    public function toggle(Request $request): RedirectResponse
    {
        $teacher = $this->authenticatedTeacher($request);

        $this->instantSessionService->toggle(
            $teacher,
            $request->boolean('is_accepting_instant_sessions')
        );

        return back()->with('status', 'تم تحديث حالة الجلسات المباشرة.');
    }

    /**
     * Accept pending live request.
     */
    public function accept(Request $request, int $liveRequestId): RedirectResponse
    {
        $teacher = $this->authenticatedTeacher($request);
        $session = $this->instantSessionService->accept($teacher, $liveRequestId);

        return redirect()->route('teacher.sessions.room.show', ['sessionId' => $session->id, 'autojoin' => 1]);
    }

    /**
     * Reject pending live request.
     */
    public function reject(Request $request, int $liveRequestId): RedirectResponse
    {
        $teacher = $this->authenticatedTeacher($request);
        $this->instantSessionService->reject($teacher, $liveRequestId);

        return back()->with('status', 'تم رفض طلب الجلسة المباشرة.');
    }

    /**
     * Confirm teacher attendance.
     */
    public function confirm(Request $request, int $sessionId): RedirectResponse
    {
        $teacher = $this->authenticatedTeacher($request);
        $this->instantSessionService->confirmAttendance($teacher, $sessionId);

        return back()->with('status', 'تم تأكيد حضور الجلسة.');
    }

    /**
     * Poll pending live requests for lightweight real-time updates.
     */
    public function poll(Request $request): JsonResponse
    {
        $teacher = $this->authenticatedTeacher($request);
        $requests = $this->instantSessionService->pending($teacher);

        return response()->json([
            'count' => $requests->count(),
            'requests' => $requests->map(fn ($liveRequest) => [
                'id' => $liveRequest->id,
                'student_name' => $liveRequest->student->name,
                'subject_name' => $liveRequest->subject->name,
                'requested_at' => $liveRequest->requested_at?->diffForHumans(),
                'accept_url' => route('teacher.instant.accept', $liveRequest->id),
                'reject_url' => route('teacher.instant.reject', $liveRequest->id),
            ])->values(),
        ]);
    }
}
