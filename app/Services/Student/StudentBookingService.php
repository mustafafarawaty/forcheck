<?php

namespace App\Services\Student;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherAvailability;
use App\Models\TeacherLiveRequest;
use App\Models\TeacherSession;
use App\Models\TeacherSubject;
use App\Repositories\TeacherLiveRequestRepository;
use App\Services\Realtime\RealtimeUpdateService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Handles student bookings for live and scheduled sessions.
 */
class StudentBookingService
{
    public function __construct(
        private readonly TeacherLiveRequestRepository $liveRequests,
        private readonly RealtimeUpdateService $realtime,
    ) {
    }

    /**
     * Create a booking against a preferred teacher or a random match.
     *
     * @param  array<string, mixed>  $data
     * @return array{mode:string, teacher:Teacher, subject:TeacherSubject, session:TeacherSession|null, liveRequest:TeacherLiveRequest|null}
     */
    public function book(Student $student, array $data): array
    {
        return DB::transaction(function () use ($student, $data): array {
            $bookingMode = $data['booking_mode'];
            $durationHours = (int) $data['duration_hours'];
            $selection = $this->resolveTeacherAndSubject(
                $student,
                $data['subject_name'],
                isset($data['teacher_id']) ? (int) $data['teacher_id'] : null,
                $bookingMode,
                $data['scheduled_slot_at'] ?? null,
                isset($data['preferred_day_of_week']) ? (int) $data['preferred_day_of_week'] : null,
                $data['preferred_starts_at'] ?? null,
                $durationHours,
            );

            $teacher = $selection['teacher'];
            $subject = $selection['subject'];

            if ($bookingMode === 'instant') {
                if (! $teacher->is_accepting_instant_sessions) {
                    throw ValidationException::withMessages([
                        'booking_mode' => 'هذا الأستاذ لا يستقبل جلسات مباشرة حاليًا.',
                    ]);
                }

                $this->ensureStudentIsAvailable($student, CarbonImmutable::now()->addMinutes(5), 1);

                $liveRequest = $this->liveRequests->create([
                    'teacher_id' => $teacher->id,
                    'student_id' => $student->id,
                    'teacher_subject_id' => $subject->id,
                    'status' => 'pending',
                    'note' => $data['note'] ?? null,
                    'requested_at' => now(),
                ]);

                $this->realtime->broadcastTeacherDashboard($teacher);
                $this->realtime->broadcastStudentDashboard($student, liveRequest: $liveRequest);

                return [
                    'mode' => 'instant',
                    'teacher' => $teacher,
                    'subject' => $subject,
                    'session' => null,
                    'liveRequest' => $liveRequest,
                ];
            }

            $availability = $this->resolveAvailability(
                $teacher,
                $subject,
                isset($data['teacher_availability_id']) ? (int) $data['teacher_availability_id'] : null,
                $data['scheduled_slot_at'] ?? null,
                isset($data['preferred_day_of_week']) ? (int) $data['preferred_day_of_week'] : null,
                $data['preferred_starts_at'] ?? null,
                $durationHours,
            );
            $scheduledAt = $this->resolveScheduledAt($availability, $data['scheduled_slot_at'] ?? null);

            $this->ensureStudentIsAvailable($student, $scheduledAt, $durationHours);

            $session = $teacher->sessions()->create([
                'teacher_subject_id' => $subject->id,
                'student_id' => $student->id,
                'student_name' => $student->name,
                'scheduled_at' => $scheduledAt,
                'ended_at' => $scheduledAt->addHours($durationHours),
                'status' => 'upcoming',
                'booking_type' => 'scheduled',
                'duration_hours' => $durationHours,
                'price' => $subject->hourly_rate_syp * $durationHours,
                'notes' => $data['note'] ?? null,
                'confirmation_deadline_at' => $scheduledAt->subMinutes(30),
            ]);

            $this->realtime->broadcastSessionParticipants($session);

            return [
                'mode' => 'scheduled',
                'teacher' => $teacher,
                'subject' => $subject,
                'session' => $session,
                'liveRequest' => null,
            ];
        });
    }

