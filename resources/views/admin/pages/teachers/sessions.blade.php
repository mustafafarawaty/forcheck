@extends('admin.layouts.app')

@section('title', 'جلسات الأستاذ')
@section('page_title', 'جلسات ' . $teacher->name)

@section('content')
    <div class="card content-card border-0">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table align-middle table-hover">
                    <thead><tr><th>الطالب</th><th>المادة</th><th>الموعد</th><th>الحالة</th><th>السعر</th><th>ربح الأستاذ</th><th>نسبة الإدارة</th><th>الشكاوي</th></tr></thead>
                    <tbody>
                        @forelse($sessions as $session)
                            <tr>
                                <td>{{ $session->student?->name ?? $session->student_name }}</td>
                                <td>{{ $session->subject?->name ?? '-' }}</td>
                                <td>{{ $session->scheduled_at?->format('Y-m-d H:i') }}</td>
                                <td><span class="badge text-bg-secondary">{{ $session->status }}</span></td>
                                <td>{{ number_format((float) $session->price, 0) }}</td>
                                <td>{{ number_format((float) $session->teacher_earning_amount, 0) }}</td>
                                <td>{{ number_format((float) $session->admin_commission_percentage, 2) }}%</td>
                                <td>{{ $session->complaints->count() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">لا توجد جلسات.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $sessions->onEachSide(1)->links('vendor.pagination.ba3eed') }}
        </div>
    </div>
@endsection
