<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Persisted WebRTC signaling message used by polling clients.
 */
class TeacherSessionSignal extends Model
{
    /**
     * Mass assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'teacher_session_id',
        'sender_role',
        'signal_type',
        'payload',
    ];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    /**
     * Related session.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(TeacherSession::class, 'teacher_session_id');
    }
}
