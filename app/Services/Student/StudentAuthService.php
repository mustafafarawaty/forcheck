<?php

namespace App\Services\Student;

use App\Models\Student;
use App\Repositories\StudentRepository;
use Illuminate\Support\Facades\Hash;

/**
 * Handles student registration and login flows.
 */
class StudentAuthService
{
    public function __construct(
        private readonly StudentRepository $students,
    ) {
    }

    /**
     * Register a student account.
     *
     * @param  array<string, mixed>  $data
     */
    public function register(array $data): Student
    {
        return $this->students->create($data);
    }

    /**
     * Attempt login by mobile number and password.
     */
    public function attemptLogin(string $phone, string $password): ?Student
    {
        $student = $this->students->findByPhone($phone);

        if (! $student || ! Hash::check($password, $student->password)) {
            return null;
        }

        return $student;
    }
}
