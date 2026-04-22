<?php

namespace App\Repositories;

use App\Models\Teacher;

/**
 * Database access for teacher accounts.
 */
class TeacherRepository
{
    /**
     * Find a teacher by phone number.
     */
    public function findByPhone(string $phone): ?Teacher
    {
        return Teacher::query()->where('phone', $phone)->first();
    }

    /**
     * Create a teacher.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Teacher
    {
        return Teacher::query()->create($attributes);
    }

    /**
     * Update a teacher account.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(Teacher $teacher, array $attributes): Teacher
    {
        $teacher->update($attributes);

        return $teacher->refresh();
    }
}
