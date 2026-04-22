<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * Student account with study level and related sessions.
 *
 * @property int $id
 * @property string $name
 * @property string $phone
 * @property string $password
 * @property string $study_level
 * @property string|null $about
 * @property string|null $avatar_path
 * @property Collection<int, TeacherSession> $sessions
 * @property Collection<int, TeacherLiveRequest> $liveRequests
 */
class Student extends Model
{
    use HasFactory;

    /**
     * Mass assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'phone',
        'password',
        'study_level',
        'about',
        'avatar_path',
    ];

    /**
     * Hidden attributes.
     *
     * @var list<string>
     */
    protected $hidden = ['password'];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Public avatar URL when available.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        return Storage::disk('public')->url($this->avatar_path);
    }

    /**
     * Student sessions.
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(TeacherSession::class);
    }

    /**
     * Student instant session requests.
     */
    public function liveRequests(): HasMany
    {
        return $this->hasMany(TeacherLiveRequest::class);
    }
}
