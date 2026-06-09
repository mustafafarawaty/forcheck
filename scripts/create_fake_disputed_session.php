<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

$teacherModel = App\Models\Teacher::class;
$studentModel = App\Models\Student::class;
$sessionModel = App\Models\TeacherSession::class;
$txModel = App\Models\WalletTransaction::class;

try {
    DB::transaction(function () use ($teacherModel, $studentModel, $sessionModel, $txModel) {
        $teacher = $teacherModel::first();
        if (! $teacher) {
            $teacher = $teacherModel::create([
                'name' => 'Fake Teacher',
                'phone' => '900' . rand(100000, 999999),
                'password' => 'secret',
                'education_stage' => 'other',
                'balance' => 0,
            ]);
            echo "Created teacher id={$teacher->id}\n";
        } else {
            echo "Using teacher id={$teacher->id}\n";
        }

        $student = $studentModel::first();
        if (! $student) {
            $student = $studentModel::create([
                'name' => 'Fake Student',
                'phone' => '700' . rand(100000, 999999),
                'password' => 'secret',
                'study_level' => 'other',
                'balance' => 200.00,
            ]);
            echo "Created student id={$student->id}\n";
        } else {
            echo "Using student id={$student->id}\n";
        }

        $startedAt = Carbon::now()->subMinutes(30);
        $endedAt = (clone $startedAt)->addMinutes(22);

        $gross = 100.00;
        $teacherEarning = 70.00;
        $adminCommissionAmount = round($gross - $teacherEarning, 2);

        $session = $sessionModel::create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'student_name' => $student->name,
            'scheduled_at' => $startedAt->copy()->subMinutes(5),
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'status' => 'cancelled',
            'duration_hours' => 1,
            'price' => $gross,
            'admin_commission_percentage' => round($adminCommissionAmount / $gross * 100, 2),
            'admin_commission_amount' => $adminCommissionAmount,
            'teacher_earning_amount' => $teacherEarning,
            'payment_status' => 'disputed',
            'wallet_held_at' => $startedAt,
            'disputed_at' => Carbon::now(),
            'cancellation_reason' => 'Fake: cancelled بعد أكثر من 20 دقيقة ومعلقة لدى الإدارة',
        ]);

        echo "Created session id={$session->id} status={$session->status} payment_status={$session->payment_status}\n";

        $student->decrement('balance', $gross);

        $tx = $txModel::create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'teacher_session_id' => $session->id,
            'type' => 'session_cancelled_held',
            'direction' => 'debit',
            'status' => 'disputed',
            'amount' => $gross,
            'description' => 'قيمة الجلسة الملغاة بقيت معلقة بانتظار مراجعة الإدارة.',
            'meta' => ['reason' => 'Fake canceled after 25 minutes', 'gross_amount' => $gross, 'admin_commission_amount' => $adminCommissionAmount],
        ]);

        echo "Created transaction: {$tx->id} (held)\n";
    });
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Done.\n";
