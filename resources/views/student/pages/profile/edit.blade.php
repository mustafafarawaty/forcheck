@extends('student.layouts.app')

@section('title', 'الملف الشخصي')
@section('page_title', 'الملف الشخصي')

@section('content')
    <section class="student-list-card student-mobile-card">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
            <div>
                <div class="student-section-title mb-1">تعديل حساب الطالب</div>
                <div class="text-muted">حدّث بياناتك الأساسية والفئة الدراسية والصورة الشخصية.</div>
            </div>
            <div class="student-profile-avatar">
                @if($student->avatar_url)
                    <img src="{{ $student->avatar_url }}" alt="{{ $student->name }}">
                @else
                    <i class="fas fa-user-graduate"></i>
                @endif
            </div>
        </div>

        <form action="{{ route('student.profile.update') }}" method="POST" enctype="multipart/form-data" class="row g-3">
            @csrf
            @method('PUT')
            <div class="col-md-6">
                <label class="form-label fw-semibold">الاسم</label>
                <input type="text" name="name" value="{{ old('name', $student->name) }}" class="form-control student-form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">رقم الموبايل</label>
                <input type="tel" inputmode="numeric" name="phone" value="{{ old('phone', $student->phone) }}" class="form-control student-form-control" data-phone-input>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">الفئة الدراسية</label>
                <select name="study_level" class="form-select student-form-select">
                    @foreach($studyLevels as $value => $label)
                        <option value="{{ $value }}" @selected(old('study_level', $student->study_level) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">الصورة الشخصية</label>
                <input type="file" name="avatar" class="form-control student-form-control" accept=".jpg,.jpeg,.png,.webp">
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">نبذة</label>
                <textarea name="about" rows="4" class="form-control student-form-control" placeholder="نبذة قصيرة عنك أو أهدافك الدراسية">{{ old('about', $student->about) }}</textarea>
            </div>
            <div class="col-12">
                <button type="submit" class="btn student-btn-primary px-4">حفظ التعديلات</button>
            </div>
        </form>
    </section>
@endsection
