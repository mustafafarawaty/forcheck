@extends('student.layouts.app')

@section('title', 'جلساتي')
@section('page_title', 'جلساتي')

@section('content')
    @php
        $selectedPayload = $selectedSession ? [
            'id' => $selectedSession->id,
            'teacher_name' => $selectedSession->teacher?->name ?? 'أستاذ',
            'subject_name' => $selectedSession->subject?->name ?? 'بدون مادة',
            'status' => $selectedSession->status,
            'scheduled_at' => $selectedSession->scheduled_at?->format('Y-m-d H:i'),
            'scheduled_at_iso' => $selectedSession->scheduled_at?->toIso8601String(),
            'started_at_iso' => $selectedSession->started_at?->toIso8601String(),
            'duration_hours' => (int) ($selectedSession->duration_hours ?: 1),
            'notes' => $selectedSession->notes,
            'recording_url' => $selectedSession->recording_public_url,
            'chat_excerpt' => $selectedSession->chat_excerpt,
            'cancellation_reason' => $selectedSession->cancellation_reason,
            'student_confirmed' => (bool) $selectedSession->student_confirmed_at,
            'teacher_confirmed' => (bool) $selectedSession->teacher_confirmed_at,
            'confirm_url' => route('student.sessions.confirm', $selectedSession->id),
            'can_confirm' => $selectedSession->status === 'upcoming' && ! $selectedSession->student_confirmed_at,
            'cancel_url' => route('student.sessions.cancel', $selectedSession->id),
            'can_cancel' => $selectedSession->status === 'upcoming',
            'can_join_now' => $selectedSession->teacher_confirmed_at
                && $selectedSession->student_confirmed_at
                && in_array($selectedSession->status, ['upcoming', 'in_progress'], true)
                && $selectedSession->scheduled_at?->lessThanOrEqualTo(now())
                && $selectedSession->plannedEndAt()?->greaterThan(now()),
            'join_url' => route('student.sessions.room.show', ['sessionId' => $selectedSession->id, 'autojoin' => 1]),
            'student_summary_notes' => $selectedSession->status === 'completed' ? $selectedSession->student_summary_notes : null,
            'recording_note' => $selectedSession->recording_expires_at
                ? ($selectedSession->recording_url
                    ? 'سيتم حذف التسجيل تلقائيًا بعد 3 ساعات من انتهاء الجلسة.'
                    : 'تم حذف التسجيل تلقائيًا بعد مرور 3 ساعات على انتهاء الجلسة.')
                : null,
            'files' => $selectedSession->files->map(fn ($file) => [
                'name' => $file->original_name,
                'url' => $file->file_url,
            ])->values()->all(),
            'complaints' => $selectedSession->complaints->map(fn ($complaint) => [
                'title' => $complaint->title,
                'status' => $complaint->status,
                'submitted_at' => $complaint->submitted_at?->format('Y-m-d H:i'),
            ])->values()->all(),
        ] : null;
    @endphp

    <section class="row g-4">
        <div class="col-xl-4">
            <div class="student-list-card student-mobile-card">
                <div class="d-flex justify-content-between align-items-center mb-3 gap-3">
                    <div class="student-section-title mb-0">قائمة الجلسات</div>
                    <button type="button" class="student-btn-primary py-2 px-3" data-bs-toggle="modal" data-bs-target="#studentBookingModal">جلسة جديدة</button>
                </div>
                <div class="teacher-session-list">
                    @forelse($sessions as $session)
                        @php
                            $payload = [
                                'id' => $session->id,
                                'teacher_name' => $session->teacher?->name ?? 'أستاذ',
                                'subject_name' => $session->subject?->name ?? 'بدون مادة',
                                'status' => $session->status,
                                'scheduled_at' => $session->scheduled_at?->format('Y-m-d H:i'),
                                'scheduled_at_iso' => $session->scheduled_at?->toIso8601String(),
                                'started_at_iso' => $session->started_at?->toIso8601String(),
                                'duration_hours' => (int) ($session->duration_hours ?: 1),
                                'notes' => $session->notes,
                                'recording_url' => $session->recording_public_url,
                                'chat_excerpt' => $session->chat_excerpt,
                                'cancellation_reason' => $session->cancellation_reason,
                                'student_confirmed' => (bool) $session->student_confirmed_at,
                                'teacher_confirmed' => (bool) $session->teacher_confirmed_at,
                                'confirm_url' => route('student.sessions.confirm', $session->id),
                                'can_confirm' => $session->status === 'upcoming' && ! $session->student_confirmed_at,
                                'cancel_url' => route('student.sessions.cancel', $session->id),
                                'can_cancel' => $session->status === 'upcoming',
                                'can_join_now' => $session->teacher_confirmed_at
                                    && $session->student_confirmed_at
                                    && in_array($session->status, ['upcoming', 'in_progress'], true)
                                    && $session->scheduled_at?->lessThanOrEqualTo(now())
                                    && $session->plannedEndAt()?->greaterThan(now()),
                                'join_url' => route('student.sessions.room.show', ['sessionId' => $session->id, 'autojoin' => 1]),
                                'student_summary_notes' => $session->status === 'completed' ? $session->student_summary_notes : null,
                                'recording_note' => $session->recording_expires_at
                                    ? ($session->recording_url
                                        ? 'سيتم حذف التسجيل تلقائيًا بعد 3 ساعات من انتهاء الجلسة.'
                                        : 'تم حذف التسجيل تلقائيًا بعد مرور 3 ساعات على انتهاء الجلسة.')
                                    : null,
                                'files' => $session->files->map(fn ($file) => [
                                    'name' => $file->original_name,
                                    'url' => $file->file_url,
                                ])->values()->all(),
                                'complaints' => $session->complaints->map(fn ($complaint) => [
                                    'title' => $complaint->title,
                                    'status' => $complaint->status,
                                    'submitted_at' => $complaint->submitted_at?->format('Y-m-d H:i'),
                                ])->values()->all(),
                            ];
                        @endphp
                        <button type="button" class="teacher-session-item {{ $selectedPayload && $selectedPayload['id'] === $session->id ? 'active' : (! $selectedPayload && $loop->first ? 'active' : '') }}" data-student-session-trigger data-session='@json($payload)'>
                            <div class="fw-semibold mb-1">{{ $session->subject?->name ?? 'بدون مادة' }}</div>
                            <div class="small text-muted mb-2">{{ $session->teacher?->name ?? 'أستاذ' }}</div>
                            <div class="small">{{ $session->scheduled_at?->format('Y-m-d H:i') }}</div>
                        </button>
                    @empty
                        <div class="text-muted">لا توجد جلسات بعد.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="student-list-card student-mobile-card" data-student-session-details @if(! $selectedPayload) style="display:none;" @endif>
                @if($selectedPayload)
                    <div class="student-section-title mb-4">تفاصيل الجلسة</div>
                    <div class="teacher-session-detail-grid mb-4">
                        <div><div class="text-muted small mb-1">الأستاذ</div><div class="fw-semibold" data-session-teacher>{{ $selectedPayload['teacher_name'] }}</div></div>
                        <div><div class="text-muted small mb-1">المادة</div><div class="fw-semibold" data-session-subject>{{ $selectedPayload['subject_name'] }}</div></div>
                        <div><div class="text-muted small mb-1">الحالة</div><div class="fw-semibold" data-session-status>{{ $selectedPayload['status'] === 'completed' ? 'مكتملة' : ($selectedPayload['status'] === 'cancelled' ? 'ملغية' : ($selectedPayload['status'] === 'in_progress' ? 'جارية الآن' : 'قادمة')) }}</div></div>
                        <div><div class="text-muted small mb-1">الموعد</div><div class="fw-semibold" data-session-scheduled>{{ $selectedPayload['scheduled_at'] }}</div></div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6"><div class="teacher-media-card"><div class="small text-muted mb-1">تأكيد الطالب</div><div class="fw-semibold" data-session-student-confirmed>{{ $selectedPayload['student_confirmed'] ? 'تم التأكيد' : 'بانتظار التأكيد' }}</div></div></div>
                        <div class="col-md-6"><div class="teacher-media-card"><div class="small text-muted mb-1">تأكيد الأستاذ</div><div class="fw-semibold" data-session-teacher-confirmed>{{ $selectedPayload['teacher_confirmed'] ? 'تم التأكيد' : 'بانتظار التأكيد' }}</div></div></div>
                    </div>

                    <div class="teacher-media-card mb-3">
                        <div class="small text-muted mb-2">ملاحظات الجلسة</div>
                        <div data-session-notes>{{ $selectedPayload['notes'] ?: 'لا توجد ملاحظات مضافة.' }}</div>
                    </div>

                    <div class="teacher-media-card mb-3" data-session-join-card @if(! $selectedPayload['can_join_now']) style="display:none;" @endif>
                        <div class="small text-muted mb-2">الانضمام السريع</div>
                        <a href="{{ $selectedPayload['join_url'] }}" class="btn btn-primary w-100" data-session-join-link>الدخول إلى غرفة الجلسة</a>
                    </div>

                    <div class="teacher-media-card mb-3">
                        <div class="small text-muted mb-2">ملاحظات المعلم للطالب</div>
                        <div data-session-student-summary>{{ $selectedPayload['student_summary_notes'] ?: 'ستظهر هذه الملاحظات بعد انتهاء الجلسة.' }}</div>
                    </div>

                    <div class="teacher-media-card mb-3">
                        <div class="small text-muted mb-2">التسجيل</div>
                        <div data-session-recording>{{ $selectedPayload['recording_url'] ? 'متاح' : 'غير متوفر' }}</div>
                        <div class="small text-muted mt-2" data-session-recording-note>{{ $selectedPayload['recording_note'] }}</div>
                    </div>

                    <div class="teacher-media-card mb-3">
                        <div class="small text-muted mb-2">المحادثة</div>
                        <div data-session-chat>{{ $selectedPayload['chat_excerpt'] ?: 'لا توجد محادثة محفوظة.' }}</div>
                    </div>

                    <div class="teacher-media-card mb-3">
                        <div class="small text-muted mb-2">الملفات المرتبطة</div>
                        <div data-session-files>
                            @forelse($selectedPayload['files'] as $file)
                                <div class="py-2 {{ $loop->last ? '' : 'border-bottom' }}">
                                    <a href="{{ $file['url'] }}" target="_blank" class="text-decoration-none">{{ $file['name'] }}</a>
                                </div>
                            @empty
                                <div class="text-muted">لا توجد ملفات مرفوعة لهذه الجلسة.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="teacher-media-card mb-3" data-student-cancellation-card @if(! $selectedPayload['cancellation_reason']) style="display:none;" @endif>
                        <div class="small text-muted mb-2">سبب الإلغاء</div>
                        <div data-session-cancellation>{{ $selectedPayload['cancellation_reason'] }}</div>
                    </div>

                    <div class="teacher-media-card mb-4">
                        <div class="small text-muted mb-2">الشكاوى</div>
                        <div data-session-complaints>
                            @forelse($selectedPayload['complaints'] as $complaint)
                                <div class="d-flex justify-content-between align-items-center py-2 {{ $loop->last ? '' : 'border-bottom' }}">
                                    <div>
                                        <div class="fw-semibold">{{ $complaint['title'] }}</div>
                                        <div class="small text-muted">{{ $complaint['submitted_at'] }}</div>
                                    </div>
                                    <span class="student-chip student-chip-warning">{{ $complaint['status'] }}</span>
                                </div>
                            @empty
                                <div class="text-muted">لا توجد شكاوى مرتبطة بهذه الجلسة.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <div data-student-cancel-card @if(! $selectedPayload['can_cancel']) style="display:none;" @endif>
                            <button type="button" class="btn btn-outline-danger" data-student-cancel-open data-bs-toggle="modal" data-bs-target="#studentCancelSessionModal">
                                إلغاء الجلسة
                            </button>
                        </div>
                        <form method="POST" data-student-confirm-form action="{{ $selectedPayload['confirm_url'] }}" @if(! $selectedPayload['can_confirm']) style="display:none;" @endif>
                            @csrf
                            <button type="submit" class="btn student-btn-primary">تأكيد حضوري للجلسة</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <div class="modal fade" id="studentCancelSessionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content student-modal-card">
                <div class="modal-header border-0 pb-0">
                    <h2 class="h5 fw-bold mb-0">تأكيد إلغاء الجلسة</h2>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <p class="text-muted mb-4">هل أنت متأكد من إلغاء هذه الجلسة؟</p>
                    <form method="POST" action="{{ $selectedPayload['cancel_url'] ?? '#' }}" data-student-cancel-form>
                        @csrf
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn student-btn-soft" data-bs-dismiss="modal">رجوع</button>
                            <button type="submit" class="btn btn-outline-danger">تأكيد الإلغاء</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
