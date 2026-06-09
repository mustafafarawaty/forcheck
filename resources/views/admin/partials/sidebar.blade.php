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
                        <i class="nav-icon fas fa-gauge-high"></i><p>الرئيسية</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.teachers.index') }}" class="nav-link {{ request()->routeIs('admin.teachers.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-chalkboard-teacher"></i><p>الأساتذة</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.students.index') }}" class="nav-link {{ request()->routeIs('admin.students.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-user-graduate"></i><p>الطلاب</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.sessions.index') }}" class="nav-link {{ request()->routeIs('admin.sessions.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-video"></i>
                        <span class="nav-link-badge badge text-bg-primary ms-2 {{ ($adminUnreadCounts['sessions'] ?? 0) > 0 ? '' : 'd-none' }}" data-dashboard-role="admin" data-dashboard-badge="sessions">{{ $adminUnreadCounts['sessions'] ?? 0 }}</span>
                        <p>الجلسات</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.wallet.index') }}" class="nav-link {{ request()->routeIs('admin.wallet.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-wallet"></i>
                        <span class="nav-link-badge badge text-bg-warning ms-2 {{ ($adminUnreadCounts['wallet'] ?? 0) > 0 ? '' : 'd-none' }}" data-dashboard-role="admin" data-dashboard-badge="wallet">{{ $adminUnreadCounts['wallet'] ?? 0 }}</span>
                        <p>الأرصدة</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.complaints.index') }}" class="nav-link {{ request()->routeIs('admin.complaints.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-shield-heart"></i>
                        <span class="nav-link-badge badge text-bg-danger ms-2 {{ ($adminUnreadCounts['complaints'] ?? 0) > 0 ? '' : 'd-none' }}" data-dashboard-role="admin" data-dashboard-badge="complaints">{{ $adminUnreadCounts['complaints'] ?? 0 }}</span>
                        <p>الشكاوى</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.settings.index') }}" class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-gear"></i><p>الإعدادات</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>
