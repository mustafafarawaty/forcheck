<aside class="teacher-sidebar">
    <div class="teacher-brand">
        <div class="d-flex align-items-center gap-3 mb-3">
            <span class="teacher-brand-badge"><i class="fas fa-chalkboard-teacher"></i></span>
            <div>
                <div class="fw-bold fs-5">قسم الأستاذ</div>
                <div class="small text-white-50">إدارة الجلسات التعليمية</div>
            </div>
        </div>

        @if($currentTeacher)
            <div class="teacher-glass">
                <div class="small text-white-50 mb-1">الحساب الحالي</div>
                <div class="fw-bold">{{ $currentTeacher->name }}</div>
                <div class="small text-white-50">{{ $currentTeacher->education_stage === 'university' ? 'مدرّس جامعي' : 'مدرّس ثانوي' }}</div>
                <div class="small mt-2">{{ $currentTeacher->is_accepting_instant_sessions ? 'يستقبل جلسات مباشرة الآن' : 'الحجز المباشر غير مفعّل' }}</div>
            </div>
        @endif
    </div>

    <div class="teacher-menu">
        <a href="{{ route('teacher.dashboard') }}" class="teacher-menu-link {{ request()->routeIs('teacher.dashboard') ? 'active' : '' }}">
            <i class="fas fa-sparkles"></i>
            <span>الرئيسية</span>
        </a>
        <a href="{{ route('teacher.subjects.index') }}" class="teacher-menu-link {{ request()->routeIs('teacher.subjects.*') ? 'active' : '' }}">
            <i class="fas fa-book-open"></i>
            <span>المواد الدراسية</span>
        </a>
        <a href="{{ route('teacher.availability.index') }}" class="teacher-menu-link {{ request()->routeIs('teacher.availability.*') ? 'active' : '' }}">
            <i class="fas fa-calendar-check"></i>
            <span>مواعيد الحجز</span>
        </a>
        <a href="{{ route('teacher.sessions.index') }}" class="teacher-menu-link {{ request()->routeIs('teacher.sessions.*') ? 'active' : '' }}">
            <i class="fas fa-video"></i>
            <span class="teacher-menu-badge {{ ($teacherUnreadCounts['sessions'] ?? 0) > 0 ? '' : 'd-none' }}" data-dashboard-role="teacher" data-dashboard-badge="sessions">{{ $teacherUnreadCounts['sessions'] ?? 0 }}</span>
            <span>الجلسات</span>
        </a>
        <a href="{{ route('teacher.wallet.index') }}" class="teacher-menu-link {{ request()->routeIs('teacher.wallet.*') ? 'active' : '' }}">
            <i class="fas fa-wallet"></i>
            <span class="teacher-menu-badge {{ ($teacherUnreadCounts['wallet'] ?? 0) > 0 ? '' : 'd-none' }}" data-dashboard-role="teacher" data-dashboard-badge="wallet">{{ $teacherUnreadCounts['wallet'] ?? 0 }}</span>
            <span>حركة الرصيد</span>
        </a>
        <a href="{{ route('teacher.complaints.index') }}" class="teacher-menu-link {{ request()->routeIs('teacher.complaints.*') ? 'active' : '' }}">
            <i class="fas fa-shield-heart"></i>
            <span class="teacher-menu-badge {{ ($teacherUnreadCounts['complaints'] ?? 0) > 0 ? '' : 'd-none' }}" data-dashboard-role="teacher" data-dashboard-badge="complaints">{{ $teacherUnreadCounts['complaints'] ?? 0 }}</span>
            <span>الشكاوى</span>
        </a>
        <a href="{{ route('teacher.profile.edit') }}" class="teacher-menu-link {{ request()->routeIs('teacher.profile.*') ? 'active' : '' }}">
            <i class="fas fa-id-card"></i>
            <span>الملف الشخصي</span>
        </a>
    </div>
</aside>
