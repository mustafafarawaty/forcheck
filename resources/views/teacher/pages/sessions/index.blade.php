@extends('teacher.layouts.app')

@section('title', 'الجلسات')
@section('page_title', 'جلسات الأستاذ')

@section('content')
<div class="teacher-list-card teacher-mobile-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="teacher-section-title mb-0">الجلسات</div>
        <div class="small text-muted">{{ $sessions->count() }} جلسة</div>
    </div>

    <div class="table-responsive">
        <table class="table align-middle table-hover teacher-table teacher-sessions-table">
            <thead>
                <tr>
                    <th class="d-none d-md-table-cell">#</th>
                    <th>الطالب</th>
                    <th>المادة</th>
                    <th>الموعد</th>
                    <th>الحالة</th>
                    <th class="text-center">عرض</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sessions as $session)
                    <tr>
                        <td class="d-none d-md-table-cell fw-bold">{{ $session->id }}</td>
                        <td class="fw-semibold">{{ $session->student_name ?: ($session->student?->name ?? 'طالب') }}</td>
                        <td>{{ $session->subject?->name ?? 'بدون مادة' }}</td>
                        <td>
                            <div class="small fw-semibold">{{ $session->scheduled_at?->format('Y-m-d') }}</div>
                            <div class="text-muted small">{{ $session->scheduled_at?->format('H:i') }}</div>
                        </td>
                        <td>
                            @if($session->status === 'completed')
                                <span class="teacher-chip teacher-chip-success">مكتملة</span>
                            @elseif($session->status === 'cancelled')
                                <span class="teacher-chip teacher-chip-danger">ملغية</span>
                            @elseif($session->status === 'in_progress')
                                <span class="teacher-chip teacher-chip-warning">جارية</span>
                            @else
                                <span class="teacher-chip teacher-chip-soft">قادمة</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('teacher.sessions.show', $session->id) }}" class="btn btn-sm btn-outline-primary w-100 w-md-auto">
                                عرض
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">لا توجد جلسات حتى الآن</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $sessions->onEachSide(1)->links('vendor.pagination.ba3eed') }}
    </div>
</div>
@endsection
