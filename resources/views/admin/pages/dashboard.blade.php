@extends('admin.layouts.app')

@section('title', 'الرئيسية')
@section('page_title', 'لوحة الإدارة')

@section('content')
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="small-box text-bg-primary metric-card"><div class="inner p-4"><h3>{{ number_format($stats['students_count']) }}</h3><p class="mb-0">عدد الطلاب</p></div></div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="small-box text-bg-success metric-card"><div class="inner p-4"><h3>{{ number_format($stats['teachers_count']) }}</h3><p class="mb-0">عدد الأساتذة</p></div></div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="small-box text-bg-warning metric-card"><div class="inner p-4"><h3>{{ number_format($stats['sessions_count']) }}</h3><p class="mb-0">كل الجلسات</p></div></div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="small-box text-bg-danger metric-card"><div class="inner p-4"><h3>{{ number_format($stats['active_sessions_count']) }}</h3><p class="mb-0">الجلسات النشطة</p></div></div>
        </div>
    </div>

    <div class="content-card card border-0">
        <div class="card-body p-4">
            <div
                class="teacher-chart"
                data-teacher-chart
                data-chart-default="month"
                data-chart='@json($chart)'
            >
                <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                    <div>
                        <h2 class="h5 fw-bold mb-1">أرباح الإدارة من الجلسات</h2>
                        <div class="text-muted">تبديل العرض يومي أو شهري أو سنوي حسب الفترة المختارة.</div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="teacher-filter-pill teacher-chart-filter" data-range="week">يومي</button>
                        <button type="button" class="teacher-filter-pill teacher-chart-filter active" data-range="month">شهري</button>
                        <button type="button" class="teacher-filter-pill teacher-chart-filter" data-range="year">سنوي</button>
                    </div>
                </div>

                <div class="teacher-chart-summary mt-4">
                    <div class="teacher-chart-kpi"><span class="teacher-chart-kpi-label">إجمالي أرباح الإدارة</span><strong data-chart-profit></strong></div>
                    <div class="teacher-chart-kpi"><span class="teacher-chart-kpi-label">عدد الجلسات</span><strong data-chart-sessions></strong></div>
                    <div class="teacher-chart-kpi"><span class="teacher-chart-kpi-label">المؤشر</span><strong data-chart-trend></strong></div>
                </div>

                <div class="teacher-chart-canvas mt-4" data-chart-canvas></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
@endpush
