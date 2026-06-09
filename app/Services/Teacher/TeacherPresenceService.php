<?php

namespace App\Services\Teacher;

use App\Models\Teacher;
use Illuminate\Support\Facades\Cache;

class TeacherPresenceService
{
    private const ONLINE_TTL_SECONDS = 45;

    public function markOnline(Teacher $teacher): void
    {
        Cache::put($this->cacheKey($teacher), true, now()->addSeconds(self::ONLINE_TTL_SECONDS));
    }

    public function markOffline(Teacher $teacher): Teacher
    {
        Cache::forget($this->cacheKey($teacher));

        if ($teacher->is_accepting_instant_sessions) {
            $teacher->update(['is_accepting_instant_sessions' => false]);
        }

        return $teacher->fresh();
    }

    public function isOnline(Teacher $teacher): bool
    {
        $isOnline = Cache::has($this->cacheKey($teacher));

        if (! $isOnline && $teacher->is_accepting_instant_sessions) {
            $teacher->update(['is_accepting_instant_sessions' => false]);
        }

        return $isOnline;
    }

    private function cacheKey(Teacher $teacher): string
    {
        return "teacher:{$teacher->id}:online";
    }
}
