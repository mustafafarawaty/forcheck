@extends('student.layouts.app')

@section('title', 'تفاصيل حركة الرصيد')
@section('page_title', 'تفاصيل حركة الرصيد')

@section('content')
<div class="mb-3">
    <a href="{{ route('student.wallet.index') }}" class="btn btn-secondary">رجوع للرصيد</a>
    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#studentWalletComplaintModal">
        تسجيل شكوى
    </button>
    @if($transaction->status === 'pending' && in_array($transaction->type, ['deposit', 'withdrawal'], true))
        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#studentWalletCancelModal">
            إلغاء الحركة
        </button>
    @endif
</div>

<div class="student-list-card">
    <div class="student-section-title mb-4">تفاصيل حركة الرصيد #{{ $transaction->id }}</div>

    <div class="teacher-session-detail-grid mb-4">
        <div>
            <div class="text-muted small mb-1">النوع</div>
            <div class="fw-semibold">{{ $typeLabels[$transaction->type] ?? $transaction->type }}</div>
        </div>
        <div>
            <div class="text-muted small mb-1">الحالة</div>
            <div class="fw-semibold">{{ $statusLabels[$transaction->status] ?? $transaction->status }}</div>
        </div>
        <div>
            <div class="text-muted small mb-1">المبلغ</div>
            <div class="fw-semibold">{{ $transaction->direction === 'credit' ? '+' : '-' }}{{ number_format((float) $transaction->amount, 0) }}</div>
        </div>
        <div>
            <div class="text-muted small mb-1">التاريخ</div>
            <div class="fw-semibold">{{ $transaction->created_at?->format('Y-m-d H:i') }}</div>
        </div>
        <div>
            <div class="text-muted small mb-1">الأستاذ</div>
            <div class="fw-semibold">{{ $transaction->teacher?->name ?? $transaction->session?->teacher?->name ?? '-' }}</div>
        </div>
        <div>
            <div class="text-muted small mb-1">الجلسة</div>
            <div class="fw-semibold">{{ $transaction->session?->subject?->name ?? '-' }}</div>
        </div>
    </div>

    <div class="teacher-media-card mb-3">
        <div class="small text-muted mb-2">الوصف</div>
        <div>{{ $transaction->description ?: 'لا يوجد وصف إضافي' }}</div>
    </div>

    <div class="teacher-media-card mb-3">
        <div class="small text-muted mb-2">ملاحظة الإدارة</div>
        <div>{{ $transaction->admin_note ?: 'لا يوجد' }}</div>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="teacher-media-card h-100">
                <div class="small text-muted mb-2">صورة الطلب</div>
                @if($transaction->proof_url)
                    <a href="{{ $transaction->proof_url }}" target="_blank">
                        <img src="{{ $transaction->proof_url }}" alt="صورة الطلب" class="img-fluid rounded border">
                    </a>
                @else
                    <div class="text-muted">لا يوجد</div>
                @endif
            </div>
        </div>
        <div class="col-md-6">
            <div class="teacher-media-card h-100">
                <div class="small text-muted mb-2">صورة الإدارة</div>
                @if($transaction->admin_attachment_url)
                    <a href="{{ $transaction->admin_attachment_url }}" target="_blank">
                        <img src="{{ $transaction->admin_attachment_url }}" alt="صورة الإدارة" class="img-fluid rounded border">
                    </a>
                @else
                    <div class="text-muted">لا يوجد</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('modals')
    @if($transaction->status === 'pending' && in_array($transaction->type, ['deposit', 'withdrawal'], true))
        <div class="modal fade" id="studentWalletCancelModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content student-modal-card">
                    <div class="modal-header border-0 pb-0">
                        <h2 class="h5 fw-bold mb-0">تأكيد إلغاء حركة الرصيد</h2>
                        <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <p class="mb-4">هل تريد إلغاء هذه الحركة؟</p>
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

    <div class="modal fade" id="studentWalletComplaintModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content student-modal-card">
                <div class="modal-header border-0 pb-0">
                    <h2 class="h5 fw-bold mb-0">تسجيل شكوى على حركة الرصيد</h2>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('student.wallet.complaint', $transaction) }}" method="POST" enctype="multipart/form-data" class="row g-3">
                        @csrf
                        <div class="col-12">
                            <label class="form-label fw-semibold">عنوان الشكوى</label>
                            <input type="text" name="title" class="form-control student-form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">وصف الشكوى</label>
                            <textarea name="description" rows="4" class="form-control student-form-control" required></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">إرفاق صورة (اختياري)</label>
                            <input type="file" name="attachment" accept="image/*" class="form-control student-form-control">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn student-btn-primary w-100">إرسال الشكوى</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endpush
