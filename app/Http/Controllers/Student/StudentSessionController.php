<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreStudentBookingRequest;
use App\Services\Student\StudentBookingService;
use App\Services\Student\StudentDirectoryService;
use App\Services\Student\StudentSessionService;
use App\Traits\ResolvesStudentAuthentication;
use Illuminate\Contracts\View\View;
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
     * Store a new booking request.
     */
    public function storeBooking(StoreStudentBookingRequest $request): RedirectResponse
    {
        $student = $this->authenticatedStudent($request);
        $result = $this->bookingService->book($student, $request->validated());

        $message = $result['mode'] === 'instant'
            ? 'تم إرسال طلب جلسة مباشرة للأستاذ.'
            : 'تم حجز الموعد بنجاح.';

        return back()->with('status', $message);
    }

    /**
     * Confirm attendance for an upcoming session.
     */
    public function confirm(Request $request, int $sessionId): RedirectResponse
    {
        $student = $this->authenticatedStudent($request);
        $this->sessionService->confirmAttendance($student, $sessionId);

        return back()->with('status', 'تم تأكيد حضورك للجلسة.');
    }
    /**
     * Cancel an upcoming session.
     */
    public function cancel(Request $request, int $sessionId): RedirectResponse
    {
        $student = $this->authenticatedStudent($request);
        $this->sessionService->cancel($student, $sessionId);

        return back()->with('status', 'تم إلغاء الجلسة بنجاح.');
    }
}
