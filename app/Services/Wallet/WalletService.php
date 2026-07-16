<?php

namespace App\Services\Wallet;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherSession;
use App\Models\WalletTransaction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Handles wallet balances, ledger entries and session settlement.
 */
class WalletService
{
    public const MIN_TRANSACTION_AMOUNT = 50;

    public function studentTransactions(Student $student)
    {
        return $student->walletTransactions()->with('session');
    }

    public function teacherTransactions(Teacher $teacher)
    {
        return $teacher->walletTransactions()->with('session');
    }

    public function depositForStudent(Student $student, float $amount, UploadedFile $proof): WalletTransaction
    {
        $this->ensureMinimumAmount($amount, 'amount');

        return DB::transaction(function () use ($student, $amount, $proof): WalletTransaction {
            $path = $proof->store("wallet/students/{$student->id}/deposits", 'public');

            return WalletTransaction::query()->create([
                'student_id' => $student->id,
                'type' => 'deposit',
                'direction' => 'credit',
                'status' => 'pending',
                'amount' => $amount,
                'proof_path' => $path,
                'student_read_at' => now(),
                'description' => 'شحن رصيد الطالب.',
            ]);
        });
    }

    public function withdrawForStudent(Student $student, float $amount, UploadedFile $proof): WalletTransaction
    {
        return DB::transaction(function () use ($student, $amount, $proof): WalletTransaction {
            $this->ensureWithdrawableAmount((float) $student->balance, $amount, 'amount');
            $student->decrement('balance', $amount);

            $path = $proof->store("wallet/students/{$student->id}/withdrawals", 'public');

            return WalletTransaction::query()->create([
                'student_id' => $student->id,
                'type' => 'withdrawal',
                'direction' => 'debit',
                'status' => 'pending',
                'amount' => $amount,
                'proof_path' => $path,
                'student_read_at' => now(),
                'description' => 'سحب من رصيد الطالب.',
            ]);
        });
    }

    public function withdrawForTeacher(Teacher $teacher, float $amount, UploadedFile $proof): WalletTransaction
    {
        return DB::transaction(function () use ($teacher, $amount, $proof): WalletTransaction {
            $this->ensureWithdrawableAmount((float) $teacher->balance, $amount, 'amount');
            $teacher->decrement('balance', $amount);

            $path = $proof->store("wallet/teachers/{$teacher->id}/withdrawals", 'public');

            return WalletTransaction::query()->create([
                'teacher_id' => $teacher->id,
                'type' => 'withdrawal',
                'direction' => 'debit',
                'status' => 'pending',
                'amount' => $amount,
                'proof_path' => $path,
                'description' => 'سحب من رصيد الأستاذ.',
            ]);
        });
    }

    public function approveTransaction(WalletTransaction $transaction, ?string $adminNote = null): WalletTransaction
    {
        return $this->updateTransaction($transaction, (float) $transaction->amount, 'completed', $adminNote);
    }

    public function rejectTransaction(WalletTransaction $transaction, ?string $adminNote = null): WalletTransaction
    {
        return $this->updateTransaction($transaction, (float) $transaction->amount, 'cancelled', $adminNote);
    }

    public function updateTransaction(WalletTransaction $transaction, float $amount, string $status, ?string $adminNote = null, ?UploadedFile $adminAttachment = null): WalletTransaction
    {
        return DB::transaction(function () use ($transaction, $amount, $status, $adminNote, $adminAttachment): WalletTransaction {
            if ($amount < 0) {
                throw ValidationException::withMessages([
                    'amount' => 'قيمة المبلغ يجب أن تكون صفراً أو أكثر.',
                ]);
            }

            $transaction = WalletTransaction::query()
                ->with(['student', 'teacher', 'session.student', 'session.teacher'])
                ->lockForUpdate()
                ->findOrFail($transaction->id);

            if ($this->isFinalStatus($transaction->status)) {
                throw ValidationException::withMessages([
                    'status' => 'لا يمكن تعديل حالة حركة رصيد منتهية.',
                ]);
            }

            if ($this->isDisputedSessionTransaction($transaction)) {
                return $this->updateDisputedSessionTransaction($transaction, $amount, $status, $adminNote, $adminAttachment);
            }

            $this->syncTransactionBalance($transaction, $amount, $status);

            $transaction->fill([
                'amount' => $amount,
                'status' => $status,
                'admin_note' => $adminNote,
                'reviewed_at' => in_array($status, ['completed', 'cancelled', 'refunded'], true) ? now() : null,
                'student_read_at' => $transaction->student_id ? null : $transaction->student_read_at,
            ]);

            if ($adminAttachment) {
                $ownerFolder = $transaction->student_id
                    ? "students/{$transaction->student_id}"
                    : ($transaction->teacher_id ? "teachers/{$transaction->teacher_id}" : 'system');

                $transaction->admin_attachment_path = $adminAttachment->store("wallet/admin/{$ownerFolder}", 'public');
            }

            $transaction->save();
            $this->syncSessionPaymentStatus($transaction, $status);

            return $transaction->fresh(['student', 'teacher', 'session']);
        });
    }

