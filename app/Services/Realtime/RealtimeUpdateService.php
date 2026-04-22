<?php

namespace App\Services\Realtime;

use App\Events\Realtime\BroadcastPayloadEvent;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherLiveRequest;
use App\Models\TeacherSession;
use App\Services\LiveSession\LiveSessionRoomService;

/**
 * Dispatches realtime dashboard and room updates through Reverb.
 */
class RealtimeUpdateService
{
    public function __construct(
        private readonly LiveSessionRoomService $roomService,
        private readonly RealtimeChannelService $channels,
    ) {
    }

    /**
     * Push the latest teacher dashboard state.
     */
    public function broadcastTeacherDashboard(Teacher $teacher, ?TeacherSession $session = null): void
    {
        $teacher->loadMissing('liveRequests.student', 'liveRequests.subject');

        event(new BroadcastPayloadEvent(
            [$this->channels->teacherDashboardChannel($teacher)],
            'teacher.realtime.updated',
            [
                'active_session_payload' => $this->joinCandidatePayloadForTeacher($teacher),
                'live_requests' => $this->teacherLiveRequestsPayload($teacher),
                'session_update' => $session && $session->teacher_id === $teacher->id
                    ? $this->teacherSessionPayload($session->fresh(['subject', 'complaints', 'student', 'files']))
                    : null,
            ],
        ));
    }

    /**
     * Push the latest student dashboard state.
     */
    public function broadcastStudentDashboard(Student $student, ?TeacherSession $session = null, ?TeacherLiveRequest $liveRequest = null): void
    {
        event(new BroadcastPayloadEvent(
            [$this->channels->studentDashboardChannel($student)],
            'student.realtime.updated',
            [
                'active_session_payload' => $this->joinCandidatePayloadForStudent($student),
                'session_update' => $session && $session->student_id === $student->id
                    ? $this->studentSessionPayload($session->fresh(['teacher', 'subject', 'complaints', 'files']))
                    : null,
                'live_request_update' => $liveRequest ? $this->studentLiveRequestPayload($liveRequest->fresh(['teacher', 'subject', 'session'])) : null,
            ],
        ));
    }

    /**
     * Push room updates to both participants.
     *
     * @param  array<string, mixed>  $payload
     */
    public function broadcastRoomEvent(TeacherSession $session, string $type, array $payload = []): void
    {
        event(new BroadcastPayloadEvent(
            [$this->channels->liveSessionChannel($session)],
            'live-session.event',
            [
                'type' => $type,
                'payload' => $payload,
                'session' => $this->sharedRoomSessionPayload($session->fresh(['teacher', 'student', 'subject'])),
            ],
        ));
    }

    /**
     * Refresh both participants after a session change.
     */
    public function broadcastSessionParticipants(TeacherSession $session, ?TeacherLiveRequest $liveRequest = null): void
    {
        $session->loadMissing(['teacher', 'student', 'subject', 'complaints', 'files']);

        if ($session->teacher) {
            $this->broadcastTeacherDashboard($session->teacher, $session);
        }

        if ($session->student) {
            $this->broadcastStudentDashboard($session->student, $session, $liveRequest);
        }
    }

    /**
     * Build the teacher list/detail payload used by realtime updates.
     *
     * @return array<string, mixed>
     */
    public function teacherSessionPayload(TeacherSession $session): array
    {
        $quickPayload = $this->roomService->quickJoinPayload($session, 'teacher');

        return [
            'id' => $session->id,
            'student_name' => $session->student_name,
            'subject_name' => $session->subject?->name ?? 'بدون مادة',
            'status' => $session->status,
            'scheduled_at' => $this->formatDateTime($session->scheduled_at),
            'scheduled_at_iso' => $session->scheduled_at?->toIso8601String(),
            'started_at_iso' => $session->started_at?->toIso8601String(),
            'duration_hours' => (int) ($session->duration_hours ?: 1),
            'ended_at' => $this->formatDateTime($session->ended_at),
            'notes' => $session->notes,
            'recording_url' => $session->recording_public_url,
            'chat_excerpt' => $session->chat_excerpt,
            'cancellation_reason' => $session->cancellation_reason,
            'teacher_confirmed' => (bool) $session->teacher_confirmed_at,
            'student_confirmed' => (bool) $session->student_confirmed_at,
            'complaints' => $session->complaints->map(fn ($complaint) => [
                'title' => $complaint->title,
                'status' => $complaint->status,
                'submitted_at' => $this->formatDateTime($complaint->submitted_at),
            ])->values()->all(),
            'can_cancel' => $session->status === 'upcoming',
            'cancel_url' => route('teacher.sessions.cancel', $session->id),
            'can_confirm' => $session->status === 'upcoming' && ! $session->teacher_confirmed_at,
            'confirm_url' => route('teacher.sessions.confirm', $session->id),
            'can_join_now' => $quickPayload['can_join_now'],
            'join_url' => $quickPayload['join_url'],
            'student_summary_notes' => $session->student_summary_notes,
            'recording_note' => $this->recordingNote($session),
            'files' => $session->files->map(fn ($file) => [
                'name' => $file->original_name,
                'url' => $file->file_url,
            ])->values()->all(),
        ];
    }

