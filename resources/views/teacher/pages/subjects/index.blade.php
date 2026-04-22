@extends('teacher.layouts.app')

@section('title', 'المواد')
@section('page_title', 'المواد التي يتم تدريسها')

@section('content')
    <div class="row g-4">
        <div class="col-xl-7">
            <div class="teacher-list-card teacher-mobile-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="teacher-section-title">عرض المواد الحالية</div>
                    <span class="teacher-chip teacher-chip-soft">{{ $subjects->count() }} مادة</span>
                </div>

                <div class="row g-3">
                    @forelse($subjects as $subject)
                        <div class="col-md-6">
                            <div class="teacher-media-card h-100">
                                <div class="d-flex justify-content-between mb-3">
                                    <h3 class="h5 fw-bold mb-0">{{ $subject->name }}</h3>
                                    <span class="teacher-chip teacher-chip-success">{{ $subject->level }}</span>
                                </div>
                                <div class="text-muted">سعر الساعة: {{ number_format($subject->hourly_rate_syp) }} ل.س</div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="text-muted">لم تتم إضافة أي مادة حتى الآن.</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="teacher-form-card teacher-mobile-card">
                <div class="teacher-section-title mb-4">إضافة مادة جديدة</div>

                <form action="{{ route('teacher.subjects.store') }}" method="POST" class="row g-3">
                    @csrf
                    <div class="col-12">
                        <label class="form-label fw-semibold">اسم المادة</label>
                        <input type="text" name="name" value="{{ old('name') }}" class="form-control teacher-form-control" placeholder="مثال: الكيمياء">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">الفئة التعليمية</label>
                        <select name="level" class="form-select teacher-form-select">
                            @foreach($allowedLevels as $value => $label)
                                <option value="{{ $value }}" @selected(old('level') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">سعر الساعة بالليرة السورية</label>
                        <input type="number" name="hourly_rate_syp" value="{{ old('hourly_rate_syp') }}" class="form-control teacher-form-control" placeholder="100000" min="0" step="1">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn teacher-btn-primary w-100">إضافة المادة</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
