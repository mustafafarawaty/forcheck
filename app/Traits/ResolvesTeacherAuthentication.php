<?php

namespace App\Traits;

use App\Models\Teacher;
use Illuminate\Http\Request;

/**
 * Shared helpers for resolving the authenticated teacher from session.
 */
trait ResolvesTeacherAuthentication
{
    /**
     * Resolve the authenticated teacher instance from request session.
     */
    protected function authenticatedTeacher(Request $request): Teacher
    {
        /** @var Teacher $teacher */
        $teacher = $request->attributes->get('authenticatedTeacher');

        return $teacher;
    }
}
