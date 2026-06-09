@extends('teacher.layouts.app')

@section('title', 'الشكاوى')
@section('page_title', 'الشكاوى المقدمة لإدارة المنصة')

@section('content')
    <div class="row g-4">
        <div class="col-xl-7">
            <div class="teacher-table-card h-100 teacher-mobile-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="teacher-section-title">سجل الشكاوى</div>
                    <span class="teacher-chip teacher-chip-soft">{{ $complaints->count() }} شكوى</span>
                </div>

                <div class="table-responsive">
                    <table class="table teacher-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>عنوان الشكوى</th>
                                <th>مرتبطة بـ</th>
                                <th>التاريخ</th>
                                <th>الحالة</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($complaints as $complaint)
                                <tr>
                                    <td>{{ $complaint->title }}</td>
                                    <td>{{ $complaint->session?->subject?->name ? $complaint->session->subject->name . ' - ' . $complaint->session->student_name : 'غير مرتبطة بجلسة' }}</td>
                                    <td>{{ $complaint->submitted_at?->format('Y-m-d h:i A') }}</td>
                                    <td>
                                        <span class="teacher-chip {{ $complaint->status === 'completed' ? 'teacher-chip-success' : ($complaint->status === 'closed' ? 'teacher-chip-danger' : 'teacher-chip-warning') }}">
                                            {{ ['in_progress' => 'قيد المعالجة', 'completed' => 'مكتملة', 'closed' => 'مغلقة', 'pending' => 'جديدة'][$complaint->status] ?? $complaint->status }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('teacher.complaints.show', $complaint->id) }}" class="btn btn-sm btn-outline-primary">رد</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">لا توجد شكاوى بعد.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="teacher-form-card mb-4 teacher-mobile-card">
                <div class="teacher-section-title mb-4">إضافة شكوى جديدة</div>

                <form action="{{ route('teacher.complaints.store') }}" method="POST" enctype="multipart/form-data" class="row g-3">
                    @csrf
                    <div class="col-12">
                        <label class="form-label fw-semibold">عنوان الشكوى</label>
                        <input type="text" name="title" value="{{ old('title') }}" class="form-control teacher-form-control" placeholder="مثال: مشكلة في تسجيل الجلسة">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">مرتبطة بأي جلسة؟</label>
                        <select name="teacher_session_id" class="form-select teacher-form-select">
                            <option value="">اختياري</option>
                            @foreach($sessions as $session)
                                <option value="{{ $session->id }}" @selected((string) old('teacher_session_id') === (string) $session->id)>
                                    {{ $session->subject?->name ?? 'من دون مادة' }} - {{ $session->student_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">وصف الشكوى</label>
                        <textarea name="description" class="form-control teacher-form-control" rows="5" placeholder="اشرح المشكلة بالتفصيل">{{ old('description') }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">إرفاق صورة (اختياري)</label>
                        <input type="file" name="attachment" accept="image/*" class="form-control teacher-form-control">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn teacher-btn-primary w-100">إرسال الشكوى</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
