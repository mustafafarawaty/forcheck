<div class="student-topbar">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
            <div class="text-muted small mb-1">قسم الطالب</div>
            <h1 class="h3 mb-0 fw-bold">@yield('page_title', 'لوحة الطالب')</h1>
        </div>

        <div class="d-flex flex-wrap gap-2 align-items-center">
            <a href="{{ route('student.wallet.index') }}" class="student-topbar-card student-balance-card d-flex align-items-center gap-2">
                <i class="fas fa-wallet text-primary"></i>
                <span>الرصيد: {{ number_format((float) ($currentStudent->balance ?? 0), 0) }}</span>
            </a>
            <button type="button" class="student-topbar-card student-theme-toggle" data-theme-toggle>
                <i class="fas fa-moon"></i>
                <span>ليلي / نهاري</span>
            </button>
            <a href="{{ route('student.profile.edit') }}" class="student-topbar-card d-flex align-items-center gap-2">
                <i class="fas fa-user-pen text-primary"></i>
                <span>تعديل الحساب</span>
            </a>
            <div class="student-topbar-card d-flex align-items-center gap-2">
                <i class="fas fa-clock text-primary"></i>
                <span>ساعات هذا الشهر: {{ number_format($currentStudentMonthlyHours ?? 0, 1) }}</span>
            </div>
            @if($currentStudent)
                <form action="{{ route('student.logout') }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="student-btn-soft px-3 py-2">خروج</button>
                </form>
            @endif
        </div>
    </div>
</div>
