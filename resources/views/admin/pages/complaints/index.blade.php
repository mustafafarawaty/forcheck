@extends('admin.layouts.app')

@section('title', 'الشكاوى')
@section('page_title', 'كل الشكاوى')

@section('content')
    <div class="card content-card border-0">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table align-middle table-hover">
                    <thead>
                        <tr>
                            <th>العنوان</th>
                            <th>الأستاذ</th>
                            <th>الطالب</th>
                            <th>الجلسة</th>
                            <th>المقدم</th>
                            <th>الحالة</th>
                            <th>التاريخ</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($complaints as $complaint)
                            <tr>
                                <td class="fw-semibold">{{ $complaint->title }}</td>
                                <td>{{ $complaint->teacher?->name ?? '-' }}</td>
                                <td>{{ $complaint->student?->name ?? $complaint->session?->student_name ?? '-' }}</td>
                                <td>{{ $complaint->session?->subject?->name ?? '-' }}</td>
                                <td>{{ $complaint->submitted_by }}</td>
                                <td>
                                    <span class="badge {{ $complaint->status === 'closed' ? 'text-bg-secondary' : ($complaint->status === 'completed' ? 'text-bg-success' : 'text-bg-warning') }}">
                                        {{ ['in_progress' => 'قيد المعالجة', 'completed' => 'مكتملة', 'closed' => 'مغلقة', 'pending' => 'جديدة'][$complaint->status] ?? $complaint->status }}
                                    </span>
                                </td>
                                <td>{{ $complaint->submitted_at?->format('Y-m-d H:i') }}</td>
                                <td>
                                    <a href="{{ route('admin.complaints.show', $complaint) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-comments"></i> رد
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">لا توجد شكاوى.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $complaints->onEachSide(1)->links('vendor.pagination.ba3eed') }}
        </div>
    </div>
@endsection
