<?php

namespace App\Services\Teacher;

use App\Models\Teacher;
use App\Repositories\TeacherRepository;
use Carbon\CarbonImmutable;
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
     * Register a teacher and seed a minimal working workspace.
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

            /** @var Teacher $teacher */
            $teacher = $this->teachers->create($data);

            $teacher->availabilities()->create([
                'teacher_subject_id' => null,
                'day_of_week' => 1,
                'starts_at' => '17:00',
                'ends_at' => '19:00',
                'notes' => 'موعد مبدئي قابل للتعديل.',
            ]);

            $upcomingAt = CarbonImmutable::now()->addDay()->setHour(18)->setMinute(0);
            $completedAt = CarbonImmutable::now()->subDay()->setHour(17)->setMinute(0);

            $completedSession = $teacher->sessions()->create([
                'teacher_subject_id' => null,
                'student_name' => 'طالب تجريبي',
                'scheduled_at' => $completedAt,
                'ended_at' => $completedAt->addMinutes(55),
                'status' => 'completed',
                'price' => 120000,
                'notes' => 'جلسة مراجعة سريعة للانطلاقة.',
                'recording_url' => '#',
                'chat_excerpt' => 'تمت مراجعة النقاط الأساسية وإرسال ملخص بعد الجلسة.',
            ]);

            $teacher->sessions()->create([
                'teacher_subject_id' => null,
                'student_name' => 'طالب جديد',
                'scheduled_at' => $upcomingAt,
                'status' => 'upcoming',
                'price' => 140000,
                'notes' => 'جلسة قادمة مع إمكانية الإلغاء عند الحاجة.',
            ]);

            $teacher->complaints()->create([
                'teacher_session_id' => $completedSession->id,
                'title' => 'تأخر في مزامنة الصوت',
                'description' => 'ظهر تأخير بسيط بين الصوت والعرض أثناء بداية الجلسة.',
                'status' => 'pending',
                'submitted_at' => now(),
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
