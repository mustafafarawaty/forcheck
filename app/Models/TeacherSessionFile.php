<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BuildsPublicStorageUrls;

/**
 * Uploaded file attached to a session.
 */
class TeacherSessionFile extends Model
{
    use BuildsPublicStorageUrls;

    /**
     * Mass assignable attributes.
     *
     * @var list<string>
     */
    protected $fillable = [
        'teacher_session_id',
        'uploader_role',
        'uploader_name',
        'original_name',
        'file_path',
        'mime_type',
        'size',
    ];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    /**
     * Public URL for the uploaded file.
     */
    public function getFileUrlAttribute(): string
    {
        return $this->publicStorageUrl($this->file_path);
    }

    /**
     * Related session.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(TeacherSession::class, 'teacher_session_id');
    }
}
