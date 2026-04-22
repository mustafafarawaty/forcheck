<?php

namespace App\Repositories;

use App\Models\Teacher;
use App\Models\TeacherLiveRequest;
use Illuminate\Database\Eloquent\Collection;

/**
 * Query access for teacher live requests.
 */
class TeacherLiveRequestRepository
{
    /**
     * Create a live request.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): TeacherLiveRequest
    {
        return TeacherLiveRequest::query()->create($attributes);
    }

    /**
     * Pending requests for teacher.
     *
     * @return Collection<int, TeacherLiveRequest>
     */
    public function pendingForTeacher(Teacher $teacher): Collection
    {
        return $teacher->liveRequests()
            ->with(['student', 'subject'])
            ->where('status', 'pending')
            ->latest('requested_at')
            ->get();
    }

    /**
     * Resolve an owned pending request or fail.
     */
    public function ownedPendingOrFail(Teacher $teacher, int $requestId): TeacherLiveRequest
    {
        return $teacher->liveRequests()
            ->with(['student', 'subject'])
            ->where('status', 'pending')
            ->findOrFail($requestId);
    }
}
