<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use App\Services\Wallet\WalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminWalletController extends Controller
{
    public function __construct(
        private readonly WalletService $wallets,
    ) {
    }

    public function index(Request $request): View
    {
        $transactions = WalletTransaction::query()
            ->with(['student', 'teacher', 'session.subject'])
            ->when($request->filled('actor_type'), function ($query) use ($request): void {
                $request->string('actor_type')->toString() === 'student'
                    ? $query->whereNotNull('student_id')
                    : $query->whereNotNull('teacher_id');
            })
            ->when($request->filled('name'), function ($query) use ($request): void {
                $name = $request->string('name')->toString();

                $query->where(function ($nested) use ($name): void {
                    $nested->whereHas('student', fn ($student) => $student->where('name', 'like', "%{$name}%"))
                        ->orWhereHas('teacher', fn ($teacher) => $teacher->where('name', 'like', "%{$name}%"));
                });
            })
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('date_to')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.pages.wallet.index', [
            'transactions' => $transactions,
            'filters' => $request->only(['actor_type', 'name', 'date_from', 'date_to']),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function show(WalletTransaction $transaction): View
    {
        $transaction->load(['student', 'teacher', 'session.subject']);

        return view('admin.pages.wallet.show', [
            'transaction' => $transaction,
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function approve(Request $request, WalletTransaction $transaction): RedirectResponse
    {
        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->wallets->approveTransaction($transaction, $validated['admin_note'] ?? null);

        return back()->with('status', 'تم قبول حركة الرصيد وتحديث الحساب.');
    }

    public function reject(Request $request, WalletTransaction $transaction): RedirectResponse
    {
        $validated = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->wallets->rejectTransaction($transaction, $validated['admin_note'] ?? null);

        return back()->with('status', 'تم إلغاء حركة الرصيد.');
    }

    public function update(Request $request, WalletTransaction $transaction): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:completed,cancelled,refunded,disputed,pending'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
            'admin_attachment' => ['nullable', 'image', 'max:5120'],
        ]);

        $this->wallets->updateTransaction(
            $transaction,
            (float) $validated['amount'],
            $validated['status'],
            $validated['admin_note'] ?? null,
            $request->file('admin_attachment'),
        );

        return back()->with('status', 'تم تعديل حركة الرصيد.');
    }

    private function statusOptions(): array
    {
        return [
            'completed' => 'مكتمل',
            'cancelled' => 'ملغى',
            'refunded' => 'مسترجع',
            'disputed' => 'قيد المعالجة',
            'pending' => 'معلق',
        ];
    }
}