    /**
     * Build the student list/detail payload used by realtime updates.
     *
     * @return array<string, mixed>
     */
    public function studentSessionPayload(TeacherSession $session): array
    {
        $quickPayload = $this->roomService->quickJoinPayload($session, 'student');

        return [
            'id' => $session->id,
            'teacher_name' => $session->teacher?->name ?? 'أستاذ',
            'subject_name' => $session->subject?->name ?? 'بدون مادة',
            'status' => $session->status,
            'scheduled_at' => $this->formatDateTime($session->scheduled_at),
            'scheduled_at_iso' => $session->scheduled_at?->toIso8601String(),
            'started_at_iso' => $session->started_at?->toIso8601String(),
            'duration_hours' => (int) ($session->duration_hours ?: 1),
            'notes' => $session->notes,
            'recording_url' => $session->recording_public_url,
            'chat_excerpt' => $session->chat_excerpt,
            'cancellation_reason' => $session->cancellation_reason,
            'student_confirmed' => (bool) $session->student_confirmed_at,
            'teacher_confirmed' => (bool) $session->teacher_confirmed_at,
            'confirm_url' => route('student.sessions.confirm', $session->id),
            'can_confirm' => $session->status === 'upcoming' && ! $session->student_confirmed_at,
            'cancel_url' => route('student.sessions.cancel', $session->id),
            'can_cancel' => $session->status === 'upcoming',
            'can_join_now' => $quickPayload['can_join_now'],
            'join_url' => $quickPayload['join_url'],
            'student_summary_notes' => $session->status === 'completed' ? $session->student_summary_notes : null,
            'recording_note' => $this->recordingNote($session),
            'files' => $session->files->map(fn ($file) => [
                'name' => $file->original_name,
                'url' => $file->file_url,
            ])->values()->all(),
            'complaints' => $session->complaints->map(fn ($complaint) => [
                'title' => $complaint->title,
                'status' => $complaint->status,
                'submitted_at' => $this->formatDateTime($complaint->submitted_at),
            ])->values()->all(),
        ];
    }

    /**
     * Shared live room session state that can safely be broadcast to both sides.
     *
     * @return array<string, mixed>
     */
    private function sharedRoomSessionPayload(TeacherSession $session): array
    {
        return [
            'id' => $session->id,
            'status' => $session->status,
            'scheduled_at' => $session->scheduled_at?->toIso8601String(),
            'started_at' => $session->started_at?->toIso8601String(),
            'ended_at' => $session->ended_at?->toIso8601String(),
            'planned_end_at' => $session->started_at
                ? $session->started_at->copy()->addHours((int) ($session->duration_hours ?: 1))->toIso8601String()
                : null,
            'join_deadline_at' => $session->join_deadline_at?->toIso8601String(),
            'teacher_joined_at' => $session->teacher_joined_at?->toIso8601String(),
            'student_joined_at' => $session->student_joined_at?->toIso8601String(),
            'recording_url' => $session->recording_public_url,
            'recording_expires_at' => $session->recording_expires_at?->toIso8601String(),
            'recording_note' => $this->recordingNote($session),
        ];
    }

    /**
     * Nearest teacher join candidate, whether active or just about to start.
     *
     * @return array<string, mixed>|null
     */
    private function joinCandidatePayloadForTeacher(Teacher $teacher): ?array
    {
        $candidate = $this->roomService->joinCandidateForTeacher($teacher);

        return $candidate ? $this->roomService->quickJoinPayload($candidate, 'teacher') : null;
    }

    /**
     * Nearest student join candidate, whether active or just about to start.
     *
     * @return array<string, mixed>|null
     */
    private function joinCandidatePayloadForStudent(Student $student): ?array
    {
        $candidate = $this->roomService->joinCandidateForStudent($student);

        return $candidate ? $this->roomService->quickJoinPayload($candidate, 'student') : null;
    }

    /**
     * Teacher direct requests payload.
     *
     * @return array<string, mixed>
     */
    private function teacherLiveRequestsPayload(Teacher $teacher): array
    {
        $requests = $teacher->liveRequests()
            ->with(['student', 'subject'])
            ->where('status', 'pending')
            ->latest('requested_at')
            ->get();

        return [
            'count' => $requests->count(),
            'requests' => $requests->map(fn (TeacherLiveRequest $liveRequest) => [
                'id' => $liveRequest->id,
                'student_name' => $liveRequest->student?->name ?? 'طالب',
                'subject_name' => $liveRequest->subject?->name ?? 'جلسة',
                'requested_at' => $liveRequest->requested_at?->diffForHumans(),
                'accept_url' => route('teacher.instant.accept', $liveRequest->id),
                'reject_url' => route('teacher.instant.reject', $liveRequest->id),
            ])->values()->all(),
        ];
    }

    /**
     * Student-side live request state payload.
     *
     * @return array<string, mixed>
     */
    private function studentLiveRequestPayload(TeacherLiveRequest $liveRequest): array
    {
        return [
            'id' => $liveRequest->id,
            'status' => $liveRequest->status,
            'teacher_name' => $liveRequest->teacher?->name ?? 'أستاذ',
            'subject_name' => $liveRequest->subject?->name ?? 'جلسة',
            'session_id' => $liveRequest->teacher_session_id,
        ];
    }

    /**
     * Local display formatting using the application timezone.
     */
    private function formatDateTime($value): ?string
    {
        return $value?->copy()->timezone(config('app.timezone'))->format('Y-m-d H:i');
    }

    /**
     * Recording expiry message.
     */
    private function recordingNote(TeacherSession $session): ?string
    {
        if (! $session->recording_expires_at) {
            return null;
        }

        return $session->recording_url
            ? 'سيتم حذف تسجيل هذه الجلسة تلقائيًا بعد 3 ساعات من انتهائها.'
            : 'تم حذف تسجيل هذه الجلسة تلقائيًا بعد مرور 3 ساعات على انتهائها.';
    }
}
