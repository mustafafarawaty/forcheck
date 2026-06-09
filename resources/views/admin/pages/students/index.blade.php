@extends('admin.layouts.app')

@section('title', 'الطلاب')
@section('page_title', 'الطلاب')

@section('content')
    <div class="card content-card border-0">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table align-middle table-hover">
                    <thead>
                        <tr>
                            <th>الاسم</th>
                            <th>الهاتف</th>
                            <th>المستوى</th>
                            <th>الرصيد</th>
                            <th>الجلسات</th>
                            <th>الحالة</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $student)
                            <tr>
                                <td class="fw-semibold">{{ $student->name }}</td>
                                <td>{{ $student->phone }}</td>
                                <td>{{ $student->study_level }}</td>
                                <td>{{ number_format((float) $student->balance, 0) }}</td>
                                <td>{{ $student->sessions_count }}</td>
                                <td>
                                    @if($student->trashed())
                                        <span class="badge text-bg-dark">محذوف</span>
                                    @elseif($student->disabled_at)
                                        <span class="badge text-bg-danger">معطل</span>
                                    @else
                                        <span class="badge text-bg-success">فعال</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        @if(! $student->trashed())
                                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.students.edit', $student) }}"><i class="fas fa-pen"></i> تعديل</a>
                                            <form action="{{ route('admin.students.toggle-disabled', $student) }}" method="POST">
                                                @csrf
                                                <button class="btn btn-sm {{ $student->disabled_at ? 'btn-outline-success' : 'btn-outline-warning' }}" type="submit">
                                                    <i class="fas fa-ban"></i> {{ $student->disabled_at ? 'تفعيل' : 'تعطيل' }}
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.students.destroy', $student) }}" method="POST" onsubmit="return confirm('هل تريد حذف حساب الطالب حذفاً ناعماً؟')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-dark" type="submit"><i class="fas fa-trash"></i> حذف</button>
                                            </form>
                                        @endif

                                        @if(! $student->trashed())
                                            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.students.sessions', $student) }}"><i class="fas fa-video"></i> الجلسات</a>
                                            <a class="btn btn-sm btn-outline-success" href="{{ route('admin.students.wallet', $student) }}"><i class="fas fa-wallet"></i> الأرصدة</a>
                                            <a class="btn btn-sm btn-outline-danger" href="{{ route('admin.students.complaints', $student) }}"><i class="fas fa-shield-heart"></i> الشكاوى</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">لا يوجد طلاب بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $students->onEachSide(1)->links('vendor.pagination.ba3eed') }}
        </div>
    </div>
@endsection