    public function createAdminAdjustment(string $ownerType, int $ownerId, string $direction, float $amount, ?string $adminNote = null): WalletTransaction
    {
        $this->ensureMinimumAmount($amount, 'amount');

        return DB::transaction(function () use ($ownerType, $ownerId, $direction, $amount, $adminNote): WalletTransaction {
            $owner = $ownerType === 'student'
                ? Student::query()->lockForUpdate()->findOrFail($ownerId)
                : Teacher::query()->lockForUpdate()->findOrFail($ownerId);

            if ($direction === 'credit') {
                $owner->increment('balance', $amount);
            } else {
                $this->ensureWithdrawableAmount((float) $owner->balance, $amount, 'amount');
                $owner->decrement('balance', $amount);
            }

            return WalletTransaction::query()->create([
                'student_id' => $ownerType === 'student' ? $owner->id : null,
                'teacher_id' => $ownerType === 'teacher' ? $owner->id : null,
                'type' => 'admin_adjustment',
                'direction' => $direction,
                'status' => 'completed',
                'amount' => $amount,
                'description' => $direction === 'credit' ? 'إضافة رصيد من الإدارة.' : 'سحب رصيد من الإدارة.',
                'admin_note' => $adminNote,
                'reviewed_at' => now(),
            ]);
        });
    }

    public function cancelPendingTransaction(WalletTransaction $transaction): WalletTransaction
    {
        return DB::transaction(function () use ($transaction): WalletTransaction {
            $transaction = WalletTransaction::query()
                ->with(['student', 'teacher'])
                ->lockForUpdate()
                ->findOrFail($transaction->id);

            if ($transaction->status !== 'pending' || ! in_array($transaction->type, ['deposit', 'withdrawal'], true)) {
                throw ValidationException::withMessages([
                    'transaction' => 'لا يمكن إلغاء هذه الحركة بعد بدء معالجتها.',
                ]);
            }

            $this->syncTransactionBalance($transaction, (float) $transaction->amount, 'cancelled');

            $transaction->update([
                'status' => 'cancelled',
                'admin_note' => 'تم إلغاء الطلب من قبل صاحب الحساب.',
                'reviewed_at' => now(),
            ]);

            return $transaction->fresh(['student', 'teacher', 'session']);
        });
    }

    public function ensureStudentCanAfford(Student $student, float $amount, string $field = 'subject_name'): void
    {
        if ((float) $student->balance >= $amount) {
            return;
        }

        throw ValidationException::withMessages([
            $field => 'رصيدك الحالي لا يكفي لحجز هذه الجلسة.',
        ]);
    }

    /**
     * Deduct the gross amount from the student when both participants really join.
     * This is an internal hold and does not create a wallet ledger entry.
     */
    public function holdSessionAmount(TeacherSession $session): TeacherSession
    {
        return DB::transaction(function () use ($session): TeacherSession {
            $session = TeacherSession::query()
                ->with(['student', 'teacher'])
                ->lockForUpdate()
                ->findOrFail($session->id);

            if (in_array($session->payment_status, ['held', 'settled', 'disputed'], true)) {
                return $session;
            }

            if (! $session->student || ! $session->teacher) {
                return $session;
            }

            $amount = (float) $session->price;
            $this->ensureStudentCanAfford($session->student, $amount, 'session');
            $session->student->decrement('balance', $amount);

            WalletTransaction::query()
                ->where('teacher_session_id', $session->id)
                ->whereIn('type', ['session_hold', 'session_pending'])
                ->delete();

            WalletTransaction::query()->create([
                'student_id' => $session->student_id,
                'teacher_session_id' => $session->id,
                'type' => 'session_hold',
                'direction' => 'debit',
                'status' => 'held',
                'amount' => $amount,
                'description' => 'تم تعليق مبلغ الجلسة في المحفظة.',
                'meta' => [
                    'booking_type' => $session->booking_type,
                ],
            ]);

            $session->update([
                'payment_status' => 'held',
                'wallet_held_at' => now(),
            ]);

            return $session->fresh(['student', 'teacher', 'subject']);
        });
    }

