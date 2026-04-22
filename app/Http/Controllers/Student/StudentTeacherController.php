<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreStudentBookingRequest;
use App\Services\Student\StudentBookingService;
use App\Services\Student\StudentDirectoryService;
use App\Services\Teacher\TeacherAvailabilityService;
use App\Traits\ResolvesStudentAuthentication;
use Illuminate\Contracts\View\View;
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
    public function storeBooking(StoreStudentBookingRequest $request, int $teacherId): RedirectResponse
    {
        $student = $this->authenticatedStudent($request);
        $payload = $request->validated();
        $payload['teacher_id'] = $teacherId;
        $result = $this->bookingService->book($student, $payload);

        $message = $result['mode'] === 'instant'
            ? 'تم إرسال طلب مباشر للأستاذ المحدد.'
            : 'تم حجز الموعد مع الأستاذ المحدد.';

        return back()->with('status', $message);
    }
}
