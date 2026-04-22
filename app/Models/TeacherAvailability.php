<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Bookable time slot for a teacher.
 */
class TeacherAvailability extends Model
{
    /**
     * Mass assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'teacher_id',
        'teacher_subject_id',
        'day_of_week',
        'starts_at',
        'ends_at',
        'notes',
        'is_booked',
    ];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_booked' => 'boolean',
        ];
    }

    /**
     * Owning teacher.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Linked subject, when set.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(TeacherSubject::class, 'teacher_subject_id');
    }
}
