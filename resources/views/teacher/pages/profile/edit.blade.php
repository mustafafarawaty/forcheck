@extends('teacher.layouts.app')

@section('title', 'الملف الشخصي')
@section('page_title', 'الملف الشخصي')

@section('content')
    <section class="teacher-list-card teacher-mobile-card">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4">
            <div>
                <div class="teacher-section-title mb-1">تعديل حساب الأستاذ</div>
                <div class="text-muted">عدّل بياناتك، صورتك الشخصية، والملف المرفق للشهادة عند الحاجة.</div>
            </div>
            <div class="teacher-profile-avatar">
                @if($teacher->avatar_url)
                    <img src="{{ $teacher->avatar_url }}" alt="{{ $teacher->name }}">
                @else
                    <i class="fas fa-user-tie"></i>
                @endif
            </div>
        </div>

        <form action="{{ route('teacher.profile.update') }}" method="POST" enctype="multipart/form-data" class="row g-3">
            @csrf
            @method('PUT')
            <div class="col-md-6">
                <label class="form-label fw-semibold">الاسم</label>
                <input type="text" name="name" value="{{ old('name', $teacher->name) }}" class="form-control teacher-form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">رقم الموبايل</label>
                <input type="tel" inputmode="numeric" name="phone" value="{{ old('phone', $teacher->phone) }}" class="form-control teacher-form-control" data-phone-input>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">المرحلة التعليمية</label>
                <select name="education_stage" class="form-select teacher-form-select">
                    @foreach($educationStages as $value => $label)
                        <option value="{{ $value }}" @selected(old('education_stage', $teacher->education_stage) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">الصورة الشخصية</label>
                <input type="file" name="avatar" class="form-control teacher-form-control" accept=".jpg,.jpeg,.png,.webp">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">التقييم الحالي</label>
                <div class="teacher-rating-panel">
                    <div class="teacher-rating-stars">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="fas fa-star {{ $i <= round((float) $teacher->rating_average) ? 'is-active' : '' }}"></i>
                        @endfor
                    </div>
                    <div class="small text-muted">
                        {{ $teacher->ratings_count > 0 ? number_format((float) $teacher->rating_average, 1) . ' من 5' : 'لا يوجد تقييمات بعد' }}
                    </div>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">نبذة</label>
                <textarea name="about" rows="4" class="form-control teacher-form-control" placeholder="نبذة قصيرة عن أسلوبك أو المواد التي تركز عليها">{{ old('about', $teacher->about) }}</textarea>
            </div>
            <div class="col-12">
                <button type="submit" class="btn teacher-btn-primary px-4">حفظ التعديلات</button>
            </div>
        </form>
    </section>
@endsection
