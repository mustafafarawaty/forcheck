<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreStudentBookingRequest;
use App\Services\LiveSession\LiveSessionRoomService;
use App\Services\Student\StudentBookingService;
use App\Services\Student\StudentDirectoryService;
use App\Services\Teacher\TeacherAvailabilityService;
use App\Traits\ResolvesStudentAuthentication;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Student-facing teacher directory.
 */
class StudentTeacherController extends Controller
{
    use ResolvesStudentAuthentication;

    public function __construct(
        private readonly StudentDirectoryService $directoryService,
        private readonly StudentBookingService $bookingService,
        private readonly TeacherAvailabilityService $teacherAvailabilityService,
        private readonly LiveSessionRoomService $roomService,
    ) {
    }

    /**
     * Show teacher cards.
     */
    public function index(Request $request): View
    {
        $student = $this->authenticatedStudent($request);

        return view('student.pages.teachers.index', [
            'teachers' => $this->directoryService->listTeachers($student),
            'subjectOptions' => $this->directoryService->availableSubjectNames($student),
        ]);
    }

    /**
     * Show teacher details page.
     */
    public function show(Request $request, int $teacherId): View
    {
        $student = $this->authenticatedStudent($request);
        $teacherProfile = $this->directoryService->findTeacherForStudent($student, $teacherId);

        return view('student.pages.teachers.show', [
            'teacherProfile' => $teacherProfile,
            'subjectOptions' => $this->directoryService->availableSubjectNames($student),
            'availableSlots' => $this->directoryService->availableSlotsForTeacher($teacherProfile),
            'availabilityWindows' => $this->teacherAvailabilityService->summarizedWindows($teacherProfile),
            'durationOptions' => $this->directoryService->durationOptions(),
        ]);
    }

    /**
     * Create a booking from teacher details page.
     */
    public function storeBooking(StoreStudentBookingRequest $request, int $teacherId): RedirectResponse|JsonResponse
    {
        $student = $this->authenticatedStudent($request);
        $payload = $request->validated();
        $payload['teacher_id'] = $teacherId;
        $result = $this->bookingService->book($student, $payload);

        $message = $result['mode'] === 'instant'
            ? 'تم إرسال طلب الجلسة المباشرة بنجاح.'
            : 'تم حجز الموعد مع الأستاذ بنجاح.';

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
}
