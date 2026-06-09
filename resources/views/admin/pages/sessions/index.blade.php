@extends('admin.layouts.app')

@section('title', 'الجلسات')
@section('page_title', 'كل الجلسات')

@section('content')
    <div class="card content-card border-0">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table align-middle table-hover">
                    <thead>
                        <tr>
                            <th>الأستاذ</th>
                            <th>الطالب</th>
                            <th>المادة</th>
                            <th>الموعد</th>
                            <th>المدة</th>
                            <th>الحالة</th>
                            <th>الشات</th>
                            <th>التسجيل</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sessions as $session)
                            <tr>
                                <td>{{ $session->teacher?->name ?? '-' }}</td>
                                <td>{{ $session->student?->name ?? $session->student_name ?? '-' }}</td>
                                <td>{{ $session->subject?->name ?? '-' }}</td>
                                <td>{{ $session->scheduled_at?->format('Y-m-d H:i') }}</td>
                                <td>{{ $session->duration_hours }} ساعة</td>
                                <td><span class="badge text-bg-secondary">{{ $session->status }}</span></td>
                                <td>{{ $session->messages->count() }}</td>
                                <td>{{ $session->recording_public_url ? 'متوفر' : 'غير متوفر' }}</td>
                                <td><a href="{{ route('admin.sessions.show', $session) }}" class="btn btn-sm btn-primary">التفاصيل</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">لا توجد جلسات.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $sessions->onEachSide(1)->links('vendor.pagination.ba3eed') }}
        </div>
    </div>
@endsection
