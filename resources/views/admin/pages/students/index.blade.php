@extends('admin.layouts.app')

@section('title', 'الطلاب')
@section('page_title', 'إدارة الطلاب')

@section('content')
    <div class="card content-card">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>الاسم</th>
                            <th>البريد</th>
                            <th>المسار</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>أحمد خالد</td>
                            <td>ahmad@example.com</td>
                            <td>Laravel</td>
                            <td><span class="badge text-bg-success">نشط</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