    /**
     * Reassign a scheduled session to a different teacher when original one misses confirmation.
     */
    public function reassignScheduledSession(TeacherSession $session): ?TeacherSession
    {
        if (! $session->student || ! $session->subject) {
            return null;
        }

        $student = $session->student;
        $durationHours = (int) ($session->duration_hours ?: 1);

        $candidates = Teacher::query()
            ->whereKeyNot($session->teacher_id)
            ->whereHas('subjects', function ($query) use ($session, $student): void {
                $query
                    ->where('name', $session->subject->name)
                    ->where('level', $student->study_level);
            })
            ->with([
                'subjects',
                'availabilities' => fn ($query) => $query->where('is_booked', false),
                'sessions' => fn ($query) => $query->where('status', 'upcoming'),
            ])
            ->get();

        $candidate = $candidates->first(function (Teacher $teacher) use ($durationHours): bool {
            return $teacher->availabilities->contains(function (TeacherAvailability $availability) use ($teacher, $durationHours): bool {
                $slot = $this->nextOccurrence($availability);

                return $this->slotIsAvailable($teacher, $availability, $slot->format('Y-m-d H:i:s'), $durationHours);
            });
        });

        if (! $candidate) {
            return null;
        }

        $subject = $candidate->subjects()
            ->where('name', $session->subject->name)
            ->where('level', $student->study_level)
            ->first();

        if (! $subject) {
            return null;
        }

        $availability = $this->resolveAvailability($candidate, $subject, null, null, null, null, $durationHours);
        $scheduledAt = $this->nextOccurrence($availability);

        return $candidate->sessions()->create([
            'teacher_subject_id' => $subject->id,
            'student_id' => $student->id,
            'student_name' => $student->name,
            'scheduled_at' => $scheduledAt,
            'ended_at' => $scheduledAt->addHours($durationHours),
            'status' => 'upcoming',
            'booking_type' => 'scheduled',
            'duration_hours' => $durationHours,
            'price' => $subject->hourly_rate_syp * $durationHours,
            'notes' => 'تمت إعادة جدولة الجلسة تلقائيًا بعد عدم تأكيد الموعد من الأستاذ السابق.',
            'confirmation_deadline_at' => $scheduledAt->subMinutes(30),
        ]);
    }

    /**
     * Find teacher and subject for the requested booking.
     *
     * @return array{teacher:Teacher, subject:TeacherSubject}
     */
    private function resolveTeacherAndSubject(
        Student $student,
        string $subjectName,
        ?int $teacherId,
        string $bookingMode,
        ?string $scheduledSlotAt,
        ?int $preferredDayOfWeek,
        ?string $preferredStartsAt,
        int $durationHours,
    ): array {
        $query = Teacher::query()
            ->with([
                'subjects' => fn ($subjectQuery) => $subjectQuery
                    ->where('name', $subjectName)
                    ->where('level', $student->study_level),
                'availabilities' => fn ($availabilityQuery) => $availabilityQuery
                    ->where('is_booked', false),
                'sessions' => fn ($sessionQuery) => $sessionQuery
                    ->where('status', 'upcoming'),
            ])
            ->whereHas('subjects', function ($subjectQuery) use ($student, $subjectName): void {
                $subjectQuery
                    ->where('name', $subjectName)
                    ->where('level', $student->study_level);
            });

        if ($teacherId) {
            $query->whereKey($teacherId);
            $teachers = $query->get();
        } else {
            if ($bookingMode === 'instant') {
                $query->where('is_accepting_instant_sessions', true);
            }

            $teachers = $query->get()->shuffle();
        }

        $teacher = $teachers->first(function (Teacher $teacher) use ($bookingMode, $scheduledSlotAt, $preferredDayOfWeek, $preferredStartsAt, $durationHours): bool {
            if ($bookingMode === 'instant') {
                return $teacher->is_accepting_instant_sessions;
            }

            return $teacher->availabilities->contains(function (TeacherAvailability $availability) use ($teacher, $scheduledSlotAt, $preferredDayOfWeek, $preferredStartsAt, $durationHours): bool {
                if ($scheduledSlotAt) {
                    return $this->slotIsAvailable($teacher, $availability, $scheduledSlotAt, $durationHours);
                }

                if ($preferredDayOfWeek === null || ! $preferredStartsAt) {
                    $slot = $this->nextOccurrence($availability);

                    return $this->slotIsAvailable($teacher, $availability, $slot->format('Y-m-d H:i:s'), $durationHours);
                }

                $slot = $this->nextOccurrenceForRequestedTime($preferredDayOfWeek, $preferredStartsAt);

                return $this->slotIsAvailable($teacher, $availability, $slot->format('Y-m-d H:i:s'), $durationHours);
            });
        });

        if (! $teacher) {
            throw ValidationException::withMessages([
                'subject_name' => 'لا يوجد أستاذ متاح حاليًا لهذا الطلب بالوقت المطلوب.',
            ]);
        }

        $subject = $teacher->subjects->first();

        if (! $subject) {
            throw ValidationException::withMessages([
                'subject_name' => 'المادة المطلوبة غير متاحة مع هذا الأستاذ.',
            ]);
        }

        return ['teacher' => $teacher, 'subject' => $subject];
    }

