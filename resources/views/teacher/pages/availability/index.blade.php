@extends('teacher.layouts.app')

@php
    $dayLabels = [0 => 'الأحد', 1 => 'الاثنين', 2 => 'الثلاثاء', 3 => 'الأربعاء', 4 => 'الخميس', 5 => 'الجمعة', 6 => 'السبت'];
@endphp

@section('title', 'مواعيد الحجز')
@section('page_title', 'مواعيد الحجز المتاحة')

@section('content')
    <div class="row g-4">
        <div class="col-xl-7">
            <div class="teacher-list-card teacher-mobile-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="teacher-section-title">المواعيد الحالية</div>
                    <span class="teacher-chip teacher-chip-soft">{{ $availabilityWindows->count() }} موعدًا</span>
                </div>

                <div class="row g-3">
                    @forelse($availabilityWindows as $window)
                        <div class="col-md-6">
                            <div class="teacher-media-card">
                                <div class="fw-bold mb-2">{{ $window['day_label'] }}</div>
                                <div class="text-muted mb-2">{{ $window['starts_label'] }} - {{ $window['ends_label'] }}</div>
                                <div class="small text-muted mb-3">{{ $window['subject_name'] ?? 'كل المواد / غير مرتبط بمادة' }}</div>
                                <span class="teacher-chip teacher-chip-success">متاح للحجز</span>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="text-muted">لا توجد مواعيد مضافة بعد.</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="teacher-form-card teacher-mobile-card">
                <div class="teacher-section-title mb-4">إضافة موعد جديد</div>

                <form action="{{ route('teacher.availability.store') }}" method="POST" class="row g-3">
                    @csrf
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">اليوم</label>
                        <select name="day_of_week" class="form-select teacher-form-select">
                            @foreach($dayLabels as $key => $label)
                                <option value="{{ $key }}" @selected((string) old('day_of_week') === (string) $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">المادة</label>
                        <select name="teacher_subject_id" class="form-select teacher-form-select">
                            <option value="">اختياري</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}" @selected((string) old('teacher_subject_id') === (string) $subject->id)>{{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">من</label>
                        <input type="time" name="starts_at" value="{{ old('starts_at') }}" class="form-control teacher-form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">إلى</label>
                        <input type="time" name="ends_at" value="{{ old('ends_at') }}" class="form-control teacher-form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">ملاحظات</label>
                        <textarea name="notes" class="form-control teacher-form-control" rows="4" placeholder="مثال: جلسة مراجعة أو دعم إضافي">{{ old('notes') }}</textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn teacher-btn-primary w-100">حفظ الموعد</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
