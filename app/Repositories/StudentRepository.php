<?php

namespace App\Repositories;

use App\Models\Student;

/**
 * Database access for students.
 */
class StudentRepository
{
    /**
     * Find a student by phone number.
     */
    public function findByPhone(string $phone): ?Student
    {
        return Student::query()->where('phone', $phone)->first();
    }

    /**
     * Create a student account.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Student
    {
        return Student::query()->create($attributes);
    }

    /**
     * Update a student account.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(Student $student, array $attributes): Student
    {
        $student->update($attributes);

        return $student->refresh();
    }
}
