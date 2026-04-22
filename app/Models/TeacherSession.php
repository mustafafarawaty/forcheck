<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Stored teaching session with status, media and optional cancellation.
 */
class TeacherSession extends Model
{
    /**
     * Mass assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'teacher_id',
        'teacher_subject_id',
        'student_id',
        'student_name',
        'scheduled_at',
        'started_at',
        'ended_at',
        'ended_by_role',
        'status',
        'booking_type',
        'teacher_confirmed_at',
        'student_confirmed_at',
        'teacher_joined_at',
        'student_joined_at',
        'join_deadline_at',
        'confirmation_deadline_at',
        'last_reminder_sent_at',
        'duration_hours',
        'price',
        'notes',
        'teacher_private_notes',
        'student_summary_notes',
        'recording_url',
        'recording_expires_at',
        'chat_excerpt',
        'cancellation_reason',
    ];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'teacher_joined_at' => 'datetime',
            'student_joined_at' => 'datetime',
            'join_deadline_at' => 'datetime',
            'teacher_confirmed_at' => 'datetime',
            'student_confirmed_at' => 'datetime',
            'confirmation_deadline_at' => 'datetime',
            'last_reminder_sent_at' => 'datetime',
            'recording_expires_at' => 'datetime',
            'duration_hours' => 'integer',
            'price' => 'decimal:2',
        ];
    }

    /**
     * Planned end time derived from booking duration.
     */
    public function plannedEndAt(): ?Carbon
    {
        $base = $this->started_at ?: $this->scheduled_at;

        if (! $base) {
            return null;
        }

        return $base->copy()->addHours((int) ($this->duration_hours ?: 1));
    }

    /**
     * Public recording URL when the stored value points to local storage.
     */
    public function getRecordingPublicUrlAttribute(): ?string
    {
        if (! $this->recording_url || $this->recording_url === '#') {
            return null;
        }

        if (str_starts_with($this->recording_url, 'http')) {
            return $this->recording_url;
        }

        return Storage::disk('public')->url($this->recording_url);
    }

    /**
     * Owning teacher.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Related subject.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(TeacherSubject::class, 'teacher_subject_id');
    }

    /**
     * Related student.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Related complaints.
     */
    public function complaints(): HasMany
    {
        return $this->hasMany(TeacherComplaint::class);
    }

    /**
     * Persisted room chat messages.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(TeacherSessionMessage::class)->latest('id');
    }

    /**
     * Uploaded room files.
     */
    public function files(): HasMany
    {
        return $this->hasMany(TeacherSessionFile::class)->latest('id');
    }

    /**
     * Stored signaling events.
     */
    public function signals(): HasMany
    {
        return $this->hasMany(TeacherSessionSignal::class)->latest('id');
    }
}
