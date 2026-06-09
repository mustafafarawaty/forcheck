@extends('admin.layouts.app')

@section('title', 'جلسات الطالب')
@section('page_title', 'جلسات ' . $student->name)

@section('content')
    <div class="card content-card border-0">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table align-middle table-hover">
                    <thead><tr><th>الأستاذ</th><th>المادة</th><th>الموعد</th><th>الحالة</th><th>السعر</th><th>حالة الدفع</th><th>الشكاوي</th></tr></thead>
                    <tbody>
                        @forelse($sessions as $session)
                            <tr>
                                <td>{{ $session->teacher?->name ?? '-' }}</td>
                                <td>{{ $session->subject?->name ?? '-' }}</td>
                                <td>{{ $session->scheduled_at?->format('Y-m-d H:i') }}</td>
                                <td><span class="badge text-bg-secondary">{{ $session->status }}</span></td>
                                <td>{{ number_format((float) $session->price, 0) }}</td>
                                <td>{{ $session->payment_status }}</td>
                                <td>{{ $session->complaints->count() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">لا توجد جلسات.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $sessions->onEachSide(1)->links('vendor.pagination.ba3eed') }}
        </div>
    </div>
@endsection
