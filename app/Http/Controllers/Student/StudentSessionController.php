<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\CancelStudentSessionRequest;
use App\Http\Requests\Student\StoreStudentBookingRequest;
use App\Models\TeacherSession;
use App\Services\LiveSession\LiveSessionRoomService;
use App\Services\Realtime\RealtimeUpdateService;
use App\Services\Student\StudentBookingService;
use App\Services\Student\StudentDirectoryService;
use App\Services\Student\StudentSessionService;
use App\Traits\ResolvesStudentAuthentication;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Handles student sessions and bookings.
 */
class StudentSessionController extends Controller
{
    use ResolvesStudentAuthentication;

    public function __construct(
        private readonly StudentSessionService $sessionService,
        private readonly StudentBookingService $bookingService,
        private readonly StudentDirectoryService $directoryService,
        private readonly LiveSessionRoomService $roomService,
        private readonly RealtimeUpdateService $realtime,
    ) {
    }

    /**
     * Show student sessions page.
     */
    public function index(Request $request): View
    {
        $student = $this->authenticatedStudent($request);
        $sessions = $this->sessionService->list($student);

        return view('student.pages.sessions.index', [
            'sessions' => $sessions,
            'selectedSession' => $sessions->first(),
            'subjectOptions' => $this->directoryService->availableSubjectNames($student),
            'dayOptions' => $this->directoryService->dayLabels(),
            'hourOptions' => $this->directoryService->hourOptions(),
            'durationOptions' => $this->directoryService->durationOptions(),
        ]);
    }

    /**
     * Show a single student session details page.
     */
    public function show(Request $request, TeacherSession $session): View
    {
        $student = $this->authenticatedStudent($request);

        abort_unless($session->student_id === $student->id, 403);

        $session->load(['teacher', 'subject', 'complaints', 'files', 'walletTransactions']);

        return view('student.pages.sessions.show', [
            'session' => $session,
        ]);
    }

    /**
     * Store a new booking request.
     */
    public function storeBooking(StoreStudentBookingRequest $request): RedirectResponse|JsonResponse
    {
        $student = $this->authenticatedStudent($request);
        $result = $this->bookingService->book($student, $request->validated());

        $message = $result['mode'] === 'instant'
            ? 'ØªÙ… Ø¥Ø±Ø³Ø§Ù„ Ø·Ù„Ø¨ Ø§Ù„Ø¬Ù„Ø³Ø© Ø§Ù„Ù…Ø¨Ø§Ø´Ø±Ø© Ø¨Ù†Ø¬Ø§Ø­.'
            : 'ØªÙ… Ø­Ø¬Ø² Ø§Ù„Ù…ÙˆØ¹Ø¯ Ø¨Ù†Ø¬Ø§Ø­.';

        $session = $result['session'];
        $redirectUrl = $result['mode'] === 'scheduled' && $session ? route('student.sessions.show', $session->id) : null;
        $activeSession = $this->roomService->joinCandidateForStudent($student);
        $popupPayload = $activeSession ? $this->roomService->quickJoinPayload($activeSession, 'student') : null;

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'ok',
                'message' => $message,
                'popup' => $popupPayload,
                'redirect_url' => $redirectUrl,
            ]);
        }

        if ($redirectUrl) {
            return redirect($redirectUrl)->with('status', $message);
        }

        if ($popupPayload) {
            return back()->with('status', $message)->with('studentBookingPopup', $popupPayload);
        }

        return back()->with('status', $message);
    }

    /**
     * Preview a booking match and cost before the student confirms.
     */
    public function previewBooking(StoreStudentBookingRequest $request): JsonResponse
    {
        $student = $this->authenticatedStudent($request);
        $preview = $this->bookingService->preview($student, $request->validated());

        return response()->json([
            'mode' => $preview['mode'],
            'teacher_id' => $preview['teacher']->id,
            'teacher_name' => $preview['teacher']->name,
            'subject_name' => $preview['subject']->name,
            'hourly_rate' => $preview['hourly_rate'],
            'duration_hours' => $preview['duration_hours'],
            'total' => $preview['total'],
            'balance' => $preview['balance'],
            'can_afford' => $preview['can_afford'],
        ]);
    }

    /**
     * Confirm attendance for an upcoming session.
     */
    public function confirm(Request $request, int $sessionId): RedirectResponse
    {
        $student = $this->authenticatedStudent($request);
        $this->sessionService->confirmAttendance($student, $sessionId);

        return back()->with('status', 'ØªÙ… ØªØ£ÙƒÙŠØ¯ Ø­Ø¶ÙˆØ±Ùƒ Ù„Ù„Ø¬Ù„Ø³Ø©.');
    }

    /**
     * Cancel an upcoming session.
     */
    public function cancel(CancelStudentSessionRequest $request, int $sessionId): RedirectResponse
    {
        $student = $this->authenticatedStudent($request);
        $this->sessionService->cancel($student, $sessionId, $request->validated('cancellation_reason'));

        return back()->with('status', 'ØªÙ… Ø¥Ù„ØºØ§Ø¡ Ø§Ù„Ø¬Ù„Ø³Ø© Ø¨Ù†Ø¬Ø§Ø­.');
    }

    public function complaint(Request $request, TeacherSession $session): RedirectResponse
    {
        $student = $this->authenticatedStudent($request);

        abort_unless($session->student_id === $student->id, 403);

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
            'teacher_id' => $session->teacher_id,
            'student_id' => $student->id,
            'submitted_by' => 'student',
            'status' => 'pending',
            'submitted_at' => now(),
            'student_read_at' => now(),
        ]);

        $this->realtime->broadcastSessionParticipants($session);

        return back()->with('status', 'ØªÙ… Ø¥Ø±Ø³Ø§Ù„ Ø§Ù„Ø´ÙƒÙˆÙ‰ Ø¨Ø§Ù†ØªØ¸Ø§Ø± Ù…Ø±Ø§Ø¬Ø¹Ø© Ø§Ù„Ø¥Ø¯Ø§Ø±Ø©.');
    }

    /**
     * Poll quick-join state for the authenticated student.
     */
    public function poll(Request $request): JsonResponse
    {
        $student = $this->authenticatedStudent($request);
        $activeSession = $this->roomService->joinCandidateForStudent($student);

        return response()->json([
            'active_session_payload' => $activeSession
                ? $this->roomService->quickJoinPayload($activeSession, 'student')
                : null,
        ]);
    }
}




