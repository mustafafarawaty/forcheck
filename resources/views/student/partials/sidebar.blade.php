<aside class="student-sidebar">
    <div class="student-brand">
        <div class="d-flex align-items-center gap-3 mb-3">
            <span class="student-brand-badge"><i class="fas fa-graduation-cap"></i></span>
            <div>
                <div class="fw-bold fs-5">قسم الطالب</div>
                <div class="small text-white-50">تعلم أبسط وحجز أسرع</div>
            </div>
        </div>

        @if($currentStudent)
            <div class="student-glass">
                <div class="small text-white-50 mb-1">الحساب الحالي</div>
                <div class="fw-bold">{{ $currentStudent->name }}</div>
                <div class="small text-white-50">{{ \App\Services\Teacher\TeacherSubjectService::levelLabels()[$currentStudent->study_level] ?? $currentStudent->study_level }}</div>
            </div>
        @endif
    </div>

    <div class="student-menu">
        <a href="{{ route('student.dashboard') }}" class="student-menu-link {{ request()->routeIs('student.dashboard') ? 'active' : '' }}">
            <i class="fas fa-sparkles"></i>
            <span>الرئيسية</span>
        </a>
        <a href="{{ route('student.teachers.index') }}" class="student-menu-link {{ request()->routeIs('student.teachers.*') ? 'active' : '' }}">
            <i class="fas fa-user-group"></i>
            <span>الأساتذة</span>
        </a>
        <a href="{{ route('student.sessions.index') }}" class="student-menu-link {{ request()->routeIs('student.sessions.*') ? 'active' : '' }}">
            <i class="fas fa-video"></i>
            <span>جلساتي</span>
        </a>
        <a href="{{ route('student.profile.edit') }}" class="student-menu-link {{ request()->routeIs('student.profile.*') ? 'active' : '' }}">
            <i class="fas fa-id-card"></i>
            <span>الملف الشخصي</span>
        </a>
    </div>
</aside>
