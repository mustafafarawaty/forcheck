<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * Teacher account with profile, education stage and owned records.
 *
 * @property int $id
 * @property string $name
 * @property string $phone
 * @property string $password
 * @property string|null $specialization
 * @property string $education_stage
 * @property array<int, string> $education_levels
 * @property string $certificate_path
 * @property string|null $avatar_path
 * @property float $rating_average
 * @property int $ratings_count
 * @property string|null $about
 * @property bool $is_accepting_instant_sessions
 * @property Collection<int, TeacherSubject> $subjects
 * @property Collection<int, TeacherAvailability> $availabilities
 * @property Collection<int, TeacherSession> $sessions
 * @property Collection<int, TeacherComplaint> $complaints
 * @property Collection<int, TeacherLiveRequest> $liveRequests
 */
class Teacher extends Model
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
        'specialization',
        'education_stage',
        'education_levels',
        'certificate_path',
        'avatar_path',
        'rating_average',
        'ratings_count',
        'about',
        'is_accepting_instant_sessions',
    ];

    /**
     * Hidden attributes for serialization.
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
            'education_stage' => 'string',
            'education_levels' => 'array',
            'password' => 'hashed',
            'rating_average' => 'decimal:2',
            'ratings_count' => 'integer',
            'is_accepting_instant_sessions' => 'boolean',
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
     * Teacher subjects.
     */
    public function subjects(): HasMany
    {
        return $this->hasMany(TeacherSubject::class);
    }

    /**
     * Teacher availabilities.
     */
    public function availabilities(): HasMany
    {
        return $this->hasMany(TeacherAvailability::class);
    }

    /**
     * Teacher sessions.
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(TeacherSession::class);
    }

    /**
     * Teacher complaints.
     */
    public function complaints(): HasMany
    {
        return $this->hasMany(TeacherComplaint::class);
    }

    /**
     * Instant live requests.
     */
    public function liveRequests(): HasMany
    {
        return $this->hasMany(TeacherLiveRequest::class);
    }
}
