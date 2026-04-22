<?php

namespace App\Services\Teacher;

use App\Models\Teacher;
use App\Models\TeacherSubject;
use Illuminate\Validation\ValidationException;

/**
 * Handles teacher subjects and level constraints.
 */
class TeacherSubjectService
{
    /**
     * Available education level labels.
     *
     * @return array<string, string>
     */
    public static function levelLabels(): array
    {
        return [
            'primary' => 'أساسي',
            'middle' => 'إعدادي',
            'secondary' => 'ثانوي',
            'university' => 'جامعي',
        ];
    }

    /**
     * Available education stage labels.
     *
     * @return array<string, string>
     */
    public static function stageLabels(): array
    {
        return [
            'secondary' => 'ثانوي',
            'university' => 'جامعي',
        ];
    }

    /**
     * Allowed levels based on the teacher highest education stage.
     *
     * @return array<string, string>
     */
    public function allowedLevelsForTeacher(Teacher $teacher): array
    {
        $allowed = $teacher->education_stage === 'university'
            ? ['primary', 'middle', 'secondary', 'university']
            : ['primary', 'middle', 'secondary'];

        return collect(self::levelLabels())
            ->only($allowed)
            ->all();
    }

    /**
     * Store a subject for the teacher while enforcing allowed levels.
     *
     * @param  array{name:string, level:string, hourly_rate_syp:int}  $data
     */
    public function store(Teacher $teacher, array $data): TeacherSubject
    {
        $allowedLevels = array_keys($this->allowedLevelsForTeacher($teacher));

        if (! in_array($data['level'], $allowedLevels, true)) {
            throw ValidationException::withMessages([
                'level' => 'هذا المستوى غير متاح بحسب المستوى التعليمي الذي وصل له الأستاذ.',
            ]);
        }

        return $teacher->subjects()->create($data);
    }
}
