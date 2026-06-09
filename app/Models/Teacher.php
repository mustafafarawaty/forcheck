<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BuildsPublicStorageUrls;

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
 * @property float $balance
 * @property Collection<int, TeacherSubject> $subjects
 * @property Collection<int, TeacherAvailability> $availabilities
 * @property Collection<int, TeacherSession> $sessions
 * @property Collection<int, TeacherComplaint> $complaints
 * @property Collection<int, TeacherLiveRequest> $liveRequests
 * @property Collection<int, WalletTransaction> $walletTransactions
 */
class Teacher extends Model
{
    use BuildsPublicStorageUrls;
    use HasFactory;
    use SoftDeletes;

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
        'approval_status',
        'approved_at',
        'disabled_at',
        'disabled_reason',
        'balance',
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
            'approved_at' => 'datetime',
            'disabled_at' => 'datetime',
            'balance' => 'decimal:2',
        ];
    }

    public function isDisabled(): bool
    {
        return $this->disabled_at !== null;
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    /**
     * Public avatar URL when available.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        return $this->publicStorageUrl($this->avatar_path);
    }

    public function getCertificateUrlAttribute(): ?string
    {
        if (! $this->certificate_path) {
            return null;
        }

        return $this->publicStorageUrl($this->certificate_path);
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

    /**
     * Teacher wallet movement history.
     */
    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class)->latest('id');
    }
}
