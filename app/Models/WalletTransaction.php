<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BuildsPublicStorageUrls;

/**
 * Unified wallet ledger entry for students and teachers.
 */
class WalletTransaction extends Model
{
    use BuildsPublicStorageUrls;

    /**
     * Mass assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'student_id',
        'teacher_id',
        'teacher_session_id',
        'type',
        'direction',
        'status',
        'amount',
        'proof_path',
        'admin_attachment_path',
        'description',
        'admin_note',
        'reviewed_at',
        'student_read_at',
        'teacher_read_at',
        'meta',
    ];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'reviewed_at' => 'datetime',
            'student_read_at' => 'datetime',
            'teacher_read_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /**
     * Owning student, when present.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Owning teacher, when present.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Related teaching session, when present.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(TeacherSession::class, 'teacher_session_id');
    }

    /**
     * Public proof URL when an image was uploaded.
     */
    public function getProofUrlAttribute(): ?string
    {
        if (! $this->proof_path) {
            return null;
        }

        return $this->publicStorageUrl($this->proof_path);
    }

    public function getAdminAttachmentUrlAttribute(): ?string
    {
        return $this->publicStorageUrl($this->admin_attachment_path);
    }

    public function getOwnerNameAttribute(): string
    {
        return $this->student?->name ?? $this->teacher?->name ?? 'غير محدد';
    }

    public function getOwnerRoleAttribute(): string
    {
        return $this->student_id ? 'student' : ($this->teacher_id ? 'teacher' : 'system');
    }
}
