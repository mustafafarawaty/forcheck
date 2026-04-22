<div class="teacher-topbar">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
            <div class="text-muted small mb-1">قسم الأستاذ</div>
            <h1 class="h3 mb-0 fw-bold">@yield('page_title', 'لوحة الأستاذ')</h1>
        </div>

        <div class="d-flex flex-wrap gap-2 align-items-center">
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
