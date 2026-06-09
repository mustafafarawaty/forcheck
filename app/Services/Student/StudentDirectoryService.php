<?php

namespace App\Services\Student;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherAvailability;
use App\Models\TeacherSubject;
use App\Services\Teacher\TeacherPresenceService;
use App\Services\Teacher\TeacherSubjectService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Student-facing directory of teachers and subjects.
 */
class StudentDirectoryService
{
    public function __construct(
        private readonly TeacherPresenceService $presence,
    ) {
    }

    /**
     * Directory cards for teachers relevant to the student level.
     *
     * @return EloquentCollection<int, Teacher>
     */
    public function listTeachers(Student $student): EloquentCollection
    {
        $teachers = Teacher::query()
            ->with([
                'subjects' => fn ($query) => $query
                    ->where('level', $student->study_level)
                    ->orderBy('name'),
                'availabilities' => fn ($query) => $query
                    ->where('is_booked', false)
                    ->orderBy('day_of_week')
                    ->orderBy('starts_at'),
                'liveRequests' => fn ($query) => $query->where('status', 'pending'),
                'sessions' => fn ($query) => $query
                    ->where('booking_type', 'instant')
                    ->whereIn('status', ['upcoming', 'in_progress']),
            ])
            ->whereHas('subjects', fn ($query) => $query->where('level', $student->study_level))
            ->latest()
            ->get()
            ->each(fn (Teacher $teacher) => $this->decorateInstantAvailability($teacher));

        return $teachers;
    }

    /**
     * Available subject names for current student level.
     *
     * @return Collection<int, string>
     */
    public function availableSubjectNames(Student $student): Collection
    {
        return TeacherSubject::query()
            ->where('level', $student->study_level)
            ->orderBy('name')
            ->pluck('name')
            ->unique()
            ->values();
    }

    /**
     * Resolve one teacher profile with related data.
     */
    public function findTeacherForStudent(Student $student, int $teacherId): Teacher
    {
        return Teacher::query()
            ->with([
                'subjects' => fn ($query) => $query
                    ->where('level', $student->study_level)
                    ->orderBy('name'),
                'availabilities' => fn ($query) => $query
                    ->with('subject')
                    ->where('is_booked', false)
                    ->orderBy('day_of_week')
                    ->orderBy('starts_at'),
                'sessions' => fn ($query) => $query
                    ->where(function ($sessionQuery): void {
                        $sessionQuery
                            ->where('status', 'upcoming')
                            ->orWhere(function ($instantQuery): void {
                                $instantQuery
                                    ->where('booking_type', 'instant')
                                    ->whereIn('status', ['upcoming', 'in_progress']);
                            });
                    })
                    ->orderBy('scheduled_at'),
                'liveRequests' => fn ($query) => $query->where('status', 'pending'),
            ])
            ->whereHas('subjects', fn ($query) => $query->where('level', $student->study_level))
            ->findOrFail($teacherId);

        $this->decorateInstantAvailability($teacher);

        return $teacher;
    }

    /**
     * Build available slots for a teacher with max contiguous duration.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function availableSlotsForTeacher(Teacher $teacher): Collection
    {
        return $teacher->availabilities
            ->flatMap(function (TeacherAvailability $availability) use ($teacher): Collection {
                return $this->slotsForAvailability($teacher, $availability);
            })
            ->sortBy('starts_at')
            ->values();
    }

    /**
     * Day-of-week labels.
     *
     * @return array<int, string>
     */
    public function dayLabels(): array
    {
        return [
            0 => 'الأحد',
            1 => 'الاثنين',
            2 => 'الثلاثاء',
            3 => 'الأربعاء',
            4 => 'الخميس',
            5 => 'الجمعة',
            6 => 'السبت',
        ];
    }

    /**
     * Hour choices for generic booking filters.
     *
     * @return Collection<int, string>
     */
    public function hourOptions(): Collection
    {
        return collect(range(8, 22))
            ->map(fn (int $hour) => str_pad((string) $hour, 2, '0', STR_PAD_LEFT) . ':00');
    }

