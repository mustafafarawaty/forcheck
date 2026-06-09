<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\StoreWalletWithdrawalRequest;
use App\Models\WalletTransaction;
use App\Services\Wallet\WalletService;
use App\Services\Realtime\RealtimeUpdateService;
use App\Traits\ResolvesTeacherAuthentication;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Teacher wallet pages and actions.
 */
class TeacherWalletController extends Controller
{
    use ResolvesTeacherAuthentication;

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
        $teacher = $this->authenticatedTeacher($request);
        $transactions = $this->wallets->teacherTransactions($teacher)->paginate(15);

        if (Schema::hasColumn('wallet_transactions', 'teacher_read_at')) {
            $transactions->getCollection()->each(function (WalletTransaction $transaction): void {
                if (is_null($transaction->teacher_read_at)) {
                    $transaction->forceFill(['teacher_read_at' => now()])->save();
                }
            });
        }

        return view('teacher.pages.wallet.index', [
            'transactions' => $transactions,
        ]);
    }

    public function show(Request $request, WalletTransaction $transaction): View
    {
        $teacher = $this->authenticatedTeacher($request);

        abort_unless($transaction->teacher_id === $teacher->id, 403);

        if (Schema::hasColumn('wallet_transactions', 'teacher_read_at') && is_null($transaction->teacher_read_at)) {
            $transaction->forceFill(['teacher_read_at' => now()])->save();
        }

        $transaction->load(['session.subject', 'student']);

        return view('teacher.pages.wallet.show', [
            'transaction' => $transaction,
            'typeLabels' => $this->typeLabels(),
            'statusLabels' => $this->statusLabels(),
        ]);
    }

    /**
     * Withdraw from teacher balance.
     */
    public function withdraw(StoreWalletWithdrawalRequest $request): RedirectResponse
    {
        $teacher = $this->authenticatedTeacher($request);
        $this->wallets->withdrawForTeacher(
            $teacher,
            (float) $request->validated('amount'),
            $request->file('proof_image')
        );

        return back()->with('status', 'ØªÙ… Ø¥Ø±Ø³Ø§Ù„ Ø·Ù„Ø¨ Ø§Ù„Ø³Ø­Ø¨ Ø¨Ø§Ù†ØªØ¸Ø§Ø± Ù…ÙˆØ§ÙÙ‚Ø© Ø§Ù„Ø¥Ø¯Ø§Ø±Ø©.');
    }

    public function cancel(Request $request, WalletTransaction $transaction): RedirectResponse
    {
        $teacher = $this->authenticatedTeacher($request);

        abort_unless($transaction->teacher_id === $teacher->id, 403);

        $this->wallets->cancelPendingTransaction($transaction);

        return back()->with('status', 'ØªÙ… Ø¥Ù„ØºØ§Ø¡ Ø­Ø±ÙƒØ© Ø§Ù„Ø±ØµÙŠØ¯.');
    }

    public function complaint(Request $request, WalletTransaction $transaction): RedirectResponse
    {
        $teacher = $this->authenticatedTeacher($request);

        abort_unless($transaction->teacher_id === $teacher->id, 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'attachment' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($request->hasFile('attachment')) {
            $validated['attachment_path'] = $request->file('attachment')->store("complaints/teachers/{$teacher->id}", 'public');
        }

        unset($validated['attachment']);

        $teacher->complaints()->create([
            ...$validated,
            'wallet_transaction_id' => $transaction->id,
            'teacher_session_id' => $transaction->teacher_session_id,
            'student_id' => $transaction->student_id,
            'submitted_by' => 'teacher',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);
        $this->realtime->broadcastAdminDashboard();
        return back()->with('status', 'ØªÙ… Ø¥Ø±Ø³Ø§Ù„ Ø§Ù„Ø´ÙƒÙˆÙ‰ Ø¨Ø§Ù†ØªØ¸Ø§Ø± Ù…Ø±Ø§Ø¬Ø¹Ø© Ø§Ù„Ø¥Ø¯Ø§Ø±Ø©.');
    }

    private function typeLabels(): array
    {
        return [
            'withdrawal' => 'Ø³Ø­Ø¨',
            'session_pending' => 'Ù…Ø³ØªØ­Ù‚ Ø¬Ù„Ø³Ø©',
            'session_earning' => 'Ø±Ø¨Ø­ Ø¬Ù„Ø³Ø©',
            'session_cancelled_pending' => 'Ø¬Ù„Ø³Ø© Ù…Ù„ØºØ§Ø© Ù…Ø¹Ù„Ù‚Ø©',
        ];
    }

    private function statusLabels(): array
    {
        return [
            'cancelled' => 'Ù…Ù„ØºÙŠ',
            'completed' => 'Ù…ÙƒØªÙ…Ù„',
            'rejected' => 'Ù…Ø±ÙÙˆØ¶',
            'pending' => 'Ù…Ø¹Ù„Ù‚',
            'disputed' => 'Ù‚ÙŠØ¯ Ø§Ù„Ù…Ø¹Ø§Ù„Ø¬Ø©',
            'held' => 'Ù…Ø¹Ù„Ù‚',
            'refunded' => 'Ù…Ø³ØªØ±Ø¬Ø¹',
        ];
    }
}

