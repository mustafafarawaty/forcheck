<?php

use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherSession;
use App\Services\Wallet\WalletService;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Storage::fake('public');
    $this->walletService = app(WalletService::class);
});

describe('Student Deposits', function () {
    test('student can deposit money', function () {
        $student = Student::factory()->create();
        $proof = UploadedFile::fake()->image('proof.jpg');

        $transaction = $this->walletService->depositForStudent($student, 5000, $proof);

        expect($transaction)
            ->type->toBe('deposit')
            ->direction->toBe('credit')
            ->status->toBe('pending')
            ->amount->toEqual(5000.0);
        expect($transaction->student_id)->toBe($student->id);
        Storage::disk('public')->assertExists($transaction->proof_path);
    });

    test('deposit amount must be at least 50', function () {
        $student = Student::factory()->create();
        $proof = UploadedFile::fake()->image('proof.jpg');

        expect(fn () => $this->walletService->depositForStudent($student, 10, $proof))
            ->toThrow(ValidationException::class);
    });
});

describe('Student Withdrawals', function () {
    test('student can withdraw money', function () {
        $student = Student::factory()->withBalance(10000)->create();
        $proof = UploadedFile::fake()->image('proof.jpg');

        $transaction = $this->walletService->withdrawForStudent($student, 5000, $proof);

        expect($transaction)
            ->type->toBe('withdrawal')
            ->direction->toBe('debit')
            ->status->toBe('pending')
            ->amount->toEqual(5000.0);
        expect((float) $student->fresh()->balance)->toBe(5000.0);
    });

    test('student cannot withdraw more than balance', function () {
        $student = Student::factory()->withBalance(1000)->create();
        $proof = UploadedFile::fake()->image('proof.jpg');

        expect(fn () => $this->walletService->withdrawForStudent($student, 5000, $proof))
            ->toThrow(ValidationException::class);
    });
});

describe('Teacher Withdrawals', function () {
    test('teacher can withdraw money', function () {
        $teacher = Teacher::factory()->withBalance(20000)->create();
        $proof = UploadedFile::fake()->image('proof.jpg');

        $transaction = $this->walletService->withdrawForTeacher($teacher, 10000, $proof);

        expect($transaction)
            ->type->toBe('withdrawal')
            ->direction->toBe('debit')
            ->status->toBe('pending')
            ->amount->toEqual(10000.0);
        expect((float) $teacher->fresh()->balance)->toBe(10000.0);
    });

    test('teacher cannot withdraw more than balance', function () {
        $teacher = Teacher::factory()->withBalance(1000)->create();
        $proof = UploadedFile::fake()->image('proof.jpg');

        expect(fn () => $this->walletService->withdrawForTeacher($teacher, 5000, $proof))
            ->toThrow(ValidationException::class);
    });
});

describe('Transaction Approval', function () {
    test('admin can approve a pending deposit', function () {
        $student = Student::factory()->create();
        $proof = UploadedFile::fake()->image('proof.jpg');
        $transaction = $this->walletService->depositForStudent($student, 5000, $proof);

        $approved = $this->walletService->approveTransaction($transaction, 'تمت الموافقة');

        expect($approved->status)->toBe('completed');
        expect($approved->admin_note)->toBe('تمت الموافقة');
        expect((float) $student->fresh()->balance)->toBe(5000.0);
    });

    test('admin can approve a pending withdrawal', function () {
        $student = Student::factory()->withBalance(10000)->create();
        $proof = UploadedFile::fake()->image('proof.jpg');
        $transaction = $this->walletService->withdrawForStudent($student, 5000, $proof);

        $approved = $this->walletService->approveTransaction($transaction, 'تمت الموافقة');

        expect($approved->status)->toBe('completed');
    });

    test('admin can reject a pending transaction', function () {
        $student = Student::factory()->create();
        $proof = UploadedFile::fake()->image('proof.jpg');
        $transaction = $this->walletService->depositForStudent($student, 5000, $proof);

        $rejected = $this->walletService->rejectTransaction($transaction, 'مرفوض');

        expect($rejected->status)->toBe('cancelled');
        expect($rejected->admin_note)->toBe('مرفوض');
        expect((float) $student->fresh()->balance)->toBe(0.0);
    });

    test('cannot modify a transaction that is already in final status', function () {
        $student = Student::factory()->create();
        $proof = UploadedFile::fake()->image('proof.jpg');
        $transaction = $this->walletService->depositForStudent($student, 5000, $proof);
        $this->walletService->approveTransaction($transaction);

        expect(fn () => $this->walletService->rejectTransaction($transaction->fresh()))
            ->toThrow(ValidationException::class);
    });
});

