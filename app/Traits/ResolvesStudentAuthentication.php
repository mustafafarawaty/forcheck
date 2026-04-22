<?php

namespace App\Traits;

use App\Models\Student;
use Illuminate\Http\Request;

/**
 * Shared helpers for resolving authenticated student from session.
 */
trait ResolvesStudentAuthentication
{
    /**
     * Resolve the authenticated student instance.
     */
    protected function authenticatedStudent(Request $request): Student
    {
        /** @var Student $student */
        $student = $request->attributes->get('authenticatedStudent');

        return $student;
    }
}
