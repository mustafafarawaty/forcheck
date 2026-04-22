<nav class="app-header navbar navbar-expand bg-white shadow-sm">
    <div class="container-fluid">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                    <i class="fas fa-bars"></i>
                </a>
            </li>
            <li class="nav-item d-none d-md-block">
                <a href="{{ route('admin.dashboard') }}" class="nav-link">لوحة التحكم</a>
            </li>
            <li class="nav-item d-none d-md-block">
                <a href="{{ route('teacher.dashboard') }}" class="nav-link">قسم الأستاذ</a>
            </li>
        </ul>
        <ul class="navbar-nav ms-auto align-items-center">
            <li class="nav-item me-2">
                <span class="badge text-bg-primary px-3 py-2">نسخة الإدارة</span>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-lte-toggle="fullscreen" href="#" role="button">
                    <i class="fas fa-expand"></i>
                </a>
            </li>
        </ul>
    </div>
</nav>