    public function settleSessionAmount(TeacherSession $session): TeacherSession
    {
        return DB::transaction(function () use ($session): TeacherSession {
            $session = TeacherSession::query()
                ->with(['student', 'teacher'])
                ->lockForUpdate()
                ->findOrFail($session->id);

            if ($session->payment_status === 'settled') {
                return $session;
            }

            if (! $session->teacher) {
                return $session;
            }

            if ($session->payment_status !== 'held') {
                $session = $this->holdSessionAmount($session);
                $session = TeacherSession::query()->with(['student', 'teacher'])->lockForUpdate()->findOrFail($session->id);
            }

            $grossAmount = (float) $session->price;
            $teacherEarning = $this->teacherEarningAmount($session);
            $adminCommission = max(0, round($grossAmount - $teacherEarning, 2));

            $session->teacher->increment('balance', $teacherEarning);

            WalletTransaction::query()->updateOrCreate(
                [
                    'student_id' => $session->student_id,
                    'teacher_session_id' => $session->id,
                    'type' => 'session_charge',
                ],
                [
                    'student_id' => $session->student_id,
                    'direction' => 'debit',
                    'status' => 'completed',
                    'amount' => $grossAmount,
                    'description' => 'تم خصم قيمة الجلسة بعد اكتمالها.',
                    'meta' => [
                        'admin_commission_percentage' => (float) $session->admin_commission_percentage,
                        'admin_commission_amount' => $adminCommission,
                        'teacher_earning_amount' => $teacherEarning,
                    ],
                ]
            );

            WalletTransaction::query()->updateOrCreate(
                [
                    'teacher_id' => $session->teacher_id,
                    'teacher_session_id' => $session->id,
                    'type' => 'session_earning',
                ],
                [
                    'teacher_id' => $session->teacher_id,
                    'direction' => 'credit',
                    'status' => 'completed',
                    'amount' => $teacherEarning,
                    'description' => 'تمت إضافة ربح الجلسة الصافي بعد خصم نسبة الإدارة.',
                    'meta' => [
                        'gross_amount' => $grossAmount,
                        'admin_commission_percentage' => (float) $session->admin_commission_percentage,
                        'admin_commission_amount' => $adminCommission,
                    ],
                ]
            );

            WalletTransaction::query()
                ->where('teacher_session_id', $session->id)
                ->whereIn('type', ['session_hold', 'session_pending'])
                ->delete();

            $session->update([
                'payment_status' => 'settled',
                'settled_at' => now(),
                'disputed_at' => null,
            ]);

            return $session->fresh(['student', 'teacher', 'subject']);
        });
    }

