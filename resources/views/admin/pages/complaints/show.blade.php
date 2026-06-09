@extends('admin.layouts.app')

@section('title', 'رد الشكوى')
@section('page_title', $complaint->title)

@section('content')
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card content-card border-0">
                <div class="card-body p-4">
                    <div class="mb-3"><span class="text-muted">الأستاذ:</span> {{ $complaint->teacher?->name ?? '-' }}</div>
                    <div class="mb-3"><span class="text-muted">الطالب:</span> {{ $complaint->student?->name ?? $complaint->session?->student_name ?? '-' }}</div>
                    <div class="mb-3"><span class="text-muted">الجلسة:</span> {{ $complaint->session?->subject?->name ?? '-' }}</div>
                    <div class="mb-4"><span class="text-muted">الوصف:</span><br>{{ $complaint->description }}</div>

                    <form action="{{ route('admin.complaints.status', $complaint) }}" method="POST" class="mb-3">
                        @csrf
                        @method('PATCH')
                        <label class="form-label fw-semibold">حالة الشكوى</label>
                        <div class="input-group">
                            <select name="status" class="form-select">
                                @foreach($statusOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($complaint->status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <button class="btn btn-outline-primary" type="submit">تعديل</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card content-card border-0">
                <div class="card-body p-4">
                    <div class="d-flex flex-column gap-3 mb-4">
                        <div class="p-3 rounded border bg-light">
                            <div class="fw-semibold mb-1">{{ $complaint->submitted_by === 'student' ? ($complaint->student?->name ?? 'الطالب') : ($complaint->teacher?->name ?? 'الأستاذ') }}</div>
                            <div>{{ $complaint->description }}</div>
                        </div>

                        @foreach($complaint->messagesChronological as $message)
                            <div class="p-3 rounded border {{ $message->sender_role === 'admin' ? 'bg-primary text-white' : 'bg-light' }}">
                                <div class="fw-semibold mb-1">{{ $message->sender_name }}</div>
                                <div style="white-space: pre-line;">{{ $message->message }}</div>
                                <div class="small mt-2 opacity-75">{{ $message->created_at?->format('Y-m-d H:i') }}</div>
                            </div>
                        @endforeach
                    </div>

                    <form action="{{ route('admin.complaints.reply', $complaint) }}" method="POST">
                        @csrf
                        <label class="form-label fw-semibold">رد الإدارة</label>
                        <textarea name="message" class="form-control mb-3" rows="4" required>{{ old('message') }}</textarea>
                        <button class="btn btn-primary px-4" type="submit"><i class="fas fa-paper-plane"></i> إرسال الرد</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
