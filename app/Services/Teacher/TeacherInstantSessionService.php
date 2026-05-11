<?php

namespace App\Services\Teacher;

use App\Models\Teacher;
use App\Models\TeacherLiveRequest;
use App\Repositories\TeacherLiveRequestRepository;
use App\Services\Realtime\RealtimeUpdateService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Manages instant session availability and incoming requests.
 */
class TeacherInstantSessionService
{
    public function __construct(
        private readonly TeacherLiveRequestRepository $liveRequests,
        private readonly RealtimeUpdateService $realtime,
    ) {
    }

    /**
     * Toggle teacher instant booking state.
     */
    public function toggle(Teacher $teacher, bool $isEnabled): Teacher
    {
        $teacher->update([
            'is_accepting_instant_sessions' => $isEnabled,
        ]);

        $updatedTeacher = $teacher->fresh();
        $this->realtime->broadcastTeacherDashboard($updatedTeacher);

        return $updatedTeacher;
    }

    /**
     * Pending instant session requests for teacher.
     *
     * @return Collection<int, TeacherLiveRequest>
     */
    public function pending(Teacher $teacher): Collection
    {
        return $this->liveRequests->pendingForTeacher($teacher);
    }

    /**
     * Accept an instant request and create a session.
     */
    public function accept(Teacher $teacher, int $requestId)
    {
        [$session, $request] = DB::transaction(function () use ($teacher, $requestId) {
            $request = $this->liveRequests->ownedPendingOrFail($teacher, $requestId);

            $session = $teacher->sessions()->create([
                'teacher_subject_id' => $request->teacher_subject_id,
                'student_id' => $request->student_id,
                'student_name' => $request->student->name,
                'scheduled_at' => now(),
                'started_at' => null,
                'status' => 'upcoming',
                'booking_type' => 'instant',
                'teacher_confirmed_at' => now(),
                'student_confirmed_at' => now(),
                'price' => $request->subject->hourly_rate_syp,
                'notes' => $request->note ?: 'جلسة مباشرة تم قبولها فورًا.',
            ]);

            $request->update([
                'status' => 'accepted',
                'teacher_session_id' => $session->id,
                'responded_at' => now(),
            ]);

            return [$session, $request->fresh(['teacher', 'student', 'subject', 'session'])];
        });

        $this->realtime->broadcastTeacherDashboard($teacher, $session);

        if ($request->student) {
            $this->realtime->broadcastStudentDashboard($request->student, $session, $request);
        }

        return $session;
    }

    /**
     * Reject an instant request.
     */
    public function reject(Teacher $teacher, int $requestId): TeacherLiveRequest
    {
        $request = $this->liveRequests->ownedPendingOrFail($teacher, $requestId);

        $request->update([
            'status' => 'rejected',
            'responded_at' => now(),
        ]);

        $updatedRequest = $request->fresh(['teacher', 'student', 'subject']);
        $this->realtime->broadcastTeacherDashboard($teacher);

        if ($updatedRequest->student) {
            $this->realtime->broadcastStudentDashboard($updatedRequest->student, liveRequest: $updatedRequest);
        }

        return $updatedRequest;
    }

    /**
     * Confirm attendance for a teacher session.
     */
    public function confirmAttendance(Teacher $teacher, int $sessionId)
    {
        $session = $teacher->sessions()->findOrFail($sessionId);

        if ($session->status !== 'upcoming') {
            throw ValidationException::withMessages([
                'session' => 'يمكن تأكيد حضور الجلسات القادمة فقط.',
            ]);
        }

        $session->update([
            'teacher_confirmed_at' => now(),
        ]);

        $updatedSession = $session->fresh(['teacher', 'student', 'subject', 'complaints', 'files']);
        $this->realtime->broadcastSessionParticipants($updatedSession);

        return $updatedSession;
    }
}
