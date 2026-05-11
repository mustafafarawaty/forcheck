<?php

namespace App\Services\LiveSession;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherComplaint;
use App\Models\TeacherSession;
use App\Models\TeacherSessionFile;
use App\Models\TeacherSessionMessage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Handles room lifecycle, signaling, chat and session media persistence.
 */
class LiveSessionRoomService
{
    /**
     * Find the session that should surface as a quick join entry for a teacher.
     */
    public function activeSessionForTeacher(Teacher $teacher): ?TeacherSession
    {
        return $this->activeSession(
            $teacher->sessions()
                ->with(['student', 'subject'])
                ->where(function ($query): void {
                    $query->whereIn('status', ['upcoming', 'in_progress'])
                        ->orWhereNotNull('recording_expires_at');
                })
                ->orderBy('scheduled_at')
                ->get()
        );
    }

    /**
     * Find the nearest confirmed teacher session, even if it has not started yet.
     */
    public function joinCandidateForTeacher(Teacher $teacher): ?TeacherSession
    {
        return $this->joinCandidate(
            $teacher->sessions()
                ->with(['student', 'subject'])
                ->whereIn('status', ['upcoming', 'in_progress'])
                ->orderBy('scheduled_at')
                ->get()
        );
    }

    /**
     * Find the session that should surface as a quick join entry for a student.
     */
    public function activeSessionForStudent(Student $student): ?TeacherSession
    {
        return $this->activeSession(
            $student->sessions()
                ->with(['teacher', 'subject'])
                ->where(function ($query): void {
                    $query->whereIn('status', ['upcoming', 'in_progress'])
                        ->orWhereNotNull('recording_expires_at');
                })
                ->orderBy('scheduled_at')
                ->get()
        );
    }

    /**
     * Find the nearest confirmed student session, even if it has not started yet.
     */
    public function joinCandidateForStudent(Student $student): ?TeacherSession
    {
        return $this->joinCandidate(
            $student->sessions()
                ->with(['teacher', 'subject'])
                ->whereIn('status', ['upcoming', 'in_progress'])
                ->orderBy('scheduled_at')
                ->get()
        );
    }

    /**
     * Sync relevant sessions owned by a teacher.
     */
    public function syncOwnedTeacherSessions(Teacher $teacher): void
    {
        $teacher->sessions()
            ->where(function ($query): void {
                $query->whereIn('status', ['upcoming', 'in_progress'])
                    ->orWhereNotNull('recording_expires_at');
            })
            ->get()
            ->each(fn (TeacherSession $session) => $this->syncSession($session));
    }

    /**
     * Sync relevant sessions owned by a student.
     */
    public function syncOwnedStudentSessions(Student $student): void
    {
        $student->sessions()
            ->where(function ($query): void {
                $query->whereIn('status', ['upcoming', 'in_progress'])
                    ->orWhereNotNull('recording_expires_at');
            })
            ->get()
            ->each(fn (TeacherSession $session) => $this->syncSession($session));
    }

    /**
     * Resolve a teacher-owned room session.
     */
    public function resolveForTeacher(Teacher $teacher, int $sessionId): TeacherSession
    {
        $session = $teacher->sessions()
            ->with($this->roomRelations())
            ->findOrFail($sessionId);

        return $this->syncSession($session)->load($this->roomRelations());
    }

    /**
     * Resolve a student-owned room session.
     */
    public function resolveForStudent(Student $student, int $sessionId): TeacherSession
    {
        $session = $student->sessions()
            ->with($this->roomRelations())
            ->findOrFail($sessionId);

        return $this->syncSession($session)->load($this->roomRelations());
    }

