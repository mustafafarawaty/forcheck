@extends('student.layouts.app')

@section('title', 'الأساتذة')
@section('page_title', 'الأساتذة')

@section('content')
    <section class="student-list-card student-mobile-card mb-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
                <div class="student-section-title mb-1">استكشف الأساتذة</div>
                <div class="text-muted">يظهر هنا فقط الأساتذة المناسبون للفئة الدراسية الحالية الخاصة بك.</div>
            </div>
            <form action="{{ route('student.sessions.book') }}" method="POST" class="row g-2 align-items-end">
                @csrf
                <div class="col-sm-8">
                    <label class="form-label fw-semibold mb-1">حجز سريع</label>
                    <select name="subject_name" class="form-select student-form-select">
                        @foreach($subjectOptions as $subjectName)
                            <option value="{{ $subjectName }}">{{ $subjectName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-4">
                    <button type="submit" class="btn student-btn-primary w-100">بحث عشوائي</button>
                </div>
            </form>
        </div>
    </section>

    <section class="row g-4">
        @forelse($teachers as $teacher)
            <div class="col-md-6 col-xl-4">
                <a href="{{ route('student.teachers.show', $teacher->id) }}" class="student-teacher-card d-block h-100">
                    <div class="student-teacher-avatar-wrap">
                        <div class="student-teacher-avatar">
                            @if($teacher->avatar_url)
                                <img src="{{ $teacher->avatar_url }}" alt="{{ $teacher->name }}">
                            @else
                                <i class="fas fa-user-tie"></i>
                            @endif
                        </div>
                    </div>

                    <div class="text-center mb-3">
                        <h3 class="h5 fw-bold mb-1">{{ $teacher->name }}</h3>
                        <div class="text-muted small">{{ $teacher->is_accepting_instant_sessions ? 'يستقبل جلسات مباشرة الآن' : 'يعتمد على المواعيد المحددة' }}</div>
                    </div>

                    <div class="student-rating-row mb-3">
                        <div class="student-rating-stars">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="fas fa-star {{ $i <= round((float) $teacher->rating_average) ? 'is-active' : '' }}"></i>
                            @endfor
                        </div>
                        <div class="small fw-semibold">
                            {{ $teacher->ratings_count > 0 ? number_format((float) $teacher->rating_average, 1) : 'جديد' }}
                        </div>
                    </div>

                    <div class="d-flex justify-content-center mb-3">
                        <span class="student-chip {{ $teacher->is_accepting_instant_sessions ? 'student-chip-success' : 'student-chip-soft' }}">
                            {{ $teacher->is_accepting_instant_sessions ? 'مباشر' : 'مواعيد' }}
                        </span>
                    </div>

                    <div class="d-flex flex-wrap justify-content-center gap-2 mb-3">
                        @foreach($teacher->subjects as $subject)
                            <span class="student-chip student-chip-soft">{{ $subject->name }}</span>
                        @endforeach
                    </div>

                    <div class="small text-muted text-center">
                        {{ $teacher->availabilities->count() }} مواعيد متاحة
                    </div>
                </a>
            </div>
        @empty
            <div class="col-12">
                <div class="student-list-card student-mobile-card text-muted">لا يوجد أساتذة مناسبون للفئة الدراسية الحالية حتى الآن.</div>
            </div>
        @endforelse
    </section>
@endsection
