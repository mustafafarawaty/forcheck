<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'لوحة الإدارة') | بعيد ليرن</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary admin-shell">
    <div class="app-wrapper">
        @include('admin.partials.header')
        @include('admin.partials.sidebar')
        <main class="app-main">
            <div class="app-content-header">
                <div class="container-fluid">
                    <div class="row align-items-center gy-3">
                        <div class="col-sm-6">
                            <h1 class="mb-0 fs-3 fw-bold">@yield('page_title', 'لوحة الإدارة')</h1>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end mb-0">
                                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">الإدارة</a></li>
                                <li class="breadcrumb-item active" aria-current="page">@yield('page_title', 'لوحة الإدارة')</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            <div class="app-content">
                <div class="container-fluid">
                    @if(session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0 ps-3">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </div>
        </main>
        @include('admin.partials.footer')
    </div>
    <div class="d-none" data-admin-realtime data-realtime-channel="{{ app(\App\Services\Realtime\RealtimeChannelService::class)->adminDashboardChannel() }}"></div>
    @stack('scripts')
</body>
</html>