describe('Admin Adjustments', function () {
    test('admin can credit student balance', function () {
        $student = Student::factory()->create();

        $adjustment = $this->walletService->createAdminAdjustment('student', $student->id, 'credit', 10000, 'مكافأة');

        expect($adjustment->status)->toBe('completed');
        expect($adjustment->type)->toBe('admin_adjustment');
        expect((float) $student->fresh()->balance)->toBe(10000.0);
    });

    test('admin can debit student balance', function () {
        $student = Student::factory()->withBalance(20000)->create();

        $adjustment = $this->walletService->createAdminAdjustment('student', $student->id, 'debit', 5000, 'غرامة');

        expect((float) $student->fresh()->balance)->toBe(15000.0);
    });

    test('admin can credit teacher balance', function () {
        $teacher = Teacher::factory()->create();

        $this->walletService->createAdminAdjustment('teacher', $teacher->id, 'credit', 15000, 'تعويض');

        expect((float) $teacher->fresh()->balance)->toBe(15000.0);
    });
});

describe('Session Amount Hold/Settle', function () {
    test('session amount can be held when both participants join', function () {
        $student = Student::factory()->withBalance(10000)->create();
        $teacher = Teacher::factory()->create();
        $session = TeacherSession::factory()
            ->create([
                'teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'price' => 5000,
                'teacher_earning_amount' => 4500,
                'admin_commission_percentage' => 10,
                'admin_commission_amount' => 500,
            ]);

        $held = $this->walletService->holdSessionAmount($session);

        expect($held->payment_status)->toBe('held');
        expect((float) $student->fresh()->balance)->toBe(5000.0);
    });

    test('session amount can be settled after completion', function () {
        $student = Student::factory()->withBalance(10000)->create();
        $teacher = Teacher::factory()->create();
        $session = TeacherSession::factory()
            ->create([
                'teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'price' => 5000,
                'teacher_earning_amount' => 4500,
                'admin_commission_percentage' => 10,
                'admin_commission_amount' => 500,
            ]);

        $this->walletService->holdSessionAmount($session);
        $settled = $this->walletService->settleSessionAmount($session->fresh());

        expect($settled->payment_status)->toBe('settled');
        expect((float) $student->fresh()->balance)->toBe(5000.0);
        expect((float) $teacher->fresh()->balance)->toBe(4500.0);
    });

    test('session amount can be refunded to student', function () {
        $student = Student::factory()->withBalance(10000)->create();
        $teacher = Teacher::factory()->create();
        $session = TeacherSession::factory()
            ->create([
                'teacher_id' => $teacher->id,
                'student_id' => $student->id,
                'price' => 5000,
                'teacher_earning_amount' => 4500,
                'admin_commission_percentage' => 10,
                'admin_commission_amount' => 500,
            ]);

        $this->walletService->holdSessionAmount($session);
        $refunded = $this->walletService->refundHeldSessionAmount($session->fresh(), 'تم الإلغاء');

        expect($refunded->payment_status)->toBe('refunded');
        expect((float) $student->fresh()->balance)->toBe(10000.0);
    });

    test('cannot hold session if student has insufficient balance', function () {
        $student = Student::factory()->withBalance(1000)->create();
        $session = TeacherSession::factory()
            ->create([
                'student_id' => $student->id,
                'price' => 5000,
            ]);

        expect(fn () => $this->walletService->holdSessionAmount($session))
            ->toThrow(ValidationException::class);
    });
});