    /**
     * Join a session room and mark the participant as present.
     */
    public function join(TeacherSession $session, string $role): TeacherSession
    {
        $session = $this->syncSession($session);

        if (! $this->canJoinNow($session)) {
            throw ValidationException::withMessages([
                'session' => 'لا يمكن الانضمام إلى هذه الجلسة الآن.',
            ]);
        }

        $updates = [];
        $joinColumn = $role === 'teacher' ? 'teacher_joined_at' : 'student_joined_at';

        if (! $session->{$joinColumn}) {
            $updates[$joinColumn] = now();
        }

        $teacherJoinedAt = $role === 'teacher' ? now() : $session->teacher_joined_at;
        $studentJoinedAt = $role === 'student' ? now() : $session->student_joined_at;

        if ($teacherJoinedAt && $studentJoinedAt && $session->status === 'upcoming') {
            $updates['status'] = 'in_progress';
            $updates['started_at'] = $session->started_at ?? now();
        }

        if ($updates !== []) {
            $session->update($updates);
            $session = $this->freshRoomSession($session);
        }

        return $session;
    }

    /**
     * Store a room chat message and refresh the session excerpt.
     */
    public function storeMessage(TeacherSession $session, string $role, string $message): TeacherSessionMessage
    {
        $this->ensureRoomIsOpen($session);

        $record = $session->messages()->create([
            'sender_role' => $role,
            'sender_name' => $this->actorDisplayName($session, $role),
            'message' => $message,
        ]);

        $this->refreshChatExcerpt($session->fresh());

        return $record;
    }

    /**
     * Update teacher-only notes and student summary notes.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveTeacherNotes(TeacherSession $session, array $data): TeacherSession
    {
        $this->ensureRoomIsOpen($session, allowCompleted: true);

        $session->update([
            'teacher_private_notes' => $data['teacher_private_notes'] ?? $session->teacher_private_notes,
            'student_summary_notes' => $data['student_summary_notes'] ?? $session->student_summary_notes,
        ]);

        return $this->freshRoomSession($session);
    }

    /**
     * Persist an uploaded room file.
     */
    public function uploadFile(TeacherSession $session, string $role, UploadedFile $file): TeacherSessionFile
    {
        $this->ensureRoomIsOpen($session, allowCompleted: true);

        $path = $file->store("session-files/{$session->id}", 'public');

        return $session->files()->create([
            'uploader_role' => $role,
            'uploader_name' => $this->actorDisplayName($session, $role),
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize() ?: 0,
        ]);
    }

    /**
     * Persist a complaint raised during the session.
     *
     * @param  array<string, string>  $data
     */
    public function storeComplaint(TeacherSession $session, string $role, array $data): TeacherComplaint
    {
        $this->ensureRoomIsOpen($session, allowCompleted: true);

        return $session->complaints()->create([
            'teacher_id' => $session->teacher_id,
            'student_id' => $session->student_id,
            'title' => $data['title'],
            'description' => $data['description'],
            'submitted_by' => $role,
            'status' => 'pending',
            'submitted_at' => now(),
        ]);
    }

    /**
     * Persist the uploaded automatic recording.
     */
    public function uploadRecording(TeacherSession $session, UploadedFile $file): TeacherSession
    {
        $this->syncSession($session);

        if ($session->recording_url) {
            return $this->freshRoomSession($session);
        }

        $path = $file->store("session-recordings/{$session->id}", 'public');
        $expiresAt = ($session->ended_at ?? now())->copy()->addHours(3);

        $session->update([
            'recording_url' => $path,
            'recording_expires_at' => $expiresAt,
        ]);

        return $this->freshRoomSession($session);
    }

    /**
     * End a running session.
     */
    public function endSession(TeacherSession $session, string $role, bool $confirmedEnd): TeacherSession
    {
        $session = $this->syncSession($session);

        if ($session->status !== 'in_progress') {
            throw ValidationException::withMessages([
                'session' => 'يمكن إنهاء الجلسة الجارية فقط.',
            ]);
        }

        $startedAt = $session->started_at ?? $session->scheduled_at ?? now();
        $elapsedMinutes = $startedAt->diffInMinutes(now());

        if ($elapsedMinutes >= 15 && ! $confirmedEnd) {
            $message = $role === 'student'
                ? 'إنهاء الجلسة الآن سيؤدي إلى احتساب ثمنها كاملًا. أكّد الإنهاء للمتابعة.'
                : 'يرجى تأكيد إنهاء الجلسة للمتابعة.';

            throw ValidationException::withMessages([
                'confirm_end' => $message,
            ]);
        }

        return $this->finishSession($session, now(), $role);
    }

