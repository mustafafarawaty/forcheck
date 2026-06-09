@extends('student.layouts.app')

@section('title', 'الرئيسية')
@section('page_title', 'الرئيسية')

@section('content')
    <section class="student-panel student-hero mb-4">
        <div class="row align-items-center gy-4 position-relative" style="z-index: 1;">
            <div class="col-xl-7">
                <h2 class="display-6 fw-bold mb-3">مساحة عملية تركز على الحجز السريع ومتابعة جلساتك بدون تعقيد.</h2>
                <p class="mb-4 text-white-50 fs-5">احجز جلستك القادمة، راقب تقدمك، وادخل مباشرة إلى الأساتذة أو جلساتك من مكان واحد.</p>
                <div class="d-flex flex-wrap gap-2">
                    <div class="d-flex flex-column flex-sm-row gap-2">
                        <a
                            href="{{ $activeSessionPayload['join_url'] ?? '#' }}"
                            class="btn btn-light {{ $activeSessionPayload && $activeSessionPayload['can_join_now'] ? '' : 'd-none' }}"
                            data-quick-join-button="student"
                        >
                            الانضمام السريع للجلسة
                        </a>
                        @if($activeSessionPayload && $activeSessionPayload['id'])
                            <form action="{{ route('student.sessions.cancel', $activeSessionPayload['id']) }}" method="POST" class="m-0">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger d-none d-sm-inline-block">إلغاء الجلسة</button>
                            </form>
                        @endif
                    </div>
                    <a href="{{ route('student.teachers.index') }}" class="student-btn-soft">استعرض الأساتذة</a>
                    <a href="{{ route('student.sessions.index') }}" class="student-btn-soft">جلساتي</a>
                </div>
            </div>

            <div class="col-xl-5">
                <div class="student-glass student-mobile-card">
                    <div class="{{ $activeSessionPayload ? '' : 'd-none' }}" data-quick-join-card="student">
                        <div class="fw-bold fs-5 mb-2">الجلسة جاهزة الآن</div>
                        <div class="text-white-50 mb-2" data-quick-join-summary="student">
                            @if($activeSessionPayload)
                                {{ $activeSessionPayload['subject_name'] }} مع {{ $activeSessionPayload['participant_name'] }}
                            @endif
                        </div>
                        <div class="text-white-50 mb-3" data-quick-join-time="student">{{ $activeSessionPayload['scheduled_at_label'] ?? '' }}</div>
                        <div class="d-flex flex-column gap-2">
                            <a
                                href="{{ $activeSessionPayload['join_url'] ?? '#' }}"
                                class="btn btn-light w-100 mb-4 {{ $activeSessionPayload && $activeSessionPayload['can_join_now'] ? '' : 'd-none' }}"
                                data-quick-join-button="student"
                            >
                                الانضمام الآن
                            </a>
                            @if($activeSessionPayload && $activeSessionPayload['id'])
                                <form action="{{ route('student.sessions.cancel', $activeSessionPayload['id']) }}" method="POST" class="m-0">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger w-100">إلغاء الجلسة</button>
                                </form>
                            @endif
                        </div>
                    </div>
                    <div class="fw-bold fs-5 mb-3">خطوات سريعة</div>
                    <div class="student-quick-points">
                        <div class="student-quick-point"><i class="fas fa-book-open"></i><span>اختر المادة المناسبة لك أولًا.</span></div>
                        <div class="student-quick-point"><i class="fas fa-user-group"></i><span>استعرض الأساتذة أو اطلب بحثًا مباشرًا.</span></div>
                        <div class="student-quick-point"><i class="fas fa-calendar-check"></i><span>تابع الجلسات القادمة وأكد حضورك عند الحاجة.</span></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-4 mb-4">
        <div class="col-12">
            <div class="student-chart student-mobile-card" data-student-chart data-chart-default="month" data-chart='@json($chart)'>
                <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                    <div>
                        <div class="student-section-title mb-1">عدد الجلسات والساعات</div>
                        <div class="text-muted">أعمدة للجلسات مع خط للساعات عبر أسبوع أو شهر أو سنة.</div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="student-filter-pill student-chart-filter" data-range="week">أسبوع</button>
                        <button type="button" class="student-filter-pill student-chart-filter active" data-range="month">شهر</button>
                        <button type="button" class="student-filter-pill student-chart-filter" data-range="year">سنة</button>
                    </div>
                </div>

                <div class="student-chart-summary mt-4">
                    <div class="student-chart-kpi"><span class="student-chart-kpi-label">إجمالي الساعات</span><strong data-chart-hours></strong></div>
                    <div class="student-chart-kpi"><span class="student-chart-kpi-label">عدد الجلسات</span><strong data-chart-sessions></strong></div>
                    <div class="student-chart-kpi"><span class="student-chart-kpi-label">المقارنة</span><strong data-chart-trend></strong></div>
                </div>

                <div class="student-chart-canvas mt-4" data-chart-canvas></div>
            </div>
        </div>
    </section>

    <section class="row g-4">
        <div class="col-lg-6">
            <div class="student-list-card h-100 student-mobile-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="student-section-title">المواد التي أخذت بها جلسات</div>
                    <a href="{{ route('student.teachers.index') }}" class="student-filter-pill">استعرض الأساتذة</a>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @forelse($subjects as $subject)
                        <span class="student-chip student-chip-soft">{{ $subject }}</span>
                    @empty
                        <span class="text-muted">لا توجد جلسات سابقة بعد.</span>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="student-list-card h-100 student-mobile-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="student-section-title">الحجوزات والتنبيهات الأخيرة</div>
                    <a href="{{ route('student.sessions.index') }}" class="student-filter-pill">كل الجلسات</a>
                </div>
                @forelse($upcomingSessions as $session)
                    <div class="py-3 {{ $loop->last ? '' : 'border-bottom' }}">
                        <div class="fw-semibold">{{ $session->subject?->name ?? 'بدون مادة' }} مع {{ $session->teacher?->name ?? 'أستاذ' }}</div>
                        <div class="small text-muted mb-2">{{ $session->scheduled_at?->format('Y-m-d H:i') }}</div>
                        @if(! $session->student_confirmed_at)
                            <form action="{{ route('student.sessions.confirm', $session->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-outline-primary btn-sm">تأكيد الحضور</button>
                            </form>
                        @endif
                    </div>
                @empty
                    @forelse($liveRequests as $liveRequest)
                        <div class="py-3 {{ $loop->last ? '' : 'border-bottom' }}">
                            <div class="fw-semibold">{{ $liveRequest->subject?->name }} مع {{ $liveRequest->teacher?->name }}</div>
                            <div class="small text-muted">طلب جلسة مباشرة - {{ $liveRequest->status }}</div>
                        </div>
                    @empty
                        <div class="text-muted">لا توجد جلسات أو طلبات حديثة.</div>
                    @endforelse
                @endforelse
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
@endpush
