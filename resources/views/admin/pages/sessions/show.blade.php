@extends('admin.layouts.app')

@section('title', 'تفاصيل الجلسة')
@section('page_title', 'تفاصيل الجلسة #' . $session->id)

@section('content')
    @php
        $actualDuration = $session->started_at && $session->ended_at
            ? $session->started_at->diffInMinutes($session->ended_at) . ' دقيقة'
            : 'غير متوفر';
    @endphp

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card content-card border-0 h-100">
                <div class="card-body p-4">
                    <div class="mb-3"><span class="text-muted">الأستاذ:</span> {{ $session->teacher?->name ?? '-' }}</div>
                    <div class="mb-3"><span class="text-muted">هاتف الأستاذ:</span> {{ $session->teacher?->phone ?? '-' }}</div>
                    <div class="mb-3"><span class="text-muted">الطالب:</span> {{ $session->student?->name ?? $session->student_name ?? '-' }}</div>
                    <div class="mb-3"><span class="text-muted">هاتف الطالب:</span> {{ $session->student?->phone ?? '-' }}</div>
                    <div class="mb-3"><span class="text-muted">المادة:</span> {{ $session->subject?->name ?? '-' }}</div>
                    <div class="mb-3"><span class="text-muted">الموعد:</span> {{ $session->scheduled_at?->format('Y-m-d H:i') }}</div>
                    <div class="mb-3"><span class="text-muted">المدة المحجوزة:</span> {{ $session->duration_hours }} ساعة</div>
                    <div class="mb-3"><span class="text-muted">المدة الفعلية:</span> {{ $actualDuration }}</div>
                    <div class="mb-3"><span class="text-muted">الحالة:</span> {{ $session->status }}</div>
                    <div><span class="text-muted">السعر:</span> {{ number_format((float) $session->price, 0) }}</div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card content-card border-0 mb-4">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-3">شات الجلسة</h2>
                    <div class="d-flex flex-column gap-3">
                        @forelse($session->messages as $message)
                            <div class="p-3 rounded border bg-light">
                                <div class="fw-semibold">{{ $message->sender_name }} <span class="text-muted small">({{ $message->sender_role }})</span></div>
                                <div style="white-space: pre-line;">{{ $message->message }}</div>
                                <div class="small text-muted mt-2">{{ $message->created_at?->format('Y-m-d H:i') }}</div>
                            </div>
                        @empty
                            <div class="text-muted">لا توجد رسائل في الجلسة.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="card content-card border-0">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-3">تسجيل الجلسة والملفات</h2>
                    @if($session->recording_public_url)
                        <a href="{{ $session->recording_public_url }}" class="btn btn-outline-primary mb-3" target="_blank">فتح التسجيل</a>
                    @else
                        <div class="text-muted mb-3">لا يوجد تسجيل متاح.</div>
                    @endif

                    @forelse($session->files as $file)
                        <div><a href="{{ $file->file_url }}" target="_blank">{{ $file->original_name }}</a></div>
                    @empty
                        <div class="text-muted">لا توجد ملفات مرفقة.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
