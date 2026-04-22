<?php

namespace App\Services\Teacher;

use App\Models\Teacher;
use App\Models\TeacherAvailability;
use App\Models\TeacherSession;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Handles teacher availability slots.
 */
class TeacherAvailabilityService
{
    /**
     * Store an availability after ownership checks.
     *
     * @param  array<string, mixed>  $data
     */
    public function store(Teacher $teacher, array $data): TeacherAvailability
    {
        if (! empty($data['teacher_subject_id']) && ! $teacher->subjects()->whereKey($data['teacher_subject_id'])->exists()) {
            throw ValidationException::withMessages([
                'teacher_subject_id' => 'المادة المحددة لا تعود لهذا الأستاذ.',
            ]);
        }

        return $teacher->availabilities()->create($data);
    }

    /**
     * Summarize bookable windows after removing overlapping upcoming sessions.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function summarizedWindows(Teacher $teacher): Collection
    {
        $teacher->loadMissing([
            'availabilities.subject',
            'sessions' => fn ($query) => $query
                ->where('status', 'upcoming')
                ->orderBy('scheduled_at'),
        ]);

        $segments = $teacher->availabilities
            ->sortBy([
                ['day_of_week', 'asc'],
                ['starts_at', 'asc'],
            ])
            ->flatMap(fn (TeacherAvailability $availability) => $this->buildFreeSegments($teacher, $availability));

        return $this->mergeContiguousSegments($segments)->values();
    }

    /**
     * Build free segments for one recurring availability.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function buildFreeSegments(Teacher $teacher, TeacherAvailability $availability): Collection
    {
        $windowStart = $this->nextOccurrenceForDay($availability->day_of_week, $availability->starts_at);
        $windowEnd = $windowStart->setTimeFromTimeString($availability->ends_at);
        $cursor = $windowStart;
        $segments = collect();

        $conflicts = $teacher->sessions
            ->filter(function (TeacherSession $session) use ($windowStart, $windowEnd): bool {
                if (! $session->scheduled_at || $session->status !== 'upcoming') {
                    return false;
                }

                $sessionEnd = $session->ended_at ?? $session->scheduled_at->addHours((int) ($session->duration_hours ?: 1));

                return $session->scheduled_at < $windowEnd && $sessionEnd > $windowStart;
            })
            ->sortBy('scheduled_at')
            ->values();

        foreach ($conflicts as $session) {
            $sessionStart = $session->scheduled_at->greaterThan($windowStart) ? $session->scheduled_at->toImmutable() : $windowStart;
            $sessionEnd = ($session->ended_at ?? $session->scheduled_at->addHours((int) ($session->duration_hours ?: 1)))->toImmutable();

            if ($sessionStart->greaterThan($cursor)) {
                $segments->push($this->segmentPayload($availability, $cursor, $sessionStart));
            }

            if ($sessionEnd->greaterThan($cursor)) {
                $cursor = $sessionEnd->lessThan($windowEnd) ? $sessionEnd : $windowEnd;
            }
        }

        if ($cursor->lessThan($windowEnd)) {
            $segments->push($this->segmentPayload($availability, $cursor, $windowEnd));
        }

        return $segments
            ->filter(fn (array $segment): bool => $segment['starts_at'] < $segment['ends_at'])
            ->values();
    }

    /**
     * Merge adjacent segments for the same day and subject.
     *
     * @param  Collection<int, array<string, mixed>>  $segments
     * @return Collection<int, array<string, mixed>>
     */
    private function mergeContiguousSegments(Collection $segments): Collection
    {
        $merged = collect();

        foreach ($segments->sortBy([
            ['day_of_week', 'asc'],
            ['subject_key', 'asc'],
            ['starts_at', 'asc'],
        ]) as $segment) {
            $lastIndex = $merged->keys()->last();
            $last = $lastIndex !== null ? $merged->get($lastIndex) : null;

            if (
                $last
                && $last['day_of_week'] === $segment['day_of_week']
                && $last['subject_key'] === $segment['subject_key']
                && $last['ends_at'] === $segment['starts_at']
            ) {
                $last['ends_at'] = $segment['ends_at'];
                $last['ends_label'] = $segment['ends_label'];
                $last['label'] = sprintf('%s / %s-%s', $last['day_label'], $last['starts_label'], $segment['ends_label']);
                $merged->put($lastIndex, $last);
                continue;
            }

            $merged->push($segment);
        }

        return $merged->map(function (array $segment): array {
            unset($segment['subject_key']);

            return $segment;
        });
    }

    /**
     * Format one free segment.
     *
     * @return array<string, mixed>
     */
    private function segmentPayload(TeacherAvailability $availability, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $dayLabel = $this->dayLabels()[$availability->day_of_week] ?? 'غير محدد';
        $startsLabel = $start->format('H:i');
        $endsLabel = $end->format('H:i');

        return [
            'day_of_week' => $availability->day_of_week,
            'day_label' => $dayLabel,
            'starts_at' => $start->format('Y-m-d H:i:s'),
            'ends_at' => $end->format('Y-m-d H:i:s'),
            'starts_label' => $startsLabel,
            'ends_label' => $endsLabel,
            'label' => sprintf('%s / %s-%s', $dayLabel, $startsLabel, $endsLabel),
            'subject_name' => $availability->subject?->name,
            'subject_key' => $availability->teacher_subject_id ?: 'general',
        ];
    }

    /**
     * Shared day labels.
     *
     * @return array<int, string>
     */
    private function dayLabels(): array
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
     * Resolve next concrete date-time for a recurring day and time.
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
}
