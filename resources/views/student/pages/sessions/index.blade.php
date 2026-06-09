@extends('student.layouts.app')

@section('title', 'جلساتي')
@section('page_title', 'جلساتي')

@section('content')
<section class="row g-4">
    <div class="col-12">
        <div class="student-list-card student-mobile-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="student-section-title mb-0">الجلسات</div>

                <button
                    type="button"
                    class="student-btn-primary py-2 px-3"
                    data-bs-toggle="modal"
                    data-bs-target="#studentBookingModal"
                >
                    جلسة جديدة
                </button>
            </div>

            <div class="table-responsive">
                <table class="table align-middle table-hover sessions-table student-table">
                    <thead>
                        <tr>
                            <th class="d-none d-md-table-cell">#</th>
                            <th>المادة</th>
                            <th class="d-none d-md-table-cell">الأستاذ</th>
                            <th>الموعد</th>
                            <th>الحالة</th>
                            <th class="text-center">عرض</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($sessions as $session)
                            <tr>
                                <td class="d-none d-md-table-cell fw-bold">{{ $session->id }}</td>
                                <td class="small fw-semibold">{{ $session->subject?->name ?? 'بدون مادة' }}</td>
                                <td class="d-none d-md-table-cell">{{ $session->teacher?->name ?? 'أستاذ' }}</td>
                                <td>
                                    <div class="small fw-semibold">{{ $session->scheduled_at?->format('Y-m-d') }}</div>
                                    <div class="text-muted small">{{ $session->scheduled_at?->format('H:i') }}</div>
                                </td>
                                <td>
                                    @if($session->status === 'completed')
                                        <span class="badge bg-success">مكتملة</span>
                                    @elseif($session->status === 'cancelled')
                                        <span class="badge bg-danger">ملغية</span>
                                    @elseif($session->status === 'in_progress')
                                        <span class="badge bg-warning text-dark">جارية</span>
                                    @else
                                        <span class="badge bg-primary">قادمة</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('student.sessions.show', $session->id) }}" class="btn btn-sm btn-outline-primary w-100 w-md-auto">
                                        عرض
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">لا توجد جلسات</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $sessions->onEachSide(1)->links('vendor.pagination.ba3eed') }}
            </div>
        </div>
    </div>
</section>
@endsection
