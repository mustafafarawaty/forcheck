<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BuildsPublicStorageUrls;

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
 * @property float $balance
 * @property Collection<int, TeacherSession> $sessions
 * @property Collection<int, TeacherLiveRequest> $liveRequests
     * @property Collection<int, WalletTransaction> $walletTransactions
 * @property Collection<int, TeacherComplaint> $complaints
 */
class Student extends Model
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
        'study_level',
        'about',
        'avatar_path',
        'disabled_at',
        'disabled_reason',
        'balance',
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
            'disabled_at' => 'datetime',
            'balance' => 'decimal:2',
        ];
    }

    public function isDisabled(): bool
    {
        return $this->disabled_at !== null;
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

    /**
     * Student wallet movement history.
     */
    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class)->latest('id');
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(TeacherComplaint::class)->latest('id');
    }
}
