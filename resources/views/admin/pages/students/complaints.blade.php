@extends('admin.layouts.app')

@section('title', 'شكاوي الطالب')
@section('page_title', 'شكاوي ' . $student->name)

@section('content')
    <div class="card content-card border-0">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table align-middle table-hover">
                    <thead><tr><th>العنوان</th><th>الأستاذ</th><th>الجلسة</th><th>المقدّم</th><th>الحالة</th><th>التاريخ</th><th>الوصف</th></tr></thead>
                    <tbody>
                        @forelse($complaints as $complaint)
                            <tr>
                                <td class="fw-semibold">{{ $complaint->title }}</td>
                                <td>{{ $complaint->teacher?->name ?? '-' }}</td>
                                <td>{{ $complaint->session?->subject?->name ?? '-' }}</td>
                                <td>{{ $complaint->submitted_by }}</td>
                                <td>{{ $complaint->status }}</td>
                                <td>{{ $complaint->submitted_at?->format('Y-m-d H:i') }}</td>
                                <td>{{ $complaint->description }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">لا توجد شكاوي.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $complaints->onEachSide(1)->links('vendor.pagination.ba3eed') }}
        </div>
    </div>
@endsection
