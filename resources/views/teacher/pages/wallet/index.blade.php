@extends('teacher.layouts.app')

@section('title', 'حركة الرصيد')
@section('page_title', 'حركة الرصيد')

@php
    $typeLabels = [
        'withdrawal' => 'سحب',
        'session_pending' => 'مستحق جلسة',
        'session_earning' => 'ربح جلسة',
        'session_cancelled_pending' => 'جلسة ملغاة معلقة',
    ];

    $statusLabels = [
        'cancelled' => 'ملغي',
        'completed' => 'مكتمل',
        'rejected' => 'مرفوض',
        'pending' => 'معلق',
        'disputed' => 'قيد المعالجة',
        'held' => 'معلق',
        'refunded' => 'مسترجع',
    ];
@endphp

@section('content')
<section class="teacher-list-card teacher-mobile-card mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
            <div class="teacher-section-title mb-1">إجمالي الرصيد</div>
            <div class="wallet-total-balance teacher-wallet-balance">{{ number_format((float) ($currentTeacher->balance ?? 0), 0) }}</div>
        </div>

        <div>
            <button type="button" class="btn teacher-btn-primary" data-bs-toggle="modal" data-bs-target="#teacherWalletWithdrawModal">
                سحب الرصيد
            </button>
        </div>
    </div>
</section>

<section class="teacher-list-card teacher-mobile-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="teacher-section-title mb-0">سجل الشحن والسحب</div>
        <div class="small text-muted">{{ $transactions->total() }} حركة</div>
    </div>

    <div class="table-responsive">
        <table class="table align-middle table-hover teacher-table teacher-sessions-table">
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
                                <a href="{{ route('teacher.wallet.show', $transaction) }}" class="btn btn-sm btn-outline-primary">تفاصيل</a>
                                @if($transaction->status === 'pending' && $transaction->type === 'withdrawal')
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#teacherWalletCancelModal{{ $transaction->id }}">إلغاء</button>
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
        @if($transaction->status === 'pending' && $transaction->type === 'withdrawal')
            <div class="modal fade" id="teacherWalletCancelModal{{ $transaction->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content teacher-modal-card">
                        <div class="modal-header border-0 pb-0">
                            <h2 class="h5 fw-bold mb-0">تأكيد إلغاء السحب</h2>
                            <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body pt-3">
                            <p class="mb-4">هل تريد إلغاء طلب السحب بقيمة {{ number_format((float) $transaction->amount, 0) }}؟</p>
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn teacher-btn-soft" data-bs-dismiss="modal">رجوع</button>
                                <form action="{{ route('teacher.wallet.cancel', $transaction) }}" method="POST">
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

    <div class="modal fade" id="teacherWalletWithdrawModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content teacher-modal-card">
                <div class="modal-header border-0 pb-0">
                    <h2 class="h5 fw-bold mb-0">سحب الرصيد</h2>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <form action="{{ route('teacher.wallet.withdraw') }}" method="POST" enctype="multipart/form-data" data-wallet-form data-wallet-max="{{ (float) ($currentTeacher->balance ?? 0) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">قيمة السحب</label>
                            <div class="input-group">
                                <input type="number" min="50" step="1" name="amount" class="form-control teacher-form-control" required data-wallet-amount>
                                <button type="button" class="btn btn-outline-secondary" data-wallet-fill-max>كل الرصيد</button>
                            </div>
                            <div class="form-text text-danger d-none" data-wallet-amount-error>أقل مبلغ مسموح به هو 50.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">صورة حساب شام كاش المراد السحب عليه</label>
                            <input type="file" accept="image/*" name="proof_image" class="form-control teacher-form-control" required data-wallet-proof>
                            <div class="form-text text-danger d-none" data-wallet-proof-error>يرجى رفع صورة صحيحة.</div>
                        </div>
                        <button type="submit" class="btn teacher-btn-primary w-100" data-wallet-submit disabled>تأكيد السحب</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endpush
