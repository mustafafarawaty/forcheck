@extends('student.layouts.app')

@section('title', 'تفاصيل الجلسة')

@section('content')
@php
    $session->load(['teacher', 'subject', 'files', 'complaints', 'walletTransactions']);
    $actualDurationMinutes = $session->started_at && $session->ended_at
        ? $session->started_at->diffInMinutes($session->ended_at)
        : null;

    $paymentLabels = [
        'refunded' => 'تم إرجاع الرصيد',
        'unpaid' => 'لم يبدأ الخصم بعد',
        'held' => 'الرصيد معلّق',
        'settled' => 'تمت التسوية',
        'disputed' => 'معلق للإدارة',
    ];
@endphp

<div class="mb-3">
    <a href="{{ route('student.sessions.index') }}" class="btn btn-secondary">
        رجوع للجدول
    </a>
    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#studentSessionComplaintModal">
        تسجيل شكوى
    </button>
</div>

<div class="student-list-card">
    <div class="student-section-title mb-4">
        تفاصيل الجلسة
    </div>

    <div class="teacher-session-detail-grid mb-4">
        <div>
            <div class="text-muted small mb-1">الأستاذ</div>
            <div class="fw-semibold">{{ $session->teacher?->name ?? 'أستاذ' }}</div>
        </div>

        <div>
            <div class="text-muted small mb-1">المادة</div>
            <div class="fw-semibold">{{ $session->subject?->name ?? 'بدون مادة' }}</div>
        </div>

        <div>
            <div class="text-muted small mb-1">الحالة</div>
            <div class="fw-semibold">
                {{ $session->status === 'completed' ? 'مكتملة' : ($session->status === 'cancelled' ? 'ملغية' : ($session->status === 'in_progress' ? 'جارية' : 'قادمة')) }}
            </div>
        </div>

        <div>
            <div class="text-muted small mb-1">الموعد</div>
            <div class="fw-semibold">{{ $session->scheduled_at?->format('Y-m-d H:i') }}</div>
        </div>

        <div>
            <div class="text-muted small mb-1">قيمة الجلسة</div>
            <div class="fw-semibold">{{ number_format((float) $session->price, 0) }}</div>
        </div>

        <div>
            <div class="text-muted small mb-1">حالة الرصيد</div>
            <div class="fw-semibold">{{ $paymentLabels[$session->payment_status] ?? $session->payment_status }}</div>
        </div>

        <div>
            <div class="text-muted small mb-1">بداية الجلسة</div>
            <div class="fw-semibold">{{ $session->started_at?->format('Y-m-d H:i') ?: 'لم تبدأ بعد' }}</div>
        </div>

        <div>
            <div class="text-muted small mb-1">نهاية الجلسة</div>
            <div class="fw-semibold">{{ $session->ended_at?->format('Y-m-d H:i') ?: 'لم تنتهِ بعد' }}</div>
        </div>

        <div>
            <div class="text-muted small mb-1">المدة الفعلية</div>
            <div class="fw-semibold">{{ $actualDurationMinutes !== null ? $actualDurationMinutes.' دقيقة' : 'غير متوفرة بعد' }}</div>
        </div>
    </div>

    @if($session->payment_status === 'disputed')
        <div class="teacher-media-card mb-3">
            <div class="small text-danger mb-2">تنبيه الرصيد</div>
            <div>تم تعليق رصيد هذه الجلسة للطرفين بانتظار مراجعة الإدارة.</div>
        </div>
    @endif

    <div class="teacher-media-card mb-3">
        <div class="small text-muted mb-2">ملاحظات</div>
        <div>{{ $session->notes ?: 'لا يوجد' }}</div>
    </div>

    <div class="teacher-media-card mb-3">
        <div class="small text-muted mb-2">المحادثة</div>
        <div style="white-space: pre-line;">{{ $session->chat_excerpt ?: 'لا يوجد' }}</div>
    </div>

    <div class="teacher-media-card mb-3">
        <div class="small text-muted mb-2">ملخص الجلسة</div>
        <div>{{ $session->student_summary_notes ?: 'لا يوجد ملخص بعد' }}</div>
    </div>

    <div class="teacher-media-card mb-3">
        <div class="small text-muted mb-2">تسجيل الجلسة</div>

        @if($session->recording_url && $session->recording_url !== '#')
            <div class="text-muted">التسجيل مخفي ولا يظهر إلا للإدارة.</div>
        @elseif(false && $session->recording_public_url)
            <a href="{{ $session->recording_public_url }}" class="btn btn-outline-primary btn-sm" download>
                تحميل تسجيل الجلسة
            </a>
        @else
            <div class="text-muted">غير متوفر</div>
        @endif

        @if(false && $session->status !== 'cancelled' && $session->recording_expires_at && $session->recording_public_url)
            <div class="small text-muted mt-2">
                متاح للتحميل حتى {{ $session->recording_expires_at->format('Y-m-d H:i') }}
            </div>
        @elseif(false && $session->status !== 'cancelled' && $session->recording_expires_at)
            <div class="small text-muted mt-2">
                تم حذف التسجيل تلقائياً في {{ $session->recording_expires_at->format('Y-m-d H:i') }}
            </div>
        @endif
    </div>

    <div class="teacher-media-card mb-3">
        <div class="small text-muted mb-2">الملفات</div>

        @forelse($session->files as $file)
            <div>
                <a href="{{ $file->file_url }}" target="_blank">
                    {{ $file->original_name }}
                </a>
            </div>
        @empty
            <div class="text-muted">لا يوجد ملفات</div>
        @endforelse
    </div>

    <div class="teacher-media-card mb-3">
        <div class="small text-muted mb-2">الشكاوى</div>

        @forelse($session->complaints as $complaint)
            <div class="border-bottom py-2">
                <div class="fw-semibold">{{ $complaint->title }}</div>
                <div class="small text-muted">{{ $complaint->status }}</div>
                <a href="{{ route('student.complaints.show', $complaint->id) }}" class="btn btn-sm btn-outline-primary mt-2">رد</a>
            </div>
        @empty
            <div class="text-muted">لا يوجد شكاوى</div>
        @endforelse
    </div>

    @if($session->cancellation_reason)
        <div class="teacher-media-card mb-3">
            <div class="small text-danger mb-2">سبب الإلغاء</div>
            <div>{{ $session->cancellation_reason }}</div>
        </div>
    @endif

    @if($session->status === 'upcoming')
        <form action="{{ route('student.sessions.cancel', $session->id) }}" method="POST" class="teacher-media-card">
            @csrf
            <div class="fw-bold mb-3">إلغاء الجلسة القادمة</div>
            <input type="text" name="cancellation_reason" class="form-control student-form-control mb-3" placeholder="سبب الإلغاء اختياري">
            <button type="submit" class="btn btn-outline-danger w-100">إلغاء الجلسة</button>
        </form>
    @endif
</div>
@endsection

@push('modals')
    <div class="modal fade" id="studentSessionComplaintModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content student-modal-card">
                <div class="modal-header border-0 pb-0">
                    <h2 class="h5 fw-bold mb-0">تسجيل شكوى على الجلسة</h2>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('student.sessions.complaint', $session->id) }}" method="POST" enctype="multipart/form-data" class="row g-3">
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