    /**
     * Build the room polling payload.
     */
    public function roomState(TeacherSession $session, string $role): array
    {
        $session = $this->syncSession($session)->load($this->roomRelations());
        $plannedEndAt = $session->plannedEndAt();
        $recordingRemoved = ! $session->recording_url
            && $session->recording_expires_at
            && $session->recording_expires_at->isPast();

        return [
            'session' => [
                'id' => $session->id,
                'status' => $session->status,
                'scheduled_at' => $session->scheduled_at?->toIso8601String(),
                'started_at' => $session->started_at?->toIso8601String(),
                'ended_at' => $session->ended_at?->toIso8601String(),
                'planned_end_at' => $plannedEndAt?->toIso8601String(),
                'duration_hours' => (int) ($session->duration_hours ?: 1),
                'join_deadline_at' => $session->join_deadline_at?->toIso8601String(),
                'teacher_joined_at' => $session->teacher_joined_at?->toIso8601String(),
                'student_joined_at' => $session->student_joined_at?->toIso8601String(),
                'teacher_private_notes' => $role === 'teacher' ? $session->teacher_private_notes : null,
                'student_summary_notes' => $role === 'teacher' || $session->status === 'completed'
                    ? $session->student_summary_notes
                    : null,
                'recording_url' => $session->recording_public_url,
                'recording_expires_at' => $session->recording_expires_at?->toIso8601String(),
                'recording_removed' => $recordingRemoved,
                'recording_note' => $recordingRemoved
                    ? 'تم حذف تسجيل هذه الجلسة تلقائيًا بعد مرور 3 ساعات على انتهائها.'
                    : ($session->recording_expires_at
                        ? 'سيتم حذف تسجيل هذه الجلسة تلقائيًا بعد 3 ساعات من انتهائها.'
                        : null),
                'can_join_now' => $this->canJoinNow($session),
                'can_end_now' => $session->status === 'in_progress',
                'teacher_name' => $session->teacher?->name ?? 'الأستاذ',
                'student_name' => $session->student?->name ?? ($session->student_name ?: 'الطالب'),
                'participant_name' => $role === 'teacher'
                    ? ($session->student_name ?: ($session->student?->name ?? 'الطالب'))
                    : ($session->teacher?->name ?? 'الأستاذ'),
                'subject_name' => $session->subject?->name ?? 'جلسة',
            ],
            'messages' => $session->messages
                ->sortBy('id')
                ->values()
                ->map(fn (TeacherSessionMessage $message) => [
                    'id' => $message->id,
                    'sender_role' => $message->sender_role,
                    'sender_name' => $message->sender_name,
                    'message' => $message->message,
                    'created_at' => $message->created_at?->toIso8601String(),
                ]),
            'files' => $session->files
                ->map(fn (TeacherSessionFile $file) => [
                    'id' => $file->id,
                    'uploader_role' => $file->uploader_role,
                    'uploader_name' => $file->uploader_name,
                    'original_name' => $file->original_name,
                    'file_url' => $file->file_url,
                    'size' => $file->size,
                    'created_at' => $file->created_at?->toIso8601String(),
                ]),
            'complaints' => $session->complaints
                ->sortByDesc('id')
                ->values()
                ->map(fn (TeacherComplaint $complaint) => [
                    'id' => $complaint->id,
                    'title' => $complaint->title,
                    'status' => $complaint->status,
                    'submitted_by' => $complaint->submitted_by,
                    'submitted_at' => $complaint->submitted_at?->toIso8601String(),
                ]),
        ];
    }