    /**
     * Resolve first available bookable slot.
     */
    private function resolveAvailability(
        Teacher $teacher,
        TeacherSubject $subject,
        ?int $availabilityId = null,
        ?string $scheduledSlotAt = null,
        ?int $preferredDayOfWeek = null,
        ?string $preferredStartsAt = null,
        int $durationHours = 1,
    ): TeacherAvailability {
        $availabilities = $teacher->availabilities
            ->filter(function (TeacherAvailability $availability) use ($subject): bool {
                return ! $availability->teacher_subject_id || $availability->teacher_subject_id === $subject->id;
            })
            ->values();

        if ($availabilityId) {
            $availabilities = $availabilities->where('id', $availabilityId)->values();
        }

        $availability = $availabilities->first(function (TeacherAvailability $availability) use ($teacher, $scheduledSlotAt, $preferredDayOfWeek, $preferredStartsAt, $durationHours): bool {
            if ($scheduledSlotAt) {
                return $this->slotIsAvailable($teacher, $availability, $scheduledSlotAt, $durationHours);
            }

            if ($preferredDayOfWeek !== null && $preferredStartsAt) {
                $slot = $this->nextOccurrenceForRequestedTime($preferredDayOfWeek, $preferredStartsAt);

                return $this->slotIsAvailable($teacher, $availability, $slot->format('Y-m-d H:i:s'), $durationHours);
            }

            $slot = $this->nextOccurrence($availability);

            return $this->slotIsAvailable($teacher, $availability, $slot->format('Y-m-d H:i:s'), $durationHours);
        });

        if (! $availability) {
            throw ValidationException::withMessages([
                'teacher_availability_id' => 'لا يوجد موعد متاح مطابق لهذا الحجز.',
            ]);
        }

        return $availability;
    }

    /**
     * Compute next actual date-time for a recurring availability.
     */
    private function nextOccurrence(TeacherAvailability $availability): CarbonImmutable
    {
        $today = CarbonImmutable::now();
        $daysUntil = ($availability->day_of_week - $today->dayOfWeek + 7) % 7;

        $candidate = $today
            ->addDays($daysUntil)
            ->setTimeFromTimeString($availability->starts_at);

        if ($candidate->lessThanOrEqualTo($today)) {
            $candidate = $candidate->addWeek();
        }

        return $candidate;
    }

    /**
     * Resolve final scheduled datetime.
     */
    private function resolveScheduledAt(TeacherAvailability $availability, ?string $scheduledSlotAt = null): CarbonImmutable
    {
        if ($scheduledSlotAt) {
            return CarbonImmutable::parse($scheduledSlotAt);
        }

        return $this->nextOccurrence($availability);
    }

    /**
     * Check if a concrete slot is still available for the requested duration.
     */
    private function slotIsAvailable(Teacher $teacher, TeacherAvailability $availability, string $scheduledSlotAt, int $durationHours): bool
    {
        $slot = CarbonImmutable::parse($scheduledSlotAt);
        $availabilityStart = CarbonImmutable::parse($availability->starts_at);
        $availabilityEnd = CarbonImmutable::parse($availability->ends_at);

        if ($slot->dayOfWeek !== $availability->day_of_week) {
            return false;
        }

        if ($slot->format('H:i:s') < $availabilityStart->format('H:i:s')) {
            return false;
        }

        if ($slot->addHours($durationHours)->format('H:i:s') > $availabilityEnd->format('H:i:s')) {
            return false;
        }

        foreach ($teacher->sessions as $session) {
            if (! $session->scheduled_at || $session->status !== 'upcoming') {
                continue;
            }

            $sessionEnd = $session->scheduled_at->addHours((int) ($session->duration_hours ?: 1));
            $requestedEnd = $slot->addHours($durationHours);

            if ($slot < $sessionEnd && $requestedEnd > $session->scheduled_at) {
                return false;
            }
        }

        return true;
    }

    /**
     * Build next occurrence for preferred generic day/time request.
     */
    private function nextOccurrenceForRequestedTime(int $dayOfWeek, string $time): CarbonImmutable
    {
        $today = CarbonImmutable::now();
        $daysUntil = ($dayOfWeek - $today->dayOfWeek + 7) % 7;

        $candidate = $today
            ->addDays($daysUntil)
            ->setTimeFromTimeString($time . ':00');

        if ($candidate->lessThanOrEqualTo($today)) {
            $candidate = $candidate->addWeek();
        }

        return $candidate;
    }

    /**
     * Prevent the student from having overlapping non-cancelled sessions.
     */
    private function ensureStudentIsAvailable(Student $student, CarbonImmutable $startAt, int $durationHours): void
    {
        $requestedEnd = $startAt->addHours($durationHours);

        $hasConflict = $student->sessions()
            ->where('status', '!=', 'cancelled')
            ->get()
            ->contains(function (TeacherSession $session) use ($startAt, $requestedEnd): bool {
                if (! $session->scheduled_at) {
                    return false;
                }

                $sessionStart = CarbonImmutable::parse($session->scheduled_at);
                $sessionEnd = $session->ended_at
                    ? CarbonImmutable::parse($session->ended_at)
                    : $sessionStart->addHours((int) ($session->duration_hours ?: 1));

                return $startAt < $sessionEnd && $requestedEnd > $sessionStart;
            });

        if ($hasConflict) {
            throw ValidationException::withMessages([
                'subject_name' => 'لديك جلسة أخرى محجوزة في هذا التوقيت بالفعل.',
            ]);
        }
    }
}
