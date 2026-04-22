<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Complaint submitted by a session participant to platform administration.
 */
class TeacherComplaint extends Model
{
    /**
     * Mass assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'teacher_id',
        'student_id',
        'teacher_session_id',
        'title',
        'description',
        'submitted_by',
        'status',
        'submitted_at',
    ];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
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
     * Student author when available.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Related session, when complaint is tied to one.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(TeacherSession::class, 'teacher_session_id');
    }
}