    public function markSessionAsDisputed(TeacherSession $session, ?string $reason = null): TeacherSession
    {
        return DB::transaction(function () use ($session, $reason): TeacherSession {
            $session = TeacherSession::query()
                ->with(['student', 'teacher'])
                ->lockForUpdate()
                ->findOrFail($session->id);

            if ($session->payment_status !== 'held') {
                $session = $this->holdSessionAmount($session);
                $session = TeacherSession::query()->with(['student', 'teacher'])->lockForUpdate()->findOrFail($session->id);
            }

            $grossAmount = (float) $session->price;
            $teacherEarning = $this->teacherEarningAmount($session);

            WalletTransaction::query()->updateOrCreate(
                [
                    'student_id' => $session->student_id,
                    'teacher_id' => $session->teacher_id,
                    'teacher_session_id' => $session->id,
                    'type' => 'session_cancelled_held',
                ],
                [
                    'student_id' => $session->student_id,
                    'teacher_id' => $session->teacher_id,
                    'direction' => 'debit',
                    'status' => 'disputed',
                    'amount' => $grossAmount,
                    'description' => 'قيمة الجلسة الملغاة بقيت معلقة بانتظار مراجعة الإدارة.',
                    'meta' => ['reason' => $reason],
                ]
            );

            $session->update([
                'payment_status' => 'disputed',
                'disputed_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            return $session->fresh(['student', 'teacher', 'subject']);
        });
    }

    /**
     * Release a held amount back to the student after a short cancellation.
     * The refund is silent, so short cancellations do not clutter wallet history.
     */
    public function refundHeldSessionAmount(TeacherSession $session, ?string $reason = null): TeacherSession
    {
        return DB::transaction(function () use ($session, $reason): TeacherSession {
            $session = TeacherSession::query()
                ->with(['student', 'teacher'])
                ->lockForUpdate()
                ->findOrFail($session->id);

            if ($session->payment_status === 'refunded') {
                return $session;
            }

            if ($session->payment_status !== 'held') {
                return $session;
            }

            if ($session->student) {
                $session->student->increment('balance', (float) $session->price);
            }

            WalletTransaction::query()
                ->where('teacher_session_id', $session->id)
                ->whereIn('type', ['session_hold', 'session_pending', 'session_refund'])
                ->delete();

            $session->update([
                'payment_status' => 'refunded',
                'settled_at' => null,
                'disputed_at' => null,
                'cancellation_reason' => $reason,
            ]);

            return $session->fresh(['student', 'teacher', 'subject']);
        });
    }

    /**
     * Settle a cancelled in-progress session on a pro-rata basis.
     *
     * The teacher earns a share proportional to elapsed time, minus admin commission.
     * If the teacher initiated the cancellation, their share is further reduced by 1.5×.
     * The student receives the remainder.
     */
    public function proRataSettleSession(TeacherSession $session, string $cancelledBy): TeacherSession
    {
        return DB::transaction(function () use ($session, $cancelledBy): TeacherSession {
            $session = TeacherSession::query()
                ->with(['student', 'teacher'])
                ->lockForUpdate()
                ->findOrFail($session->id);

            if (in_array($session->payment_status, ['settled', 'refunded'], true)) {
                return $session;
            }

            if (! $session->teacher) {
                return $session;
            }

            if ($session->payment_status !== 'held') {
                $session = $this->holdSessionAmount($session);
                $session = TeacherSession::query()->with(['student', 'teacher'])->lockForUpdate()->findOrFail($session->id);
            }

            $grossAmount = (float) $session->price;

            if ($grossAmount <= 0) {
                $session->update([
                    'payment_status' => 'settled',
                    'settled_at' => now(),
                ]);

                return $session->fresh(['student', 'teacher', 'subject']);
            }

            $elapsedMinutes = max(1, (int) abs(now()->diffInSeconds($session->started_at)) / 60);
            $plannedMinutes = max(1, (int) abs($session->plannedEndAt()->diffInSeconds($session->started_at)) / 60);
            $ratePerMinute = $grossAmount / $plannedMinutes;
            $earnedAmount = round($ratePerMinute * $elapsedMinutes, 2);

            $adminCommissionPct = (float) $session->admin_commission_percentage;
            $adminCommission = round($earnedAmount * ($adminCommissionPct / 100), 2);
            $teacherNet = round($earnedAmount - $adminCommission, 2);

            if ($cancelledBy === 'teacher') {
                $teacherPayout = round($teacherNet / 1.5, 2);
            } else {
                $teacherPayout = $teacherNet;
            }

            $studentRefund = round($grossAmount - $teacherPayout, 2);

            if ($session->student && $studentRefund > 0) {
                $session->student->increment('balance', $studentRefund);
            }

            if ($session->teacher && $teacherPayout > 0) {
                $session->teacher->increment('balance', $teacherPayout);
            }

            WalletTransaction::query()->updateOrCreate(
                [
                    'student_id' => $session->student_id,
                    'teacher_session_id' => $session->id,
                    'type' => 'session_charge',
                ],
                [
                    'student_id' => $session->student_id,
                    'direction' => 'debit',
                    'status' => 'completed',
                    'amount' => $earnedAmount,
                    'description' => 'تم خصم الجزء المستحق من قيمة الجلسة بعد الإلغاء الجزئي.',
                    'meta' => [
                        'admin_commission_percentage' => $adminCommissionPct,
                        'admin_commission_amount' => $adminCommission,
                        'teacher_earning_amount' => $teacherPayout,
                        'elapsed_minutes' => $elapsedMinutes,
                        'planned_minutes' => $plannedMinutes,
                        'cancelled_by' => $cancelledBy,
                    ],
                ]
            );

            WalletTransaction::query()->updateOrCreate(
                [
                    'teacher_id' => $session->teacher_id,
                    'teacher_session_id' => $session->id,
                    'type' => 'session_earning',
                ],
                [
                    'teacher_id' => $session->teacher_id,
                    'direction' => 'credit',
                    'status' => 'completed',
                    'amount' => $teacherPayout,
                    'description' => $cancelledBy === 'teacher'
                        ? 'ربح الجلسة بعد الخصم الجزئي وعقوبة الإلغاء من قبل الأستاذ.'
                        : 'ربح الجلسة بعد الخصم الجزئي.',
                    'meta' => [
                        'gross_amount' => $grossAmount,
                        'admin_commission_percentage' => $adminCommissionPct,
                        'admin_commission_amount' => $adminCommission,
                        'elapsed_minutes' => $elapsedMinutes,
                        'planned_minutes' => $plannedMinutes,
                        'cancelled_by' => $cancelledBy,
                    ],
                ]
            );

            if ($studentRefund > 0) {
                WalletTransaction::query()->updateOrCreate(
                    [
                        'student_id' => $session->student_id,
                        'teacher_session_id' => $session->id,
                        'type' => 'session_refund',
                    ],
                    [
                        'student_id' => $session->student_id,
                        'direction' => 'credit',
                        'status' => 'completed',
                        'amount' => $studentRefund,
                        'description' => 'استرجاع المبلغ المتبقي بعد إلغاء الجلسة.',
                        'meta' => [
                            'elapsed_minutes' => $elapsedMinutes,
                            'planned_minutes' => $plannedMinutes,
                            'cancelled_by' => $cancelledBy,
                        ],
                    ]
                );
            }

            WalletTransaction::query()
                ->where('teacher_session_id', $session->id)
                ->whereIn('type', ['session_hold', 'session_pending'])
                ->delete();

            $session->update([
                'payment_status' => 'settled',
                'settled_at' => now(),
                'disputed_at' => null,
            ]);

            return $session->fresh(['student', 'teacher', 'subject']);
        });
    }

    private function ensureMinimumAmount(float $amount, string $field): void
    {
        if ($amount >= self::MIN_TRANSACTION_AMOUNT) {
            return;
        }

        throw ValidationException::withMessages([
            $field => 'أقل مبلغ مسموح به هو 50.',
        ]);
    }

    private function ensureWithdrawableAmount(float $balance, float $amount, string $field): void
    {
        $this->ensureMinimumAmount($amount, $field);

        if ($balance >= $amount) {
            return;
        }

        throw ValidationException::withMessages([
            $field => 'الرصيد الحالي غير كاف لإتمام عملية السحب.',
        ]);
    }

    private function teacherEarningAmount(TeacherSession $session): float
    {
        $earning = (float) $session->teacher_earning_amount;

        return $earning > 0 ? $earning : (float) $session->price;
    }

    private function syncTransactionBalance(WalletTransaction $transaction, float $nextAmount, string $nextStatus): void
    {
        $owner = $transaction->student ?: $transaction->teacher;

        if (! $owner) {
            return;
        }

        $currentEffect = $this->balanceEffect($transaction, (float) $transaction->amount, $transaction->status);
        $nextEffect = $this->balanceEffect($transaction, $nextAmount, $nextStatus);
        $delta = round($nextEffect - $currentEffect, 2);

        if ($delta > 0) {
            $owner->increment('balance', $delta);

            return;
        }

        if ($delta < 0) {
            $debit = abs($delta);

            if ($transaction->direction === 'debit' && (float) $owner->balance < $debit) {
                throw ValidationException::withMessages([
                    'amount' => 'الرصيد الحالي غير كاف لإتمام هذا التعديل.',
                ]);
            }

            $owner->decrement('balance', $debit);
        }
    }

    private function balanceEffect(WalletTransaction $transaction, float $amount, string $status): float
    {
        if ($transaction->direction === 'credit') {
            return $status === 'completed' ? $amount : 0.0;
        }

        if ($transaction->type === 'withdrawal') {
            return in_array($status, ['pending', 'disputed', 'held', 'completed'], true) ? -$amount : 0.0;
        }

        return $status === 'completed' ? -$amount : 0.0;
    }

    private function isDisputedSessionTransaction(WalletTransaction $transaction): bool
    {
        return $transaction->teacher_session_id
            && in_array($transaction->type, ['session_cancelled_held', 'session_cancelled_pending'], true);
    }

    private function isFinalStatus(string $status): bool
    {
        return in_array($status, ['completed', 'cancelled', 'refunded'], true);
    }

    private function updateDisputedSessionTransaction(
        WalletTransaction $transaction,
        float $amount,
        string $status,
        ?string $adminNote = null,
        ?UploadedFile $adminAttachment = null
    ): WalletTransaction {
        $session = TeacherSession::query()
            ->with(['student', 'teacher'])
            ->lockForUpdate()
            ->findOrFail($transaction->teacher_session_id);

        if (in_array($session->payment_status, ['settled', 'refunded'], true)) {
            throw ValidationException::withMessages([
                'status' => 'لا يمكن تعديل حالة جلسة تمت تسويتها مسبقاً.',
            ]);
        }

        [$grossAmount, $teacherEarning] = $this->disputedSessionAmounts($session, $transaction, $amount, $status);

        if ($status === 'completed') {
            $this->completeDisputedSessionPayment($session, $grossAmount, $teacherEarning);
        } elseif (in_array($status, ['cancelled', 'refunded'], true)) {
            $this->refundDisputedSessionPayment($session, $grossAmount, $teacherEarning);
        } elseif (in_array($status, ['pending', 'disputed'], true) && ! in_array($session->payment_status, ['settled', 'refunded'], true)) {
            $session->update([
                'payment_status' => 'disputed',
                'disputed_at' => now(),
            ]);
        }

        $this->syncDisputedSessionTransactions($session, $grossAmount, $teacherEarning, $status, $adminNote, $adminAttachment);

        return $transaction->fresh(['student', 'teacher', 'session']);
    }

    private function disputedSessionAmounts(TeacherSession $session, WalletTransaction $transaction, float $amount, string $status): array
    {
        $shareRatio = $this->disputedSessionTeacherShareRatio($session);

        if ($transaction->type === 'session_cancelled_pending') {
            if (in_array($status, ['cancelled', 'refunded'], true)) {
                return [(float) $amount, 0.0];
            }

            $teacherEarning = $amount;
            $grossAmount = $shareRatio > 0
                ? round($teacherEarning / $shareRatio, 2)
                : (float) $session->price;

            return [$grossAmount, $teacherEarning];
        }

        if (in_array($status, ['cancelled', 'refunded'], true)) {
            return [(float) $amount, 0.0];
        }

        $grossAmount = $amount;
        $teacherEarning = round($grossAmount * $this->disputedSessionAdminShareRatio($session), 2);

        return [$grossAmount, $teacherEarning];
    }

    private function disputedSessionTeacherShareRatio(TeacherSession $session): float
    {
        $price = (float) $session->price;

        if ($price <= 0) {
            return 1.0;
        }

        $ratio = (float) $session->teacher_earning_amount / $price;

        return $ratio > 0 ? min(1.0, $ratio) : 0.0;
    }

    private function disputedSessionAdminShareRatio(TeacherSession $session): float
    {
        $percentage = (float) $session->admin_commission_percentage;

        if ($percentage <= 0) {
            return 1.0;
        }

        $ratio = 1.0 - ($percentage / 100.0);

        return $ratio >= 0 ? min(1.0, $ratio) : 0.0;
    }

    private function completeDisputedSessionPayment(TeacherSession $session, float $grossAmount, float $teacherEarning): void
    {
        if ($session->payment_status === 'settled') {
            return;
        }

        if ($session->student) {
            if ($session->payment_status === 'refunded') {
                $this->ensureStudentCanAfford($session->student, $grossAmount, 'amount');
                $session->student->decrement('balance', $grossAmount);
            } elseif ($session->payment_status === 'disputed') {
                $heldAmount = WalletTransaction::query()
                    ->where('teacher_session_id', $session->id)
                    ->where('type', 'session_cancelled_held')
                    ->value('amount')
                    ?? (float) $session->price;

                $refundAmount = round(max(0, $heldAmount - $grossAmount), 2);

                if ($refundAmount > 0) {
                    $session->student->increment('balance', $refundAmount);
                }
            }
        }

        if ($session->teacher) {
            $session->teacher->increment('balance', $teacherEarning);
        }

        WalletTransaction::query()
            ->where('teacher_session_id', $session->id)
            ->whereIn('type', ['session_hold', 'session_pending'])
            ->delete();

        $session->update([
            'payment_status' => 'settled',
            'settled_at' => now(),
            'disputed_at' => null,
        ]);
    }

    private function refundDisputedSessionPayment(TeacherSession $session, float $grossAmount, float $teacherEarning): void
    {
        if ($session->payment_status === 'refunded') {
            return;
        }

        if ($session->payment_status === 'settled' && $session->teacher) {
            $this->ensureWithdrawableAmount((float) $session->teacher->balance, $teacherEarning, 'amount');
            $session->teacher->decrement('balance', $teacherEarning);
        }

        if ($session->student) {
            $session->student->increment('balance', $grossAmount);
        }

        WalletTransaction::query()
            ->where('teacher_session_id', $session->id)
            ->whereIn('type', ['session_hold', 'session_pending'])
            ->delete();

        $session->update([
            'payment_status' => 'refunded',
            'settled_at' => null,
            'disputed_at' => null,
        ]);
    }

    private function syncDisputedSessionTransactions(
        TeacherSession $session,
        float $grossAmount,
        float $teacherEarning,
        string $status,
        ?string $adminNote,
        ?UploadedFile $adminAttachment
    ): void {
        $reviewedAt = in_array($status, ['completed', 'cancelled', 'refunded'], true) ? now() : null;
        $adminAttachmentPath = $adminAttachment?->store('wallet/admin/sessions', 'public');

        $updates = [
            'status' => $status,
            'admin_note' => $adminNote,
            'reviewed_at' => $reviewedAt,
            'student_read_at' => null,
        ];

        if ($adminAttachmentPath) {
            $updates['admin_attachment_path'] = $adminAttachmentPath;
        }

        WalletTransaction::query()
            ->where('teacher_session_id', $session->id)
            ->where('type', 'session_cancelled_held')
            ->update(array_merge($updates, [
                'amount' => $grossAmount,
            ]));

        WalletTransaction::query()
            ->where('teacher_session_id', $session->id)
            ->where('type', 'session_cancelled_pending')
            ->update(array_merge($updates, [
                'amount' => $teacherEarning,
            ]));
    }

    private function applyTransactionToBalance(WalletTransaction $transaction): void
    {
        $owner = $transaction->student ?: $transaction->teacher;

        if (! $owner) {
            return;
        }

        $amount = (float) $transaction->amount;

        if ($transaction->direction === 'credit') {
            $owner->increment('balance', $amount);

            return;
        }

        $this->ensureWithdrawableAmount((float) $owner->balance, $amount, 'amount');
        $owner->decrement('balance', $amount);
    }

    private function rollbackTransactionFromBalance(WalletTransaction $transaction): void
    {
        $owner = $transaction->student ?: $transaction->teacher;

        if (! $owner) {
            return;
        }

        $amount = (float) $transaction->amount;

        if ($transaction->direction === 'credit') {
            $owner->decrement('balance', $amount);

            return;
        }

        $owner->increment('balance', $amount);
    }

    private function syncSessionPaymentStatus(WalletTransaction $transaction, string $status): void
    {
        if (! $transaction->teacher_session_id || $status !== 'disputed') {
            return;
        }

        TeacherSession::query()
            ->whereKey($transaction->teacher_session_id)
            ->update([
                'payment_status' => 'disputed',
                'disputed_at' => now(),
            ]);
    }
}
