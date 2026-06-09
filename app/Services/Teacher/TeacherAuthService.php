<?php

namespace App\Services\Teacher;

use App\Models\Teacher;
use App\Repositories\TeacherRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Handles teacher registration and login flows.
 */
class TeacherAuthService
{
    public function __construct(
        private readonly TeacherRepository $teachers,
    ) {
    }

    /**
     * Register a teacher account without seeding fake sessions, wallets or complaints.
     *
     * @param  array<string, mixed>  $data
     */
    public function register(array $data): Teacher
    {
        return DB::transaction(function () use ($data): Teacher {
            $data['education_levels'] = $data['education_stage'] === 'university'
                ? ['primary', 'middle', 'secondary', 'university']
                : ['primary', 'middle', 'secondary'];
            $data['specialization'] = null;
            $data['approval_status'] = 'pending';

            /** @var Teacher $teacher */
            $teacher = $this->teachers->create($data);

            $teacher->availabilities()->create([
                'teacher_subject_id' => null,
                'day_of_week' => 1,
                'starts_at' => '17:00',
                'ends_at' => '19:00',
                'notes' => 'موعد مبدئي قابل للتعديل.',
            ]);

            return $teacher;
        });
    }

    /**
     * Attempt login by phone and password.
     */
    public function attemptLogin(string $phone, string $password): ?Teacher
    {
        $teacher = $this->teachers->findByPhone($phone);

        if (! $teacher || ! Hash::check($password, $teacher->password)) {
            return null;
        }

        return $teacher;
    }
}
