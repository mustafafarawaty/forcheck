<div class="teacher-topbar">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
            <div class="text-muted small mb-1">قسم الأستاذ</div>
            <h1 class="h3 mb-0 fw-bold">@yield('page_title', 'لوحة الأستاذ')</h1>
        </div>

        <div class="d-flex flex-wrap gap-2 align-items-center">
            @if($currentTeacher)
                <div
                    class="teacher-live-navbar"
                    data-live-request-indicator
                    data-poll-url="{{ route('teacher.instant.poll') }}"
                    data-presence-url="{{ route('teacher.instant.heartbeat') }}"
                    data-offline-url="{{ route('teacher.instant.offline') }}"
                    data-realtime-channel="{{ app(\App\Services\Realtime\RealtimeChannelService::class)->teacherDashboardChannel($currentTeacher) }}"
                    data-active-session='@json($teacherActiveSessionPayload)'
                >
                    <form
                        action="{{ route('teacher.instant.toggle') }}"
                        method="POST"
                        class="teacher-live-toggle-form m-0"
                        data-device-check-form
                    >
                        @csrf
                        <input type="hidden" name="is_accepting_instant_sessions" value="0">
                        <label
                            class="teacher-live-switch"
                            title="هذا الخيار يتيح لك تلقي جلسات مباشرة من الطلاب من دون موعد مسبق"
                        >
                            <span class="teacher-live-switch-label">الجلسات المباشرة</span>
                            <input
                                type="checkbox"
                                name="is_accepting_instant_sessions"
                                value="1"
                                data-live-availability-switch
                                @checked($currentTeacher->is_accepting_instant_sessions)
                            >
                            <span class="teacher-live-switch-track" aria-hidden="true">
                                <span class="teacher-live-switch-thumb"></span>
                            </span>
                            <strong class="teacher-live-badge d-none" data-live-request-count>0</strong>
                        </label>
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
            <a href="{{ route('teacher.wallet.index') }}" class="teacher-topbar-card teacher-balance-card d-flex align-items-center gap-2">
                <i class="fas fa-wallet text-primary"></i>
                <span>الرصيد: {{ number_format((float) ($currentTeacher->balance ?? 0), 0) }}</span>
            </a>
            <button type="button" class="teacher-topbar-card border-0" data-theme-toggle>
                <i class="fas fa-moon text-primary"></i>
                <span>ليلي / نهاري</span>
            </button>
            <a href="{{ route('teacher.profile.edit') }}" class="teacher-topbar-card d-flex align-items-center gap-2">
                <i class="fas fa-user-pen text-primary"></i>
                <span>تعديل الحساب</span>
            </a>
            @if($currentTeacher)
                <form action="{{ route('teacher.logout') }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="teacher-btn-soft px-3 py-2">خروج</button>
                </form>
            @endif
        </div>
    </div>
</div>