    /**
     * Duration choices.
     *
     * @return Collection<int, int>
     */
    public function durationOptions(): Collection
    {
        return collect([1, 2, 3, 4]);
    }

    /**
     * Level labels for student forms.
     *
     * @return array<string, string>
     */
    public function levelLabels(): array
    {
        return TeacherSubjectService::levelLabels();
    }

    /**
     * Create available start slots from an availability.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function slotsForAvailability(Teacher $teacher, TeacherAvailability $availability): Collection
    {
        $start = CarbonImmutable::parse($availability->starts_at);
        $end = CarbonImmutable::parse($availability->ends_at);
        $slots = collect();
        $dayLabels = $this->dayLabels();

        while ($start->addHour()->lessThanOrEqualTo($end)) {
            $nextOccurrence = $this->nextOccurrenceForDay($availability->day_of_week, $start->format('H:i:s'));
            $maxDuration = $this->maxDurationForSlot($teacher, $availability, $nextOccurrence);

            if ($maxDuration > 0) {
                $slots->push([
                    'availability_id' => $availability->id,
                    'starts_at' => $nextOccurrence->format('Y-m-d H:i:s'),
                    'label' => sprintf(
                        '%s - %s / %s',
                        $dayLabels[$availability->day_of_week] ?? $availability->day_of_week,
                        $nextOccurrence->format('Y-m-d'),
                        $nextOccurrence->format('H:i')
                    ),
                    'subject_name' => $availability->subject?->name,
                    'day_of_week' => $availability->day_of_week,
                    'max_duration' => $maxDuration,
                ]);
            }

            $start = $start->addHour();
        }

        return $slots;
    }

    /**
     * Compute next occurrence for given day and time.
     */
    private function nextOccurrenceForDay(int $dayOfWeek, string $time): CarbonImmutable
    {
        $today = CarbonImmutable::now();
        $daysUntil = ($dayOfWeek - $today->dayOfWeek + 7) % 7;

        $candidate = $today
            ->addDays($daysUntil)
            ->setTimeFromTimeString($time);

        if ($candidate->lessThanOrEqualTo($today)) {
            $candidate = $candidate->addWeek();
        }

        return $candidate;
    }

    /**
     * Calculate the max contiguous available hours for a start slot.
     */
    private function maxDurationForSlot(Teacher $teacher, TeacherAvailability $availability, CarbonImmutable $slotStart): int
    {
        $availabilityEnd = CarbonImmutable::parse($availability->ends_at);
        $maxDuration = 0;

        for ($duration = 1; $duration <= 4; $duration++) {
            $slotEnd = $slotStart->addHours($duration);

            if ($slotEnd->format('H:i:s') > $availabilityEnd->format('H:i:s')) {
                break;
            }

            $hasConflict = false;

            for ($hour = 0; $hour < $duration; $hour++) {
                $current = $slotStart->addHours($hour);

                if ($teacher->sessions->contains(function ($session) use ($current): bool {
                    if (! $session->scheduled_at || $session->status !== 'upcoming') {
                        return false;
                    }

                    $sessionEnd = $session->scheduled_at->addHours((int) ($session->duration_hours ?: 1));

                    return $current->greaterThanOrEqualTo($session->scheduled_at)
                        && $current->lessThan($sessionEnd);
                })) {
                    $hasConflict = true;
                    break;
                }
            }

            if ($hasConflict) {
                break;
            }

            $maxDuration = $duration;
        }

        return $maxDuration;
    }

    private function decorateInstantAvailability(Teacher $teacher): void
    {
        $canReceiveInstant = $teacher->is_accepting_instant_sessions
            && $this->presence->isOnline($teacher)
            && $teacher->liveRequests->isEmpty()
            && $teacher->sessions
                ->where('booking_type', 'instant')
                ->whereIn('status', ['upcoming', 'in_progress'])
                ->isEmpty();

        $teacher->setAttribute('is_accepting_instant_sessions', $canReceiveInstant);
    }
}
