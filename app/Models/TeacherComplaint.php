<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BuildsPublicStorageUrls;

/**
 * Complaint submitted by a session participant to platform administration.
 */
class TeacherComplaint extends Model
{
    use BuildsPublicStorageUrls;

    /**
     * Mass assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'teacher_id',
        'student_id',
        'teacher_session_id',
        'wallet_transaction_id',
        'title',
        'description',
        'attachment_path',
        'submitted_by',
        'status',
        'submitted_at',
        'student_read_at',
        'teacher_read_at',
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
            'student_read_at' => 'datetime',
            'teacher_read_at' => 'datetime',
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

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->publicStorageUrl($this->attachment_path);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TeacherComplaintMessage::class)->latest('id');
    }

    public function messagesChronological(): HasMany
    {
        return $this->hasMany(TeacherComplaintMessage::class)->oldest('id');
    }

    public function isClosed(): bool
    {
        return in_array($this->status, ['closed', 'completed'], true);
    }
}
