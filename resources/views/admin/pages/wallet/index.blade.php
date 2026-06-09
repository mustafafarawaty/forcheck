@extends('admin.layouts.app')

@section('title', 'الأرصدة')
@section('page_title', 'إدارة الأرصدة')

@section('content')
    @php
        $typeLabels = [
            'deposit' => 'إيداع',
            'withdrawal' => 'سحب',
            'session_charge' => 'خصم جلسة',
            'session_earning' => 'ربح جلسة',
            'session_cancelled_held' => 'تعليق جلسة ملغاة',
            'session_cancelled_pending' => 'استحقاق معلق',
        ];
    @endphp

    <div class="card content-card border-0 mb-4">
        <div class="card-body p-4">
            <form method="GET" action="{{ route('admin.wallet.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">الطرف</label>
                    <select name="actor_type" class="form-select">
                        <option value="">الكل</option>
                        <option value="student" @selected(($filters['actor_type'] ?? '') === 'student')>طالب</option>
                        <option value="teacher" @selected(($filters['actor_type'] ?? '') === 'teacher')>أستاذ</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">الاسم</label>
                    <input type="text" name="name" value="{{ $filters['name'] ?? '' }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">من تاريخ</label>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">إلى تاريخ</label>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="form-control">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-primary flex-fill" type="submit">فلترة</button>
                    <a href="{{ route('admin.wallet.index') }}" class="btn btn-outline-secondary">مسح</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card content-card border-0">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table align-middle table-hover">
                    <thead>
                        <tr>
                            <th>الطرف</th>
                            <th>النوع</th>
                            <th>المبلغ</th>
                            <th>الحالة</th>
                            <th>التاريخ</th>
                            <th>الصورة</th>
                            <th>ملاحظة الإدارة</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $transaction->owner_name }}</div>
                                    <div class="small text-muted">{{ $transaction->owner_role === 'student' ? 'طالب' : ($transaction->owner_role === 'teacher' ? 'أستاذ' : 'نظام') }}</div>
                                </td>
                                <td>{{ $typeLabels[$transaction->type] ?? $transaction->type }}</td>
                                <td class="{{ $transaction->direction === 'credit' ? 'text-success' : 'text-danger' }}">
                                    {{ $transaction->direction === 'credit' ? '+' : '-' }}{{ number_format((float) $transaction->amount, 0) }}
                                </td>
                                <td>{{ $statusOptions[['held' => 'pending', 'rejected' => 'cancelled'][$transaction->status] ?? $transaction->status] ?? $transaction->status }}</td>
                                <td>{{ $transaction->created_at?->format('Y-m-d H:i') }}</td>
                                <td>
                                    @if($transaction->proof_url)
                                        <a href="{{ $transaction->proof_url }}" target="_blank" class="btn btn-sm btn-outline-primary">عرض</a>
                                    @else
                                        <span class="text-muted small">لا يوجد</span>
                                    @endif
                                </td>
                                <td>{{ $transaction->admin_note ?: '-' }}</td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="{{ route('admin.wallet.show', $transaction) }}" class="btn btn-sm btn-primary">التفاصيل</a>
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#walletQuickEdit{{ $transaction->id }}">
                                            تعديل
                                        </button>
                                        <form action="{{ route('admin.wallet.approve', $transaction) }}" method="POST">
                                            @csrf
                                            <button class="btn btn-sm btn-success" type="submit">قبول</button>
                                        </form>
                                        <form action="{{ route('admin.wallet.update', $transaction) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="amount" value="{{ $transaction->amount }}">
                                            <input type="hidden" name="status" value="cancelled">
                                            <button class="btn btn-sm btn-outline-danger" type="submit">ملغى</button>
                                        </form>
                                        <form action="{{ route('admin.wallet.update', $transaction) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="amount" value="{{ $transaction->amount }}">
                                            <input type="hidden" name="status" value="refunded">
                                            <button class="btn btn-sm btn-outline-warning" type="submit">مسترجع</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <tr class="collapse" id="walletQuickEdit{{ $transaction->id }}">
                                <td colspan="8" class="bg-light">
                                    <form action="{{ route('admin.wallet.update', $transaction) }}" method="POST" class="row g-2 align-items-end">
                                        @csrf
                                        @method('PATCH')
                                        <div class="col-md-2">
                                            <label class="form-label small fw-semibold">المبلغ</label>
                                            <input type="number" name="amount" min="50" step="0.01" value="{{ $transaction->amount }}" class="form-control form-control-sm" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label small fw-semibold">الحالة</label>
                                            <select name="status" class="form-select form-select-sm">
                                                @foreach($statusOptions as $value => $label)
                                                    <option value="{{ $value }}" @selected($transaction->status === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small fw-semibold">ملاحظة الإدارة</label>
                                            <input type="text" name="admin_note" value="{{ $transaction->admin_note }}" class="form-control form-control-sm">
                                        </div>
                                        <div class="col-md-2">
                                            <button class="btn btn-sm btn-primary w-100" type="submit">حفظ</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">لا توجد حركات رصيد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $transactions->onEachSide(1)->links('vendor.pagination.ba3eed') }}
        </div>
    </div>
@endsection
