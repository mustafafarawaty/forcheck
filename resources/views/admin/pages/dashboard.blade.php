@extends('admin.layouts.app')

@section('title', 'الرئيسية')
@section('page_title', 'لوحة الإدارة')

@section('content')
    <div class="row g-4">
        <div class="col-md-6 col-xl-3">
            <div class="small-box text-bg-primary metric-card">
                <div class="inner p-4">
                    <h3>1,284</h3>
                    <p class="mb-0">إجمالي الطلاب</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="small-box text-bg-success metric-card">
                <div class="inner p-4">
                    <h3>32</h3>
                    <p class="mb-0">الدورات النشطة</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="small-box text-bg-warning metric-card">
                <div class="inner p-4">
                    <h3>18</h3>
                    <p class="mb-0">المدرسون</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="small-box text-bg-danger metric-card">
                <div class="inner p-4">
                    <h3>92%</h3>
                    <p class="mb-0">رضا المستخدمين</p>
                </div>
            </div>
        </div>
    </div>
@endsection
