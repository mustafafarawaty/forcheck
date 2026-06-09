@extends('teacher.layouts.app')

@section('title', 'الرئيسية')
@section('page_title', 'الرئيسية')

@section('content')
    <section class="teacher-panel teacher-hero mb-4">
        <div class="row align-items-center gy-4 position-relative" style="z-index: 1;">
            <div class="col-xl-7">
                <h2 class="display-6 fw-bold mb-4">كل ما تحتاجه لإدارة حصصك اليومية موجود هنا بشكل واضح وسريع.</h2>
                <div class="d-flex flex-wrap gap-2">
                    <div class="d-flex flex-column flex-sm-row gap-2">
                        <a
                            href="{{ $activeSessionPayload['join_url'] ?? '#' }}"
                            class="btn btn-light {{ $activeSessionPayload && $activeSessionPayload['can_join_now'] ? '' : 'd-none' }}"
                            data-quick-join-button="teacher"
                        >
                            الانضمام السريع للجلسة
                        </a>
                        @if($activeSessionPayload && $activeSessionPayload['id'])
                            <form action="{{ route('teacher.sessions.cancel', $activeSessionPayload['id']) }}" method="POST" class="m-0">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger d-none d-sm-inline-block">إلغاء الجلسة</button>
                            </form>
                        @endif
                    </div>
                    <a href="{{ route('teacher.sessions.index') }}" class="teacher-btn-soft">عرض الجلسات</a>
                    <a href="{{ route('teacher.availability.index') }}" class="teacher-btn-soft">إضافة موعد جديد</a>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="teacher-glass teacher-mobile-card">
                    @if($countdownSession)
                        <div class="small text-white-50 mb-2">الجلسة الأقرب خلال الـ10 ساعات القادمة</div>
                        <div class="fs-4 fw-bold mb-2">{{ $countdownSession->student_name }} - {{ $countdownSession->subject?->name ?? 'جلسة' }}</div>
                        <div class="small text-white-50 mb-3">{{ $countdownSession->scheduled_at?->format('Y-m-d H:i') }}</div>
                    @else
                        <div class="small text-white-50 mb-2">لا توجد جلسة تبدأ خلال الـ10 ساعات القادمة</div>
                        <div class="fs-5 fw-bold mb-2">يمكنك متابعة المواعيد أو تفعيل استقبال الجلسات المباشرة.</div>
                        <div class="small text-white-50">سيظهر أحدث الجلسات هنا تلقائيًا.</div>
                    @endif

                    <div class="d-flex flex-column gap-2">
                        <a
                            href="{{ $activeSessionPayload['join_url'] ?? '#' }}"
                            class="btn btn-light mt-3 w-100 {{ $activeSessionPayload && $activeSessionPayload['can_join_now'] ? '' : 'd-none' }}"
                            data-quick-join-button="teacher"
                        >
                            الانضمام الآن
                        </a>
                        @if($activeSessionPayload && $activeSessionPayload['id'])
                            <form action="{{ route('teacher.sessions.cancel', $activeSessionPayload['id']) }}" method="POST" class="m-0">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger w-100">إلغاء الجلسة</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-4 mb-4 teacher-stats-grid">
        <div class="col-md-6 col-xl-3">
            <div class="teacher-stat-card teacher-mobile-card">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div><div class="text-muted mb-2">عدد الجلسات</div><div class="display-6 fw-bold">{{ $stats['sessions_count'] }}</div></div>
                    <span class="teacher-stat-icon"><i class="fas fa-video"></i></span>
                </div>
                <div class="text-success fw-semibold">نشاط هذا الشهر</div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="teacher-stat-card teacher-mobile-card">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div><div class="text-muted mb-2">الطلاب الفعّالون</div><div class="display-6 fw-bold">{{ $stats['students_count'] }}</div></div>
                    <span class="teacher-stat-icon"><i class="fas fa-user-group"></i></span>
                </div>
                <div class="text-primary fw-semibold">عدد الطلاب الفريدين</div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="teacher-stat-card teacher-mobile-card">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div><div class="text-muted mb-2">أرباح الشهر</div><div class="display-6 fw-bold">{{ number_format($stats['monthly_profit'], 0) }}</div></div>
                    <span class="teacher-stat-icon"><i class="fas fa-wallet"></i></span>
                </div>
                <div class="text-success fw-semibold">ليرة سورية</div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="teacher-stat-card teacher-mobile-card">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div><div class="text-muted mb-2">الجلسات القادمة</div><div class="display-6 fw-bold">{{ $stats['upcoming_count'] }}</div></div>
                    <span class="teacher-stat-icon"><i class="fas fa-calendar-days"></i></span>
                </div>
                <div class="text-warning fw-semibold">تنتظر المتابعة</div>
            </div>
        </div>
    </section>

    <section class="row g-4 mb-4">
        <div class="col-12">
            <div
                class="teacher-chart teacher-mobile-card"
                data-teacher-chart
                data-chart-default="month"
                data-chart='@json($chart)'
            >
                <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                    <div>
                        <div class="teacher-section-title mb-1">مخطط الأرباح وعدد الجلسات</div>
                        <div class="text-muted">أعمدة للجلسات مع خط للأرباح عبر أسبوع أو شهر أو سنة.</div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="teacher-filter-pill teacher-chart-filter" data-range="week">أسبوع</button>
                        <button type="button" class="teacher-filter-pill teacher-chart-filter active" data-range="month">شهر</button>
                        <button type="button" class="teacher-filter-pill teacher-chart-filter" data-range="year">سنة</button>
                    </div>
                </div>

                <div class="teacher-chart-summary mt-4">
                    <div class="teacher-chart-kpi"><span class="teacher-chart-kpi-label">إجمالي الأرباح</span><strong data-chart-profit></strong></div>
                    <div class="teacher-chart-kpi"><span class="teacher-chart-kpi-label">عدد الجلسات</span><strong data-chart-sessions></strong></div>
                    <div class="teacher-chart-kpi"><span class="teacher-chart-kpi-label">المقارنة</span><strong data-chart-trend></strong></div>
                </div>

                <div class="teacher-chart-canvas mt-4" data-chart-canvas></div>
            </div>
        </div>
    </section>

    <section class="row g-4 mb-4">
        <div class="col-xl-6">
            <div class="teacher-list-card h-100 teacher-mobile-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="teacher-section-title">جلسات اليوم</div>
                    <a href="{{ route('teacher.sessions.index') }}" class="teacher-filter-pill">كل الجلسات</a>
                </div>
                @forelse($today_sessions as $session)
                    <a href="{{ route('teacher.sessions.index', ['selected_session_id' => $session->id]) }}" class="teacher-timeline-item teacher-timeline-link pb-4 d-block">
                        <div class="fw-semibold">{{ $session->subject?->name ?? 'من دون مادة' }} - {{ $session->student_name }}</div>
                        <div class="text-muted small">{{ $session->scheduled_at?->format('H:i') }} • {{ $session->status === 'cancelled' ? 'ملغية' : ($session->status === 'completed' ? 'مكتملة' : ($session->status === 'in_progress' ? 'جارية الآن' : 'قادمة')) }}</div>
                    </a>
                @empty
                    <div class="text-muted">لا توجد جلسات مجدولة اليوم.</div>
                @endforelse
            </div>
        </div>

        <div class="col-xl-6">
            <div class="teacher-list-card h-100 teacher-mobile-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="teacher-section-title">المواد التي تدرّسها</div>
                    <a href="{{ route('teacher.subjects.index') }}" class="teacher-filter-pill">إدارة المواد</a>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @forelse($subjects as $subject)
                        <span class="teacher-chip teacher-chip-soft">{{ $subject->name }} - {{ $subject->level }}</span>
                    @empty
                        <span class="text-muted">لم تتم إضافة مواد بعد.</span>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <section class="row g-4">
        <div class="col-12">
            <div class="teacher-list-card h-100 teacher-mobile-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="teacher-section-title">آخر الشكاوى</div>
                    <a href="{{ route('teacher.complaints.index') }}" class="teacher-filter-pill">عرض الكل</a>
                </div>
                @forelse($complaints as $complaint)
                    <div class="d-flex justify-content-between align-items-center py-3 {{ $loop->last ? '' : 'border-bottom' }}">
                        <div>
                            <div class="fw-semibold">{{ $complaint->title }}</div>
                            <div class="small text-muted">{{ $complaint->submitted_at?->format('Y-m-d H:i') }}</div>
                        </div>
                        <span class="teacher-chip {{ $complaint->status === 'resolved' ? 'teacher-chip-success' : 'teacher-chip-warning' }}">
                            {{ $complaint->status === 'resolved' ? 'تم الرد' : 'قيد المراجعة' }}
                        </span>
                    </div>
                @empty
                    <div class="text-muted">لا توجد شكاوى بعد.</div>
                @endforelse
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
@endpush
