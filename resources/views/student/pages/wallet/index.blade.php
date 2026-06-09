@extends('student.layouts.app')

@section('title', 'حركة الرصيد')
@section('page_title', 'حركة الرصيد')

@php
    $typeLabels = [
        'session_refund' => 'استرجاع جلسة',
        'deposit' => 'شحن',
        'withdrawal' => 'سحب',
        'session_hold' => 'تعليق جلسة',
        'session_pending' => 'جلسة معلقة',
        'session_charge' => 'اكتمال جلسة',
        'session_cancelled_held' => 'جلسة ملغاة معلقة',
    ];

    $statusLabels = [
        'refunded' => 'مسترجع',
        'cancelled' => 'ملغي',
        'completed' => 'مكتمل',
        'rejected' => 'مرفوض',
        'held' => 'معلق',
        'pending' => 'معلقة',
        'disputed' => 'قيد المعالجة',
    ];
@endphp

@section('content')
<section class="student-list-card student-mobile-card mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
            <div class="student-section-title mb-1">إجمالي الرصيد</div>
            <div class="wallet-total-balance student-wallet-balance">{{ number_format((float) ($currentStudent->balance ?? 0), 0) }}</div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn student-btn-primary" data-bs-toggle="modal" data-bs-target="#studentWalletDepositModal">
                شحن رصيد
            </button>
            <button type="button" class="btn student-btn-soft" data-bs-toggle="modal" data-bs-target="#studentWalletWithdrawModal">
                سحب رصيد
            </button>
        </div>
    </div>
</section>

<section class="student-list-card student-mobile-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="student-section-title mb-0">سجل الشحن والسحب</div>
        <div class="small text-muted">{{ $transactions->total() }} حركة</div>
    </div>

    <div class="table-responsive">
        <table class="table align-middle table-hover student-table">
            <thead>
                <tr>
                    <th>النوع</th>
                    <th>المبلغ</th>
                    <th>الحالة</th>
                    <th>التفاصيل</th>
                    <th>التاريخ</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $transaction)
                    <tr>
                        <td>{{ $typeLabels[$transaction->type] ?? $transaction->type }}</td>
                        <td class="{{ $transaction->direction === 'credit' ? 'text-success' : 'text-danger' }}">
                            {{ $transaction->direction === 'credit' ? '+' : '-' }}{{ number_format((float) $transaction->amount, 0) }}
                        </td>
                        <td>{{ $statusLabels[$transaction->status] ?? $transaction->status }}</td>
                        <td>
                            <div>{{ $transaction->description ?: 'لا يوجد وصف إضافي' }}</div>
                            @if($transaction->admin_note)
                                <div class="small text-muted mt-1">ملاحظة الإدارة: {{ $transaction->admin_note }}</div>
                            @endif
                        </td>
                        <td>{{ $transaction->created_at?->format('Y-m-d H:i') }}</td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('student.wallet.show', $transaction) }}" class="btn btn-sm btn-outline-primary">تفاصيل</a>
                                @if($transaction->status === 'pending' && in_array($transaction->type, ['deposit', 'withdrawal'], true))
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#studentWalletCancelModal{{ $transaction->id }}">إلغاء</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">لا توجد حركات رصيد بعد.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $transactions->onEachSide(1)->links('vendor.pagination.ba3eed') }}
    </div>
</section>
@endsection

@push('modals')
    @foreach($transactions as $transaction)
        @if($transaction->status === 'pending' && in_array($transaction->type, ['deposit', 'withdrawal'], true))
            <div class="modal fade" id="studentWalletCancelModal{{ $transaction->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content student-modal-card">
                        <div class="modal-header border-0 pb-0">
                            <h2 class="h5 fw-bold mb-0">تأكيد إلغاء حركة الرصيد</h2>
                            <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body pt-3">
                            <p class="mb-4">هل تريد إلغاء طلب {{ $typeLabels[$transaction->type] ?? $transaction->type }} بقيمة {{ number_format((float) $transaction->amount, 0) }}؟</p>
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn student-btn-soft" data-bs-dismiss="modal">رجوع</button>
                                <form action="{{ route('student.wallet.cancel', $transaction) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger">تأكيد الإلغاء</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

    <div class="modal fade" id="studentWalletDepositModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content student-modal-card">
                <div class="modal-header border-0 pb-0">
                    <h2 class="h5 fw-bold mb-0">شحن الرصيد</h2>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <form action="{{ route('student.wallet.deposit') }}" method="POST" enctype="multipart/form-data" data-wallet-form>
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">قيمة الشحن</label>
                            <input type="number" min="50" step="1" name="amount" class="form-control student-form-control" required data-wallet-amount>
                            <div class="form-text text-danger d-none" data-wallet-amount-error>أقل مبلغ مسموح به هو 50.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">صورة الحوالة من شام كاش</label>
                            <input type="file" accept="image/*" name="proof_image" class="form-control student-form-control" required data-wallet-proof>
                            <div class="form-text text-danger d-none" data-wallet-proof-error>يرجى رفع صورة صحيحة.</div>
                        </div>
                        <button type="submit" class="btn student-btn-primary w-100" data-wallet-submit disabled>تأكيد الشحن</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="studentWalletWithdrawModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content student-modal-card">
                <div class="modal-header border-0 pb-0">
                    <h2 class="h5 fw-bold mb-0">سحب الرصيد</h2>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <form action="{{ route('student.wallet.withdraw') }}" method="POST" enctype="multipart/form-data" data-wallet-form data-wallet-max="{{ (float) ($currentStudent->balance ?? 0) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">قيمة السحب</label>
                            <div class="input-group">
                                <input type="number" min="50" step="1" name="amount" class="form-control student-form-control" required data-wallet-amount>
                                <button type="button" class="btn btn-outline-secondary" data-wallet-fill-max>كل الرصيد</button>
                            </div>
                            <div class="form-text text-danger d-none" data-wallet-amount-error>أقل مبلغ مسموح به هو 50.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">صورة حساب شام كاش المراد السحب عليه</label>
                            <input type="file" accept="image/*" name="proof_image" class="form-control student-form-control" required data-wallet-proof>
                            <div class="form-text text-danger d-none" data-wallet-proof-error>يرجى رفع صورة صحيحة.</div>
                        </div>
                        <button type="submit" class="btn student-btn-primary w-100" data-wallet-submit disabled>تأكيد السحب</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endpush