    /**
     * Quick payload consumed by hero cards and prompt modals.
     *
     * @return array<string, mixed>
     */
    public function quickJoinPayload(TeacherSession $session, string $role): array
    {
        $session = $this->syncSession($session)->loadMissing(['teacher', 'student', 'subject']);
        $joinUrl = $role === 'teacher'
            ? route('teacher.sessions.room.show', ['sessionId' => $session->id, 'autojoin' => 1], false)
            : route('student.sessions.room.show', ['sessionId' => $session->id, 'autojoin' => 1], false);

        return [
            'id' => $session->id,
            'subject_name' => $session->subject?->name ?? 'جلسة',
            'participant_name' => $role === 'teacher'
                ? ($session->student_name ?: ($session->student?->name ?? 'الطالب'))
                : ($session->teacher?->name ?? 'الأستاذ'),
            'scheduled_at_label' => $session->scheduled_at?->copy()->timezone(config('app.timezone'))->format('Y-m-d H:i'),
            'scheduled_at_iso' => $session->scheduled_at?->toIso8601String(),
            'started_at_iso' => $session->started_at?->toIso8601String(),
            'planned_end_at_iso' => $session->plannedEndAt()?->toIso8601String(),
            'join_deadline_at_iso' => $session->join_deadline_at?->toIso8601String(),
            'duration_hours' => (int) ($session->duration_hours ?: 1),
            'status' => $session->status,
            'can_join_now' => $this->canJoinNow($session),
            'join_url' => $joinUrl,
        ];
    }

    /**
     * Purge expired recordings across all sessions.
     */
    public function cleanupExpiredRecordings(): int
    {
        $count = 0;

        TeacherSession::query()
            ->whereNotNull('recording_expires_at')
            ->where('recording_expires_at', '<=', now())
            ->whereNotNull('recording_url')
            ->get()
            ->each(function (TeacherSession $session) use (&$count): void {
                if ($this->purgeRecording($session)) {
                    $count++;
                }
            });

        return $count;
    }

    /**
     * Shared session relations for room and detail pages.
     *
     * @return array<int|string, mixed>
     */
    private function roomRelations(): array
    {
        return [
            'teacher',
            'student',
            'subject',
            'messages' => fn ($query) => $query->orderBy('id'),
            'files' => fn ($query) => $query->latest('id'),
            'complaints' => fn ($query) => $query->latest('submitted_at'),
        ];
    }

    /**
     * Reload a session with all room relations.
     */
    private function freshRoomSession(TeacherSession $session): TeacherSession
    {
        return $session->fresh()->load($this->roomRelations());
    }

    /**
     * Sync lifecycle rules for a single session.
     */
    private function syncSession(TeacherSession $session): TeacherSession
    {
        $session->refresh();
        $now = now();

        if ($session->recording_expires_at && $session->recording_url && $session->recording_expires_at->lessThanOrEqualTo($now)) {
            $this->purgeRecording($session);
            $session->refresh();
        }

        $plannedEnd = $session->plannedEndAt();

        if (
            $session->status === 'upcoming'
            && $session->teacher_confirmed_at
            && $session->student_confirmed_at
            && $session->scheduled_at
            && $now->greaterThanOrEqualTo($session->scheduled_at)
            && ! $session->join_deadline_at
        ) {
            $session->update([
                'join_deadline_at' => $session->scheduled_at->copy()->addMinutes(5),
            ]);

            $session->refresh();
        }

        if (
            in_array($session->status, ['upcoming', 'in_progress'], true)
            && $session->join_deadline_at
            && $session->join_deadline_at->lessThan($now)
            && (! $session->teacher_joined_at || ! $session->student_joined_at)
        ) {
            $session->update([
                'status' => 'cancelled',
                'cancellation_reason' => $session->cancellation_reason ?: 'تم إلغاء الجلسة لعدم دخول الطرفين إلى الغرفة خلال أول 5 دقائق.',
            ]);

            $session->update([
                'cancellation_reason' => 'تم إلغاء الجلسة لعدم اكتمال دخول الطرفين إلى الغرفة خلال أول 5 دقائق.',
            ]);

            return $session->fresh();
        }

        if ($session->status === 'upcoming' && $plannedEnd && $plannedEnd->lessThanOrEqualTo($now)) {
            $session->update([
                'status' => 'cancelled',
                'cancellation_reason' => $session->cancellation_reason ?: 'تم إلغاء الجلسة تلقائيًا لانتهاء وقتها دون انعقاد.',
            ]);

            return $session->fresh();
        }

        if ($session->status === 'in_progress' && $plannedEnd && $plannedEnd->lessThanOrEqualTo($now)) {
            return $this->finishSession($session, $plannedEnd, 'system');
        }

        return $session;
    }