describe('Transaction Cancellation', function () {
    test('student can cancel a pending deposit', function () {
        $student = Student::factory()->create();
        $proof = UploadedFile::fake()->image('proof.jpg');
        $transaction = $this->walletService->depositForStudent($student, 5000, $proof);

        $cancelled = $this->walletService->cancelPendingTransaction($transaction);

        expect($cancelled->status)->toBe('cancelled');
    });

    test('cannot cancel a completed transaction', function () {
        $student = Student::factory()->create();
        $proof = UploadedFile::fake()->image('proof.jpg');
        $transaction = $this->walletService->depositForStudent($student, 5000, $proof);
        $this->walletService->approveTransaction($transaction);

        expect(fn () => $this->walletService->cancelPendingTransaction($transaction->fresh()))
            ->toThrow(ValidationException::class);
    });
});

describe('Pro-Rata Session Settlement', function () {
    test('student cancellation pays teacher full earned amount minus commission', function () {
        $student = Student::factory()->withBalance(10000)->create();
        $teacher = Teacher::factory()->create();
        $session = TeacherSession::factory()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'price' => 10000,
            'duration_hours' => 1,
            'admin_commission_percentage' => 10,
            'started_at' => now()->subMinutes(25),
            'payment_status' => 'held',
        ]);

        $settled = $this->walletService->proRataSettleSession($session, 'student');

        expect($settled->payment_status)->toBe('settled');
        expect((float) $teacher->fresh()->balance)->toBeGreaterThan(0);
        expect((float) $student->fresh()->balance)->toBeGreaterThan(0);
    });

    test('teacher cancellation applies 1.5x penalty', function () {
        $student = Student::factory()->withBalance(10000)->create();
        $teacher = Teacher::factory()->create();
        $session = TeacherSession::factory()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'price' => 10000,
            'duration_hours' => 1,
            'admin_commission_percentage' => 10,
            'started_at' => now()->subMinutes(25),
            'payment_status' => 'held',
        ]);

        $settledByStudent = $this->walletService->proRataSettleSession($session->fresh(), 'student');
        $teacherBalanceAfterStudent = (float) $teacher->fresh()->balance;

        $student2 = Student::factory()->withBalance(10000)->create();
        $teacher2 = Teacher::factory()->create();
        $session2 = TeacherSession::factory()->create([
            'teacher_id' => $teacher2->id,
            'student_id' => $student2->id,
            'price' => 10000,
            'duration_hours' => 1,
            'admin_commission_percentage' => 10,
            'started_at' => now()->subMinutes(25),
            'payment_status' => 'held',
        ]);

        $settledByTeacher = $this->walletService->proRataSettleSession($session2->fresh(), 'teacher');
        $teacherBalanceAfterTeacher = (float) $teacher2->fresh()->balance;

        expect($teacherBalanceAfterTeacher)->toBeLessThan($teacherBalanceAfterStudent);
    });

    test('free session settles without financial transactions', function () {
        $student = Student::factory()->withBalance(10000)->create();
        $teacher = Teacher::factory()->create();
        $session = TeacherSession::factory()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'price' => 0,
            'duration_hours' => 1,
            'admin_commission_percentage' => 10,
            'started_at' => now()->subMinutes(25),
            'payment_status' => 'held',
        ]);

        $settled = $this->walletService->proRataSettleSession($session, 'student');

        expect($settled->payment_status)->toBe('settled');
        expect((float) $teacher->fresh()->balance)->toBe(0.0);
    });

    test('pro-rata is idempotent for already settled sessions', function () {
        $student = Student::factory()->withBalance(10000)->create();
        $teacher = Teacher::factory()->create();
        $session = TeacherSession::factory()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'price' => 10000,
            'duration_hours' => 1,
            'admin_commission_percentage' => 10,
            'started_at' => now()->subMinutes(25),
            'payment_status' => 'settled',
        ]);

        $result = $this->walletService->proRataSettleSession($session, 'student');

        expect($result->payment_status)->toBe('settled');
    });

    test('14 minute partial session with 50 price and 10% commission - student cancelled', function () {
        $student = Student::factory()->withBalance(10000)->create();
        $teacher = Teacher::factory()->create();
        $session = TeacherSession::factory()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'price' => 50,
            'duration_hours' => 1,
            'admin_commission_percentage' => 10,
            'started_at' => now()->subMinutes(14),
            'payment_status' => 'pending',
        ]);

        $settled = $this->walletService->proRataSettleSession($session, 'student');

        expect($settled->payment_status)->toBe('settled');
        expect((float) $teacher->fresh()->balance)->toBe(10.50);
        expect((float) $student->fresh()->balance)->toBe(10000 - 50 + 39.50);
    });

    test('16 minute partial session with 50 price and 10% commission - student cancelled', function () {
        $student = Student::factory()->withBalance(10000)->create();
        $teacher = Teacher::factory()->create();
        $session = TeacherSession::factory()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'price' => 50,
            'duration_hours' => 1,
            'admin_commission_percentage' => 10,
            'started_at' => now()->subMinutes(16),
            'payment_status' => 'pending',
        ]);

        $settled = $this->walletService->proRataSettleSession($session, 'student');

        expect($settled->payment_status)->toBe('settled');
        expect((float) $teacher->fresh()->balance)->toBe(12.00);
        expect((float) $student->fresh()->balance)->toBe(10000 - 50 + 38.00);
    });

    test('14 minute session - teacher cancelled gets 1.5x penalty', function () {
        $student = Student::factory()->withBalance(10000)->create();
        $teacher = Teacher::factory()->create();
        $session = TeacherSession::factory()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'price' => 50,
            'duration_hours' => 1,
            'admin_commission_percentage' => 10,
            'started_at' => now()->subMinutes(14),
            'payment_status' => 'pending',
        ]);

        $settled = $this->walletService->proRataSettleSession($session, 'teacher');

        expect($settled->payment_status)->toBe('settled');
        expect((float) $teacher->fresh()->balance)->toBe(7.00);
        expect((float) $student->fresh()->balance)->toBe(10000 - 50 + 43.00);
    });

    test('pro-rata teacher earns less than student cancellation - penalty verified', function () {
        $student = Student::factory()->withBalance(10000)->create();
        $teacher = Teacher::factory()->create();
        $session = TeacherSession::factory()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'price' => 50,
            'duration_hours' => 1,
            'admin_commission_percentage' => 10,
            'started_at' => now()->subMinutes(14),
            'payment_status' => 'pending',
        ]);

        $this->walletService->proRataSettleSession($session, 'student');
        $teacherBalanceStudent = (float) $teacher->fresh()->balance;

        $student2 = Student::factory()->withBalance(10000)->create();
        $teacher2 = Teacher::factory()->create();
        $session2 = TeacherSession::factory()->create([
            'teacher_id' => $teacher2->id,
            'student_id' => $student2->id,
            'price' => 50,
            'duration_hours' => 1,
            'admin_commission_percentage' => 10,
            'started_at' => now()->subMinutes(14),
            'payment_status' => 'pending',
        ]);

        $this->walletService->proRataSettleSession($session2, 'teacher');
        $teacherBalanceTeacher = (float) $teacher2->fresh()->balance;

        expect($teacherBalanceTeacher)->toBeLessThan($teacherBalanceStudent);
    });

    test('student must not be charged more than pro-rata share', function () {
        $student = Student::factory()->withBalance(10000)->create();
        $teacher = Teacher::factory()->create();
        $session = TeacherSession::factory()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'price' => 50,
            'duration_hours' => 1,
            'admin_commission_percentage' => 10,
            'started_at' => now()->subMinutes(14),
            'payment_status' => 'pending',
        ]);

        $this->walletService->proRataSettleSession($session, 'student');

        expect((float) $student->fresh()->balance)->toBe(9989.50);
    });
});
