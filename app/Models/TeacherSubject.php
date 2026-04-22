<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Subject taught by a teacher within allowed education levels.
 */
class TeacherSubject extends Model
{
    /**
     * Mass assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = ['teacher_id', 'name', 'level', 'hourly_rate_syp'];

    /**
     * Owning teacher.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Related availabilities.
     */
    public function availabilities(): HasMany
    {
        return $this->hasMany(TeacherAvailability::class);
    }

    /**
     * Related sessions.
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(TeacherSession::class);
    }

    /**
     * Related live requests.
     */
    public function liveRequests(): HasMany
    {
        return $this->hasMany(TeacherLiveRequest::class);
    }
}
