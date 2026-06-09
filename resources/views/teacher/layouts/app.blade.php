<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'قسم الأستاذ') | بعيد ليرن</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
@php($isLiveRoomRoute = request()->routeIs('teacher.sessions.room.*'))
<body class="teacher-body" data-theme-scope="teacher" data-app-timezone="{{ config('app.timezone') }}">
    @if($isLiveRoomRoute)
        <main class="teacher-live-room-main">
            @yield('content')
        </main>
    @else
        <div class="teacher-shell">
            <div class="teacher-frame">
                <div class="row g-0 flex-xl-nowrap">
                    @include('teacher.partials.sidebar')
                    <main class="col teacher-main">
                        @include('teacher.partials.topbar')
                        <div class="teacher-content">
                            @if(session('status'))
                                <div class="alert alert-success teacher-mobile-card mb-4">{{ session('status') }}</div>
                            @endif

                            @if($errors->any())
                                <div class="alert alert-danger teacher-mobile-card mb-4">
                                    <ul class="mb-0 ps-3">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if($currentTeacher && ! $currentTeacher->isApproved())
                                <div class="alert alert-warning teacher-mobile-card mb-4 position-sticky top-0 z-3 shadow-sm">
                                    <strong>حسابك قيد المراجعة من الإدارة.</strong>
                                    يمكنك استكشاف اللوحة، لكن الأزرار والحقول والإجراءات غير مفعلة حتى تتم الموافقة على طلب التسجيل.
                                </div>
                            @endif

                            @yield('content')
                        </div>
                    </main>
                </div>
            </div>
        </div>

        @if(session('status'))
            <div class="d-none" data-page-toast data-toast-type="success" data-toast-message="{{ session('status') }}"></div>
        @endif

        @if($errors->any())
            <div class="d-none" data-page-toast data-toast-type="danger" data-toast-message="{{ $errors->first() }}"></div>
        @endif

        <nav class="teacher-mobile-nav d-xl-none">
            <a href="{{ route('teacher.dashboard') }}" class="teacher-mobile-link {{ request()->routeIs('teacher.dashboard') ? 'active' : '' }}">
                <i class="fas fa-house"></i>
                <span>الرئيسية</span>
            </a>
            <a href="{{ route('teacher.subjects.index') }}" class="teacher-mobile-link {{ request()->routeIs('teacher.subjects.*') ? 'active' : '' }}">
                <i class="fas fa-book-open"></i>
                <span>المواد</span>
            </a>
            <a href="{{ route('teacher.availability.index') }}" class="teacher-mobile-link {{ request()->routeIs('teacher.availability.*') ? 'active' : '' }}">
                <i class="fas fa-calendar-days"></i>
                <span>المواعيد</span>
            </a>
            <a href="{{ route('teacher.sessions.index') }}" class="teacher-mobile-link {{ request()->routeIs('teacher.sessions.*') ? 'active' : '' }}">
                <i class="fas fa-video"></i>
                @if(($teacherUnreadCounts['sessions'] ?? 0) > 0)
                    <span class="teacher-menu-badge teacher-mobile-badge">{{ $teacherUnreadCounts['sessions'] }}</span>
                @endif
                <span>الجلسات</span>
            </a>
            <a href="{{ route('teacher.wallet.index') }}" class="teacher-mobile-link {{ request()->routeIs('teacher.wallet.*') ? 'active' : '' }}">
                <i class="fas fa-wallet"></i>
                <span>الرصيد</span>
            </a>
            <a href="{{ route('teacher.complaints.index') }}" class="teacher-mobile-link {{ request()->routeIs('teacher.complaints.*') ? 'active' : '' }}">
                <i class="fas fa-shield-heart"></i>
                <span>الشكاوى</span>
            </a>
            <a href="{{ route('teacher.profile.edit') }}" class="teacher-mobile-link {{ request()->routeIs('teacher.profile.*') ? 'active' : '' }}">
                <i class="fas fa-user"></i>
                <span>الحساب</span>
            </a>
        </nav>

        @if($currentTeacher)
            <div
                class="d-none"
                data-teacher-realtime
                data-realtime-channel="{{ app(\App\Services\Realtime\RealtimeChannelService::class)->teacherDashboardChannel($currentTeacher) }}"
                data-active-session='@json($teacherActiveSessionPayload)'
                data-poll-url="{{ route('teacher.instant.poll') }}"
            ></div>
        @endif

        @if(false && $currentTeacher)
            <div
                class="teacher-live-dock"
                data-live-request-indicator
                data-poll-url="{{ route('teacher.instant.poll') }}"
                data-realtime-channel="{{ app(\App\Services\Realtime\RealtimeChannelService::class)->teacherDashboardChannel($currentTeacher) }}"
                data-active-session='@json($teacherActiveSessionPayload)'
            >
                <form action="{{ route('teacher.instant.toggle') }}" method="POST" class="teacher-live-toggle-form" data-device-check-form>
                    @csrf
                    <input type="hidden" name="is_accepting_instant_sessions" value="{{ $currentTeacher->is_accepting_instant_sessions ? 0 : 1 }}">
                    <button type="submit" class="teacher-live-toggle-btn {{ $currentTeacher->is_accepting_instant_sessions ? 'is-active' : '' }}">
                        <i class="fas fa-bolt"></i>
                        <span>{{ $currentTeacher->is_accepting_instant_sessions ? 'استقبال الجلسات المباشرة مفعّل' : 'تفعيل الجلسات المباشرة' }}</span>
                        <strong class="teacher-live-badge d-none" data-live-request-count>0</strong>
                    </button>
                </form>

                <div class="teacher-live-request-card d-none" data-teacher-live-card>
                    <div class="fw-bold mb-1">طلب جلسة مباشرة</div>
                    <div class="small mb-1" data-live-toast-summary></div>
                    <div class="small text-muted mb-3" data-live-toast-meta></div>
                    <div class="d-flex gap-2">
                        <form method="POST" class="flex-fill" data-live-accept-form data-device-check-form>
                            @csrf
                            <button type="submit" class="btn btn-success w-100 btn-sm">قبول</button>
                        </form>
                        <form method="POST" class="flex-fill" data-live-reject-form>
                            @csrf
                            <button type="submit" class="btn btn-outline-danger w-100 btn-sm">رفض</button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        @if($currentTeacher)
            <div
                class="modal fade"
                id="teacherJoinSessionPrompt"
                tabindex="-1"
                aria-hidden="true"
                data-join-session-modal='@json($teacherActiveSessionPayload)'
            >
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content teacher-modal-card">
                        <div class="modal-header border-0 pb-0">
                            <h2 class="h5 fw-bold mb-0">حان وقت الجلسة</h2>
                            <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body pt-3">
                            <p class="mb-2 fw-semibold" data-join-session-summary>{{ $teacherActiveSessionPayload['subject_name'] ?? 'جلسة' }} مع {{ $teacherActiveSessionPayload['participant_name'] ?? 'الطالب' }}</p>
                            <p class="text-muted mb-4" data-join-session-time>{{ $teacherActiveSessionPayload['scheduled_at_label'] ?? '' }}</p>
                            <div class="d-flex flex-column flex-sm-row justify-content-end gap-2">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إغلاق</button>
                                <a href="{{ $teacherActiveSessionPayload['join_url'] ?? '#' }}" class="btn btn-primary" data-join-session-link>الانضمام للجلسة</a>
                                @if($teacherActiveSessionPayload && $teacherActiveSessionPayload['id'])
                                    <form action="{{ route('teacher.sessions.cancel', $teacherActiveSessionPayload['id']) }}" method="POST" class="m-0">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-danger">إلغاء الجلسة</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif

    @stack('modals')
    @if($currentTeacher && ! $currentTeacher->isApproved() && ! $isLiveRoomRoute)
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('form').forEach((form) => {
                    if (form.action.includes('/teacher/logout')) {
                        return;
                    }

                    form.querySelectorAll('input, select, textarea, button').forEach((control) => {
                        control.disabled = true;
                    });
                });

                document.querySelectorAll('[data-live-request-indicator] button, [data-live-request-toggle] button, .student-floating-booking-btn, a[data-join-session-link]').forEach((control) => {
                    control.classList.add('disabled');
                    control.setAttribute('aria-disabled', 'true');
                    control.addEventListener('click', (event) => event.preventDefault());
                });
            });
        </script>
    @endif
    @stack('scripts')
</body>
</html>
