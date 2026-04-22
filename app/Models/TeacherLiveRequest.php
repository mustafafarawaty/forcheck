<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pending or resolved live session request between student and teacher.
 */
class TeacherLiveRequest extends Model
{
    /**
     * Mass assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'teacher_id',
        'student_id',
        'teacher_subject_id',
        'teacher_session_id',
        'status',
        'note',
        'requested_at',
        'responded_at',
    ];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    /**
     * Requested teacher.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Requesting student.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Requested subject.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(TeacherSubject::class, 'teacher_subject_id');
    }

    /**
     * Accepted session if created.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(TeacherSession::class, 'teacher_session_id');
    }
}
