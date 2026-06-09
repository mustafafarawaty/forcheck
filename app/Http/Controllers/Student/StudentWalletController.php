<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\StoreWalletDepositRequest;
use App\Http\Requests\Wallet\StoreWalletWithdrawalRequest;
use App\Models\WalletTransaction;
use App\Services\Wallet\WalletService;
use App\Services\Realtime\RealtimeUpdateService;
use App\Traits\ResolvesStudentAuthentication;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Student wallet pages and actions.
 */
class StudentWalletController extends Controller
{
    use ResolvesStudentAuthentication;

    public function __construct(
        private readonly WalletService $wallets,
        private readonly RealtimeUpdateService $realtime,
    ) {
    }

    /**
     * Wallet activity page.
     */
    public function index(Request $request): View
    {
        $student = $this->authenticatedStudent($request);
        $this->markTransactionsAsRead($student->id);

        return view('student.pages.wallet.index', [
            'transactions' => $this->wallets->studentTransactions($student)->paginate(15),
        ]);
    }

    public function show(Request $request, WalletTransaction $transaction): View
    {
        $student = $this->authenticatedStudent($request);

        abort_unless($transaction->student_id === $student->id, 403);

        $transaction->forceFill(['student_read_at' => now()])->save();
        $transaction->load(['session.subject', 'teacher']);

        return view('student.pages.wallet.show', [
            'transaction' => $transaction,
            'typeLabels' => $this->typeLabels(),
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    /**
     * Charge student balance.
     */
    public function deposit(StoreWalletDepositRequest $request): RedirectResponse
    {
        $student = $this->authenticatedStudent($request);
        $this->wallets->depositForStudent(
            $student,
            (float) $request->validated('amount'),
            $request->file('proof_image')
        );

        return back()->with('status', 'تم إرسال طلب شحن الرصيد بانتظار موافقة الإدارة.');
    }

    /**
     * Withdraw from student balance.
     */
    public function withdraw(StoreWalletWithdrawalRequest $request): RedirectResponse
    {
        $student = $this->authenticatedStudent($request);
        $this->wallets->withdrawForStudent(
            $student,
            (float) $request->validated('amount'),
            $request->file('proof_image')
        );

        return back()->with('status', 'تم إرسال طلب السحب بانتظار موافقة الإدارة.');
    }

    public function cancel(Request $request, WalletTransaction $transaction): RedirectResponse
    {
        $student = $this->authenticatedStudent($request);

        abort_unless($transaction->student_id === $student->id, 403);

        $this->wallets->cancelPendingTransaction($transaction);

        return back()->with('status', 'تم إلغاء حركة الرصيد.');
    }

    public function complaint(Request $request, WalletTransaction $transaction): RedirectResponse
    {
        $student = $this->authenticatedStudent($request);

        abort_unless($transaction->student_id === $student->id, 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'attachment' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($request->hasFile('attachment')) {
            $validated['attachment_path'] = $request->file('attachment')->store("complaints/students/{$student->id}", 'public');
        }

        unset($validated['attachment']);

        $student->complaints()->create([
            ...$validated,
            'wallet_transaction_id' => $transaction->id,
            'teacher_session_id' => $transaction->teacher_session_id,
            'teacher_id' => $transaction->teacher_id,
            'submitted_by' => 'student',
            'status' => 'pending',
            'submitted_at' => now(),
            'student_read_at' => now(),
        ]);

        $this->realtime->broadcastAdminDashboard();

        return back()->with('status', 'تم إرسال الشكوى بانتظار مراجعة الإدارة.');
    }

    private function typeLabels(): array
    {
        return [
            'session_refund' => 'استرجاع جلسة',
            'deposit' => 'شحن',
            'withdrawal' => 'سحب',
            'session_hold' => 'تعليق جلسة',
            'session_pending' => 'جلسة معلقة',
            'session_charge' => 'اكتمال جلسة',
            'session_cancelled_held' => 'جلسة ملغاة معلقة',
        ];
    }

    private function statusLabels(): array
    {
        return [
            'refunded' => 'مسترجع',
            'cancelled' => 'ملغي',
            'completed' => 'مكتمل',
            'rejected' => 'مرفوض',
            'held' => 'معلق',
            'pending' => 'معلقة',
            'disputed' => 'قيد المعالجة',
        ];
    }

    private function markTransactionsAsRead(int $studentId): void
    {
        WalletTransaction::query()
            ->where('student_id', $studentId)
            ->whereNull('student_read_at')
            ->update(['student_read_at' => now()]);
    }
}