    /**
     * Determine whether the session can be joined now.
     */
    private function canJoinNow(TeacherSession $session): bool
    {
        $plannedEnd = $session->plannedEndAt();

        if (! $session->scheduled_at || ! $plannedEnd) {
            return false;
        }

        if (! $session->teacher_confirmed_at || ! $session->student_confirmed_at) {
            return false;
        }

        if (! in_array($session->status, ['upcoming', 'in_progress'], true)) {
            return false;
        }

        $now = now();

        return $now->greaterThanOrEqualTo($session->scheduled_at) && $now->lessThan($plannedEnd);
    }

    /**
     * Resolve the active prompt-worthy session from a collection.
     *
     * @param  Collection<int, TeacherSession>  $sessions
     */
    private function activeSession(Collection $sessions): ?TeacherSession
    {
        return $sessions
            ->map(fn (TeacherSession $session) => $this->syncSession($session))
            ->first(fn (TeacherSession $session) => $this->canJoinNow($session));
    }

    /**
     * Resolve the nearest confirmed session that should be tracked by the UI.
     *
     * @param  Collection<int, TeacherSession>  $sessions
     */
    private function joinCandidate(Collection $sessions): ?TeacherSession
    {
        $candidates = $sessions
            ->map(fn (TeacherSession $session) => $this->syncSession($session))
            ->filter(function (TeacherSession $session): bool {
                $plannedEndAt = $session->plannedEndAt();

                return $session->teacher_confirmed_at
                    && $session->student_confirmed_at
                    && in_array($session->status, ['upcoming', 'in_progress'], true)
                    && $plannedEndAt
                    && $plannedEndAt->isFuture();
            })
            ->values();

        return $candidates->first(fn (TeacherSession $session) => $this->canJoinNow($session))
            ?? $candidates->first();
    }

    /**
     * Ensure the room is currently available.
     */
    private function ensureRoomIsOpen(TeacherSession $session, bool $allowCompleted = false): void
    {
        $session = $this->syncSession($session);

        if ($session->status === 'in_progress') {
            return;
        }

        if ($allowCompleted && $session->status === 'completed') {
            return;
        }

        throw ValidationException::withMessages([
            'session' => 'هذه الجلسة ليست مفتوحة الآن.',
        ]);
    }

    /**
     * Finalize a session and preserve excerpts and expiry timings.
     */
    private function finishSession(TeacherSession $session, Carbon $endedAt, string $role): TeacherSession
    {
        $this->refreshChatExcerpt($session);

        $session->update([
            'status' => 'completed',
            'ended_at' => $endedAt,
            'ended_by_role' => $role,
            'recording_expires_at' => $session->recording_url
                ? ($session->recording_expires_at ?? $endedAt->copy()->addHours(3))
                : $session->recording_expires_at,
        ]);

        return $this->freshRoomSession($session);
    }

    /**
     * Refresh the stored chat excerpt for details pages.
     */
    private function refreshChatExcerpt(TeacherSession $session): void
    {
        $excerpt = $session->messages()
            ->latest('id')
            ->take(4)
            ->get()
            ->reverse()
            ->map(fn (TeacherSessionMessage $message) => "{$message->sender_name}: {$message->message}")
            ->implode("\n");

        $session->update([
            'chat_excerpt' => $excerpt ?: $session->chat_excerpt,
        ]);
    }

    /**
     * Remove a stored recording when it expires.
     */
    private function purgeRecording(TeacherSession $session): bool
    {
        if (! $session->recording_url || str_starts_with($session->recording_url, 'http') || $session->recording_url === '#') {
            $session->update(['recording_url' => null]);

            return true;
        }

        Storage::disk('public')->delete($session->recording_url);
        $session->update(['recording_url' => null]);

        return true;
    }

    /**
     * Friendly actor name based on session ownership.
     */
    private function actorDisplayName(TeacherSession $session, string $role): string
    {
        return $role === 'teacher'
            ? ($session->teacher?->name ?? 'الأستاذ')
            : ($session->student?->name ?? ($session->student_name ?: 'الطالب'));
    }
}
