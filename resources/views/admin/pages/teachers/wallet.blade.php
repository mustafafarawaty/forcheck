@extends('admin.layouts.app')

@section('title', 'رصيد الأستاذ')
@section('page_title', 'رصيد ' . $teacher->name)

@section('content')
    <div class="card content-card border-0">
        <div class="card-body p-4">
            <div class="h4 fw-bold mb-4">الرصيد الحالي: {{ number_format((float) $teacher->balance, 0) }}</div>
            <div class="table-responsive">
                <table class="table align-middle table-hover">
                    <thead><tr><th>النوع</th><th>المبلغ</th><th>الحالة</th><th>الجلسة</th><th>التفاصيل</th><th>التاريخ</th></tr></thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                            <tr>
                                <td>{{ $transaction->type }}</td>
                                <td class="{{ $transaction->direction === 'credit' ? 'text-success' : 'text-danger' }}">{{ $transaction->direction === 'credit' ? '+' : '-' }}{{ number_format((float) $transaction->amount, 0) }}</td>
                                <td>{{ $transaction->status }}</td>
                                <td>{{ $transaction->session?->subject?->name ?? '-' }}</td>
                                <td>{{ $transaction->description }}</td>
                                <td>{{ $transaction->created_at?->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">لا توجد حركات رصيد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $transactions->onEachSide(1)->links('vendor.pagination.ba3eed') }}
        </div>
    </div>
@endsection
