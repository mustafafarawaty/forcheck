<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'قسم الطالب') | بعيد ليرن</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="student-body" data-theme-scope="student" data-app-timezone="{{ config('app.timezone') }}">
    <div class="student-shell">
        <div class="student-frame">
            <div class="row g-0 flex-xl-nowrap">
                @include('student.partials.sidebar')
                <main class="col student-main">
                    @include('student.partials.topbar')
                    <div class="student-content">
                        @if(session('status'))
                            <div class="alert alert-success student-mobile-card mb-4">{{ session('status') }}</div>
                        @endif

                        @if($errors->any())
                            <div class="alert alert-danger student-mobile-card mb-4">
                                <ul class="mb-0 ps-3">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @yield('content')
                    </div>
                </main>
            </div>
        </div>
    </div>

    <nav class="student-mobile-nav d-xl-none">
        <a href="{{ route('student.dashboard') }}" class="student-mobile-link {{ request()->routeIs('student.dashboard') ? 'active' : '' }}">
            <i class="fas fa-house"></i>
            <span>الرئيسية</span>
        </a>
        <a href="{{ route('student.teachers.index') }}" class="student-mobile-link {{ request()->routeIs('student.teachers.*') ? 'active' : '' }}">
            <i class="fas fa-user-group"></i>
            <span>الأساتذة</span>
        </a>
        <a href="{{ route('student.sessions.index') }}" class="student-mobile-link {{ request()->routeIs('student.sessions.*') ? 'active' : '' }}">
            <i class="fas fa-video"></i>
            <span>الجلسات</span>
        </a>
        <a href="{{ route('student.profile.edit') }}" class="student-mobile-link {{ request()->routeIs('student.profile.*') ? 'active' : '' }}">
            <i class="fas fa-user"></i>
            <span>الحساب</span>
        </a>
    </nav>

    @if($currentStudent)
        <div
            class="d-none"
            data-student-realtime
            data-realtime-channel="{{ app(\App\Services\Realtime\RealtimeChannelService::class)->studentDashboardChannel($currentStudent) }}"
            data-active-session='@json($studentActiveSessionPayload)'
        ></div>

        <button type="button" class="student-floating-booking-btn" data-bs-toggle="modal" data-bs-target="#studentBookingModal">
            <i class="fas fa-plus"></i>
            <span>حجز جلسة</span>
        </button>

        @include('student.partials.booking-modal')
    @endif

    @if($currentStudent && ! request()->routeIs('student.sessions.room.*'))
        <div
            class="modal fade"
            id="studentJoinSessionPrompt"
            tabindex="-1"
            aria-hidden="true"
            data-join-session-modal='@json($studentActiveSessionPayload)'
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content student-modal-card">
                    <div class="modal-header border-0 pb-0">
                        <h2 class="h5 fw-bold mb-0">حان وقت الجلسة</h2>
                        <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <p class="mb-2 fw-semibold" data-join-session-summary>{{ $studentActiveSessionPayload['subject_name'] ?? 'جلسة' }} مع {{ $studentActiveSessionPayload['participant_name'] ?? 'الأستاذ' }}</p>
                        <p class="text-muted mb-4" data-join-session-time>{{ $studentActiveSessionPayload['scheduled_at_label'] ?? '' }}</p>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إغلاق</button>
                            <a href="{{ $studentActiveSessionPayload['join_url'] ?? '#' }}" class="btn btn-primary" data-join-session-link>الانضمام للجلسة</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @stack('modals')
    @stack('scripts')
</body>
</html>
