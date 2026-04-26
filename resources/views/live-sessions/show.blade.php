@extends($actorRole === 'teacher' ? 'teacher.layouts.app' : 'student.layouts.app')

@section('title', 'غرفة الجلسة')
@section('page_title', 'غرفة الجلسة المباشرة')

@section('content')
    @php
        $roomConfig = [
            'role' => $actorRole,
            'sessionId' => $session->id,
            'stateUrl' => $stateUrl,
            'joinUrl' => $joinUrl,
            'signalUrl' => $signalUrl,
            'messageUrl' => $messageUrl,
            'fileUrl' => $fileUrl,
            'notesUrl' => $notesUrl,
            'complaintUrl' => $complaintUrl,
            'recordingUrl' => $recordingUrl,
            'endUrl' => $endUrl,
            'roomChannel' => $roomChannel,
            'initialState' => $roomState,
            'redirectUrl' => $actorRole === 'teacher'
                ? route('teacher.sessions.index', ['selected_session_id' => $session->id])
                : route('student.sessions.index'),
        ];
    @endphp
    
    <style>
/* 📱 MOBILE ONLY */
@media (max-width: 768px) {

    /* 1. تحويل layout لعمودي */
    .live-room-layout {
        display: flex;
        flex-direction: column !important;
        gap: 10px;
    }

    /* 2. الفيديو */
    .live-room-video-wrap {
        width: 100%;
        height: 220px;
        position: relative;
    }

    .live-room-video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* preview تبع الكاميرا */
    .live-room-local-preview {
        width: 80px;
        height: 60px;
        position: absolute;
        bottom: 10px;
        right: 10px;
        border-radius: 8px;
        overflow: hidden;
    }

    /* 3. الأزرار */
    .live-room-controls {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 10px;
    }

    .live-room-control {
        flex: 1 1 48%;
        font-size: 13px;
        padding: 8px;
    }

    /* زر الإنهاء ياخد عرض كامل */
    .live-room-control-danger {
        flex: 1 1 100%;
    }

    /* 4. الهيدر */
    .live-room-header {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .live-room-title {
        font-size: 16px;
    }

    .live-room-meta {
        font-size: 12px;
    }

    /* 5. sidebar → تحت الفيديو */
    .live-room-sidebar {
        width: 100%;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    /* 6. panels */
    .live-room-panel {
        padding: 10px;
    }

    .live-room-panel-title {
        font-size: 14px;
        margin-bottom: 5px;
    }

    /* 7. الشات */
    .live-room-chat-list {
        max-height: 150px;
        overflow-y: auto;
    }

    /* 8. input */
    .live-room-input {
        font-size: 13px;
        padding: 6px;
    }

    .live-room-submit {
        font-size: 13px;
        padding: 6px 10px;
    }

    /* 9. timer */
    .live-room-timer {
        font-size: 14px;
    }

    /* 10. recording text */
    .live-room-recording-pill span {
        font-size: 11px;
    }

}
</style>

    <div class="live-room-shell" data-live-room='@json($roomConfig)'>
        <section class="live-room-header mb-4">
            <div>
                <div class="live-room-kicker">جلسة مباشرة</div>
                <h2 class="live-room-title mb-2">
                    {{ $session->subject?->name ?? 'جلسة تعليمية' }}
                    <span class="live-room-separator">•</span>
                    <span data-room-participant>{{ $actorRole === 'teacher' ? ($session->student_name ?: $session->student?->name) : $session->teacher?->name }}</span>
                </h2>
                <div class="live-room-meta">
                    <span data-room-start-label>{{ $session->scheduled_at?->format('Y-m-d H:i') }}</span>
                    <span>حتى</span>
                    <span data-room-end-label>{{ $session->plannedEndAt()?->format('H:i') }}</span>
                </div>
            </div>

            <div class="live-room-header-actions">
                <div class="live-room-status" data-room-status-badge>قادمة</div>
                <a href="{{ $actorRole === 'teacher' ? route('teacher.sessions.index', ['selected_session_id' => $session->id]) : route('student.sessions.index') }}" class="live-room-back-link">
                    رجوع للجلسات
                </a>
            </div>
        </section>

        <section class="live-room-layout">
            <div class="live-room-stage">
                <div class="live-room-stage-topbar">
                    <div class="live-room-recording-pill">
                        <i class="fas fa-circle"></i>
                        <span>سيتم تسجيل هذه الجلسة تلقائيًا وحفظها للطرفين</span>
                    </div>
                    <div class="live-room-timer" data-room-remaining>
                        --:--:--
                    </div>
                </div>

                <div class="live-room-video-wrap">
                    <video class="live-room-video" data-remote-video autoplay playsinline></video>
                    <div class="live-room-video-overlay" data-room-video-overlay>
                        <div class="live-room-video-message">
                            بانتظار اتصال الطرف الآخر ودخول البث المباشر.
                        </div>
                    </div>

                    <div class="live-room-local-preview">
                        <video class="live-room-local-video" data-local-video autoplay playsinline muted></video>
                    </div>
                </div>

                <div class="live-room-controls">
                    <button type="button" class="live-room-control" data-toggle-camera>
                        <i class="fas fa-video"></i>
                        <span>الكاميرا</span>
                    </button>
                    <button type="button" class="live-room-control" data-toggle-mic>
                        <i class="fas fa-microphone"></i>
                        <span>الميكروفون</span>
                    </button>
                    <button type="button" class="live-room-control live-room-control-join" data-join-now>
                        <i class="fas fa-right-to-bracket"></i>
                        <span>الانضمام الآن</span>
                    </button>
                    <button type="button" class="live-room-control live-room-control-danger" data-end-session>
                        <i class="fas fa-phone-slash"></i>
                        <span>إنهاء الجلسة</span>
                    </button>
                </div>

                <div class="live-room-warning-toast d-none" data-room-ending-soon>
                    تبقّى أقل من 10 دقائق على نهاية الجلسة.
                </div>
            </div>

            <aside class="live-room-sidebar">
                @if($actorRole === 'teacher')
                    <section class="live-room-panel">
                        <div class="live-room-panel-title">ملاحظات المعلم</div>
                        <textarea class="live-room-textarea" rows="4" placeholder="ملاحظات خاصة لا تظهر للطالب" data-room-teacher-notes>{{ $session->teacher_private_notes }}</textarea>
                        <textarea class="live-room-textarea mt-3" rows="4" placeholder="ملاحظات ستظهر للطالب بعد انتهاء الجلسة" data-room-student-notes>{{ $session->student_summary_notes }}</textarea>
                        <div class="live-room-helper" data-room-notes-status>يتم الحفظ تلقائيًا أثناء الكتابة.</div>
                    </section>
                @else
                    <section class="live-room-panel">
                        <div class="live-room-panel-title">ملاحظات المعلم للطالب</div>
                        <div class="live-room-note-box" data-room-student-summary>
                            {{ $session->status === 'completed' ? ($session->student_summary_notes ?: 'لا توجد ملاحظات مضافة بعد.') : 'ستظهر لك هذه الملاحظات بعد انتهاء الجلسة.' }}
                        </div>
                    </section>
                @endif

                <section class="live-room-panel">
                    <div class="live-room-panel-title">الشات المباشر</div>
                    <div class="live-room-chat-list" data-room-chat-list></div>
                    <form class="live-room-chat-form" data-room-chat-form>
                        <input type="text" class="live-room-input" name="message" placeholder="اكتب رسالة سريعة">
                        <button type="submit" class="live-room-submit">إرسال</button>
                    </form>
                </section>

                <section class="live-room-panel">
                    <div class="live-room-panel-title">الملفات المرفوعة</div>
                    <div class="live-room-file-list" data-room-file-list></div>
                    <form class="live-room-upload-form" data-room-file-form>
                        <input type="file" class="live-room-input" name="file">
                        <button type="submit" class="live-room-submit">رفع ملف</button>
                    </form>
                </section>

                <section class="live-room-panel">
                    <div class="live-room-panel-title">شكوى للإدارة</div>
                    <form class="live-room-complaint-form" data-room-complaint-form>
                        <input type="text" class="live-room-input mb-2" name="title" placeholder="عنوان الشكوى">
                        <textarea class="live-room-textarea" rows="3" name="description" placeholder="اكتب تفاصيل الشكوى"></textarea>
                        <button type="submit" class="live-room-submit mt-3">إرسال الشكوى</button>
                    </form>
                    <div class="live-room-complaint-list mt-3" data-room-complaint-list></div>
                </section>
            </aside>
        </section>
    </div>

    <div class="modal fade" id="liveSessionEndConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content {{ $actorRole === 'teacher' ? 'teacher-modal-card' : 'student-modal-card' }}">
                <div class="modal-header border-0 pb-0">
                    <h2 class="h5 fw-bold mb-0">تأكيد إنهاء الجلسة</h2>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <p class="mb-4" data-room-end-confirm-message>
                        هل أنت متأكد من إنهاء الجلسة الآن؟
                    </p>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">رجوع</button>
                        <button type="button" class="btn btn-danger" data-room-end-confirm-submit>تأكيد الإنهاء</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
