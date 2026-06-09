@extends('admin.layouts.app')

@section('title', 'تفاصيل حركة الرصيد')
@section('page_title', 'تفاصيل حركة الرصيد #' . $transaction->id)

@section('content')
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card content-card border-0 h-100">
                <div class="card-body p-4">
                    <div class="mb-3"><span class="text-muted">الطرف:</span> {{ $transaction->owner_name }}</div>
                    <div class="mb-3"><span class="text-muted">الصفة:</span> {{ $transaction->owner_role === 'student' ? 'طالب' : ($transaction->owner_role === 'teacher' ? 'أستاذ' : 'نظام') }}</div>
                    <div class="mb-3"><span class="text-muted">الهاتف:</span> {{ $transaction->student?->phone ?? $transaction->teacher?->phone ?? '-' }}</div>
                    <div class="mb-3"><span class="text-muted">نوع الحركة:</span> {{ $transaction->type }}</div>
                    <div class="mb-3"><span class="text-muted">الاتجاه:</span> {{ $transaction->direction === 'credit' ? 'إضافة' : 'خصم' }}</div>
                    <div class="mb-3"><span class="text-muted">المبلغ:</span> {{ number_format((float) $transaction->amount, 0) }}</div>
                    <div class="mb-3"><span class="text-muted">الحالة:</span> {{ $statusOptions[['held' => 'pending', 'rejected' => 'cancelled'][$transaction->status] ?? $transaction->status] ?? $transaction->status }}</div>
                    <div class="mb-3"><span class="text-muted">الوصف:</span><br>{{ $transaction->description ?: '-' }}</div>
                    <div class="mb-3"><span class="text-muted">ملاحظة الإدارة:</span><br>{{ $transaction->admin_note ?: '-' }}</div>
                    <div class="mb-0"><span class="text-muted">تاريخ المراجعة:</span> {{ $transaction->reviewed_at?->format('Y-m-d H:i') ?: '-' }}</div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card content-card border-0 mb-4">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-3">الصورة والتفاصيل</h2>
                    @if($transaction->proof_url)
                        <a href="{{ $transaction->proof_url }}" target="_blank">
                            <img src="{{ $transaction->proof_url }}" alt="صورة حركة الرصيد" class="img-fluid rounded border" style="max-height: 360px;">
                        </a>
                    @else
                        <div class="text-muted">لا توجد صورة مرفقة.</div>
                    @endif
                    @if($transaction->admin_attachment_url)
                        <div class="mt-4">
                            <div class="fw-semibold mb-2">صورة الإدارة</div>
                            <a href="{{ $transaction->admin_attachment_url }}" target="_blank">
                                <img src="{{ $transaction->admin_attachment_url }}" alt="صورة الإدارة" class="img-fluid rounded border" style="max-height: 360px;">
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card content-card border-0">
                <div class="card-body p-4">
                    <form action="{{ route('admin.wallet.update', $transaction) }}" method="POST" enctype="multipart/form-data" class="row g-3 mb-4">
                        @csrf
                        @method('PATCH')
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">تعديل المبلغ</label>
                            <input type="number" name="amount" min="0" step="0.01" value="{{ old('amount', $transaction->amount) }}" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">حالة الرصيد</label>
                            <select name="status" class="form-select">
                                @foreach($statusOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $transaction->status) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">توضيح الإدارة</label>
                            <textarea name="admin_note" class="form-control" rows="3">{{ old('admin_note', $transaction->admin_note) }}</textarea>
                            <div class="form-text">هذا التوضيح يظهر للطرف الآخر ضمن ملاحظات الأرصدة.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">صورة من الإدارة (اختياري)</label>
                            <input type="file" name="admin_attachment" accept="image/*" class="form-control">
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary px-4" type="submit">حفظ التعديل</button>
                        </div>
                    </form>

                    <div class="d-flex flex-wrap gap-2">
                        <form action="{{ route('admin.wallet.approve', $transaction) }}" method="POST" class="d-flex gap-2 flex-grow-1">
                            @csrf
                            <input type="text" name="admin_note" class="form-control" placeholder="توضيح اختياري عند القبول">
                            <button class="btn btn-success px-4" type="submit">قبول</button>
                        </form>
                        <form action="{{ route('admin.wallet.update', $transaction) }}" method="POST" class="d-flex gap-2 flex-grow-1">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="amount" value="{{ $transaction->amount }}">
                            <input type="hidden" name="status" value="cancelled">
                            <input type="text" name="admin_note" class="form-control" placeholder="توضيح اختياري عند الإلغاء">
                            <button class="btn btn-outline-danger px-4" type="submit">ملغى</button>
                        </form>
                        <form action="{{ route('admin.wallet.update', $transaction) }}" method="POST" class="d-flex gap-2 flex-grow-1">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="amount" value="{{ $transaction->amount }}">
                            <input type="hidden" name="status" value="refunded">
                            <input type="text" name="admin_note" class="form-control" placeholder="توضيح اختياري عند الاسترجاع">
                            <button class="btn btn-outline-warning px-4" type="submit">مسترجع</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
