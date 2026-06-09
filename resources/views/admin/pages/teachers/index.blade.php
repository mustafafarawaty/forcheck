@extends('admin.layouts.app')

@section('title', 'الأساتذة')
@section('page_title', 'الأساتذة')

@section('content')
    <div class="card content-card border-0">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table align-middle table-hover">
                    <thead>
                        <tr>
                            <th>الاسم</th>
                            <th>الهاتف</th>
                            <th>الاختصاص</th>
                            <th>الرصيد</th>
                            <th>المواد</th>
                            <th>الجلسات</th>
                            <th>الحالة</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($teachers as $teacher)
                            <tr>
                                <td class="fw-semibold">{{ $teacher->name }}</td>
                                <td>{{ $teacher->phone }}</td>
                                <td>{{ $teacher->specialization ?: '-' }}</td>
                                <td>{{ number_format((float) $teacher->balance, 0) }}</td>
                                <td>{{ $teacher->subjects_count }}</td>
                                <td>{{ $teacher->sessions_count }}</td>
                                <td>
                                    @if($teacher->trashed())
                                        <span class="badge text-bg-dark">محذوف</span>
                                    @elseif($teacher->disabled_at)
                                        <span class="badge text-bg-danger">معطل</span>
                                    @elseif($teacher->approval_status === 'pending')
                                        <span class="badge text-bg-warning">قيد المراجعة</span>
                                    @elseif($teacher->approval_status === 'rejected')
                                        <span class="badge text-bg-secondary">مرفوض</span>
                                    @else
                                        <span class="badge text-bg-success">مقبول</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        @if(! $teacher->trashed())
                                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.teachers.edit', $teacher) }}"><i class="fas fa-pen"></i> تعديل</a>
                                            <a class="btn btn-sm btn-outline-info" href="{{ route('admin.teachers.show', $teacher) }}"><i class="fas fa-id-card"></i> التفاصيل</a>

                                            @if($teacher->approval_status === 'pending')
                                                <span class="small text-muted align-self-center">الموافقة من صفحة التفاصيل</span>
                                            @endif

                                            <form action="{{ route('admin.teachers.toggle-disabled', $teacher) }}" method="POST">
                                                @csrf
                                                <button class="btn btn-sm {{ $teacher->disabled_at ? 'btn-outline-success' : 'btn-outline-warning' }}" type="submit">
                                                    <i class="fas fa-ban"></i> {{ $teacher->disabled_at ? 'تفعيل' : 'تعطيل' }}
                                                </button>
                                            </form>

                                            <form action="{{ route('admin.teachers.destroy', $teacher) }}" method="POST" onsubmit="return confirm('هل تريد حذف حساب الأستاذ حذفاً ناعماً؟')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-dark" type="submit"><i class="fas fa-trash"></i> حذف</button>
                                            </form>
                                        @endif

                                        @if(! $teacher->trashed())
                                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.teachers.sessions', $teacher) }}"><i class="fas fa-video"></i> الجلسات</a>
                                            <a class="btn btn-sm btn-outline-success" href="{{ route('admin.teachers.wallet', $teacher) }}"><i class="fas fa-wallet"></i> الأرصدة</a>
                                            <a class="btn btn-sm btn-outline-danger" href="{{ route('admin.teachers.complaints', $teacher) }}"><i class="fas fa-shield-heart"></i> الشكاوى</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">لا يوجد أساتذة بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $teachers->onEachSide(1)->links('vendor.pagination.ba3eed') }}
        </div>
    </div>
@endsection
