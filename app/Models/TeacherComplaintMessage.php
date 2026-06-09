<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherComplaintMessage extends Model
{
    protected $fillable = [
        'teacher_complaint_id',
        'sender_role',
        'sender_name',
        'message',
    ];

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(TeacherComplaint::class, 'teacher_complaint_id');
    }
}
