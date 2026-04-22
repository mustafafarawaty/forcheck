<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Real-time style chat message persisted for a session room.
 */
class TeacherSessionMessage extends Model
{
    /**
     * Mass assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'teacher_session_id',
        'sender_role',
        'sender_name',
        'message',
    ];

    /**
     * Related session.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(TeacherSession::class, 'teacher_session_id');
    }
}
