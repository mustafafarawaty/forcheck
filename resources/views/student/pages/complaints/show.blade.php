@extends('student.layouts.app')

@section('title', 'محادثة الشكوى')

@section('content')
    <div class="student-list-card">
        <div class="student-section-title mb-4">{{ $complaint->title }}</div>

        @if($complaint->status === 'closed')
            <div class="alert alert-secondary">تم إغلاق الشكوى. المحادثة متاحة للقراءة فقط.</div>
        @elseif(! $canReply)
            <div class="alert alert-info">يمكنك الرد بعد أن ترد الإدارة وتكون الشكوى قيد المعالجة.</div>
        @endif

        <div class="d-flex flex-column gap-3 mb-4">
            <div class="p-3 rounded border bg-light">
                <div class="fw-semibold mb-1">{{ $complaint->student?->name ?? 'الطالب' }}</div>
                <div>{{ $complaint->description }}</div>
                @if($complaint->attachment_url)
                    <a href="{{ $complaint->attachment_url }}" target="_blank" class="btn btn-sm btn-outline-primary mt-3">عرض المرفق</a>
                @endif
            </div>

            @foreach($complaint->messagesChronological as $message)
                <div class="p-3 rounded border {{ $message->sender_role === 'student' ? 'bg-primary text-white' : 'bg-light' }}">
                    <div class="fw-semibold mb-1">{{ $message->sender_name }}</div>
                    <div style="white-space: pre-line;">{{ $message->message }}</div>
                    <div class="small mt-2 opacity-75">{{ $message->created_at?->format('Y-m-d H:i') }}</div>
                </div>
            @endforeach
        </div>

        <form action="{{ route('student.complaints.reply', $complaint->id) }}" method="POST">
            @csrf
            <textarea name="message" class="form-control student-form-control mb-3" rows="4" required @disabled(! $canReply)>{{ old('message') }}</textarea>
            <button type="submit" class="btn btn-primary px-4" @disabled(! $canReply)>إرسال الرد</button>
        </form>
    </div>
@endsection
