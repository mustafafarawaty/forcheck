<?php

namespace App\Services\Teacher;

use App\Models\Teacher;
use App\Models\TeacherLiveRequest;
use App\Repositories\TeacherLiveRequestRepository;
use App\Services\AppSettingsService;
use App\Services\Realtime\RealtimeUpdateService;
use App\Services\Wallet\WalletService;
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
        private readonly WalletService $wallets,
        private readonly AppSettingsService $settings,
        private readonly TeacherPresenceService $presence,
    ) {
    }

    /**
     * Toggle teacher instant booking state.
     */
    public function toggle(Teacher $teacher, bool $isEnabled): Teacher
    {
        if ($isEnabled && ! $this->canReceiveInstantRequest($teacher)) {
            throw ValidationException::withMessages([
                'is_accepting_instant_sessions' => 'لا يمكنك تفعيل الجلسات المباشرة الآن. تأكد أنك متصل ولا يوجد لديك طلب مباشر أو جلسة مباشرة قيد العمل.',
            ]);
        }

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
            $otherPendingExists = $teacher->liveRequests()
                ->where('status', 'pending')
                ->whereKeyNot($request->id)
                ->exists();

            if ($otherPendingExists || $this->hasActiveInstantSession($teacher)) {
                throw ValidationException::withMessages([
                    'request' => 'لا يمكن استقبال أكثر من طلب مباشر واحد في نفس الوقت.',
                ]);
            }

            $this->wallets->ensureStudentCanAfford($request->student, (float) $request->subject->hourly_rate_syp, 'session');
            $pricing = $this->settings->sessionPricing((float) $request->subject->hourly_rate_syp);

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
                'price' => $pricing['gross'],
                'admin_commission_percentage' => $pricing['admin_commission_percentage'],
                'admin_commission_amount' => $pricing['admin_commission_amount'],
                'teacher_earning_amount' => $pricing['teacher_earning_amount'],
                'notes' => $request->note ?: 'جلسة مباشرة تم قبولها فورًا.',
            ]);

            $request->update([
                'status' => 'accepted',
                'teacher_session_id' => $session->id,
                'responded_at' => now(),
            ]);

            $this->wallets->holdSessionAmount($session);

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

        $nextTeacher = $this->findNextInstantTeacher($updatedRequest);

        if ($nextTeacher) {
            $nextRequest = $this->liveRequests->create([
                'teacher_id' => $nextTeacher->id,
                'student_id' => $updatedRequest->student_id,
                'teacher_subject_id' => $updatedRequest->teacher_subject_id,
                'status' => 'pending',
                'note' => $updatedRequest->note,
                'requested_at' => now(),
            ]);

            $this->realtime->broadcastTeacherDashboard($nextTeacher);

            if ($nextRequest->student) {
                $this->realtime->broadcastStudentDashboard($nextRequest->student, liveRequest: $nextRequest);
            }
        }

        return $updatedRequest;
    }

    /**
     * Find the next eligible teacher for the same instant request chain.
     */
    private function findNextInstantTeacher(TeacherLiveRequest $request): ?Teacher
    {
        $excludedTeacherIds = TeacherLiveRequest::query()
            ->where('student_id', $request->student_id)
            ->where('teacher_subject_id', $request->teacher_subject_id)
            ->where('requested_at', '>=', now()->subMinutes(5))
            ->whereIn('status', ['pending', 'rejected', 'accepted'])
            ->pluck('teacher_id')
            ->all();

        return Teacher::query()
            ->where('is_accepting_instant_sessions', true)
            ->whereNotIn('id', $excludedTeacherIds)
            ->whereDoesntHave('liveRequests', function ($query): void {
                $query->where('status', 'pending');
            })
            ->whereDoesntHave('sessions', function ($query): void {
                $query
                    ->where('booking_type', 'instant')
                    ->whereIn('status', ['upcoming', 'in_progress']);
            })
            ->whereHas('subjects', function ($subjectQuery) use ($request) {
                $subjectQuery
                    ->where('name', $request->subject->name)
                    ->where('level', $request->student->study_level);
            })
            ->inRandomOrder()
            ->get()
            ->first(fn (Teacher $teacher): bool => $this->presence->isOnline($teacher));
    }

    public function canReceiveInstantRequest(Teacher $teacher): bool
    {
        return $this->presence->isOnline($teacher)
            && ! $this->hasPendingInstantRequest($teacher)
            && ! $this->hasActiveInstantSession($teacher);
    }

    public function hasPendingInstantRequest(Teacher $teacher): bool
    {
        return $teacher->liveRequests()
            ->where('status', 'pending')
            ->exists();
    }

    public function hasActiveInstantSession(Teacher $teacher): bool
    {
        return $teacher->sessions()
            ->where('booking_type', 'instant')
            ->whereIn('status', ['upcoming', 'in_progress'])
            ->exists();
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
