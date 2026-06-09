@extends('admin.layouts.app')

@section('title', 'تفاصيل الأستاذ')
@section('page_title', 'طلب انضمام الأستاذ')

@section('content')
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card content-card border-0 h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <h2 class="h5 fw-bold mb-0">{{ $teacher->name }}</h2>
                        @if($teacher->approval_status === 'pending')
                            <span class="badge text-bg-warning">قيد المراجعة</span>
                        @elseif($teacher->approval_status === 'approved')
                            <span class="badge text-bg-success">مقبول</span>
                        @else
                            <span class="badge text-bg-secondary">مرفوض</span>
                        @endif
                    </div>

                    <div class="mb-3"><span class="text-muted">رقم الموبايل:</span> {{ $teacher->phone }}</div>
                    <div class="mb-3"><span class="text-muted">الاختصاص:</span> {{ $teacher->specialization ?: '-' }}</div>
                    <div class="mb-3"><span class="text-muted">المرحلة:</span> {{ $teacher->education_stage }}</div>
                    <div class="mb-3"><span class="text-muted">الرصيد:</span> {{ number_format((float) $teacher->balance, 0) }}</div>
                    <div class="mb-3"><span class="text-muted">المواد:</span> {{ $teacher->subjects_count }}</div>
                    <div class="mb-3"><span class="text-muted">الجلسات:</span> {{ $teacher->sessions_count }}</div>
                    <div class="mb-4"><span class="text-muted">نبذة:</span><br>{{ $teacher->about ?: '-' }}</div>

                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('admin.teachers.edit', $teacher) }}" class="btn btn-outline-secondary">
                            <i class="fas fa-pen"></i> تعديل البيانات
                        </a>
                        @if($teacher->certificate_url)
                            <a href="{{ $teacher->certificate_url }}" target="_blank" class="btn btn-outline-primary">
                                <i class="fas fa-file-lines"></i> عرض الشهادة
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card content-card border-0 h-100">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-3">مراجعة طلب الانضمام</h2>
                    <p class="text-muted mb-4">من هنا تتم الموافقة أو الرفض بعد مراجعة بيانات الأستاذ والشهادة ورقم الموبايل.</p>

                    @if($teacher->certificate_url)
                        <div class="border rounded p-3 mb-4 bg-light">
                            <div class="fw-semibold mb-2">الشهادة المرفوعة</div>
                            <a href="{{ $teacher->certificate_url }}" target="_blank">{{ $teacher->certificate_path }}</a>
                        </div>
                    @else
                        <div class="alert alert-warning">لا توجد شهادة مرفوعة لهذا الأستاذ.</div>
                    @endif

                    @if($teacher->approval_status === 'pending')
                        <div class="d-flex flex-wrap gap-2">
                            <form action="{{ route('admin.teachers.approve', $teacher) }}" method="POST">
                                @csrf
                                <button class="btn btn-success px-4" type="submit">
                                    <i class="fas fa-check"></i> قبول طلب الانضمام
                                </button>
                            </form>

                            <form action="{{ route('admin.teachers.reject', $teacher) }}" method="POST" onsubmit="return confirm('هل تريد رفض الطلب وحذف الحساب حذفاً ناعماً؟')">
                                @csrf
                                <button class="btn btn-outline-danger px-4" type="submit">
                                    <i class="fas fa-xmark"></i> رفض الطلب
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="alert alert-info mb-0">تمت مراجعة هذا الطلب مسبقاً.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
