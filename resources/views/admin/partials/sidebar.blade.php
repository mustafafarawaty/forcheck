<aside class="app-sidebar shadow" data-bs-theme="light">
    <div class="sidebar-brand">
        <a href="{{ route('admin.dashboard') }}" class="brand-link text-decoration-none">
            <span class="brand-text fw-bold text-primary">Ba3eedLearn Admin</span>
        </a>
    </div>
    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                <li class="nav-item">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-gauge-high"></i>
                        <p>الرئيسية</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.students.index') }}" class="nav-link {{ request()->routeIs('admin.students.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-user-graduate"></i>
                        <p>الطلاب</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.courses.index') }}" class="nav-link {{ request()->routeIs('admin.courses.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-book-open"></i>
                        <p>الدورات</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.reports.index') }}" class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-chart-line"></i>
                        <p>التقارير</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.settings.index') }}" class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-gear"></i>
                        <p>الإعدادات</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>
