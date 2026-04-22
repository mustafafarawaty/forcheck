@extends('teacher.layouts.app')

@php
    $selectedSessionId = old('selected_session_id', $selectedSession?->id);
@endphp

@section('title', 'الجلسات')
@section('page_title', 'الجلسات وتفاصيلها')

@section('content')
    <div class="row g-4">
        <div class="col-xl-5">
            <div class="teacher-list-card teacher-mobile-card">
                <div class="teacher-section-title mb-4">جلسات الأستاذ</div>

                <div class="teacher-session-list" data-session-list>
                    @forelse($sessions as $session)
                        @php
                            $sessionPayload = [
                                'id' => $session->id,
                                'student_name' => $session->student_name,
                                'subject_name' => $session->subject?->name ?? 'من دون مادة',
                                'status' => $session->status,
                                'scheduled_at' => $session->scheduled_at?->format('Y-m-d H:i'),
                                'scheduled_at_iso' => $session->scheduled_at?->toIso8601String(),
                                'started_at_iso' => $session->started_at?->toIso8601String(),
                                'duration_hours' => (int) ($session->duration_hours ?: 1),
                                'ended_at' => $session->ended_at?->format('Y-m-d H:i'),
                                'notes' => $session->notes,
                                'recording_url' => $session->recording_public_url,
                                'chat_excerpt' => $session->chat_excerpt,
                                'cancellation_reason' => $session->cancellation_reason,
                                'teacher_confirmed' => (bool) $session->teacher_confirmed_at,
                                'student_confirmed' => (bool) $session->student_confirmed_at,
                                'complaints' => $session->complaints->map(fn ($complaint) => [
                                    'title' => $complaint->title,
                                    'status' => $complaint->status,
                                    'submitted_at' => $complaint->submitted_at?->format('Y-m-d H:i'),
                                ])->values()->all(),
                                'can_cancel' => $session->status === 'upcoming',
                                'cancel_url' => route('teacher.sessions.cancel', $session->id),
                                'can_confirm' => $session->status === 'upcoming' && ! $session->teacher_confirmed_at,
                                'confirm_url' => route('teacher.sessions.confirm', $session->id),
                                'can_join_now' => $session->teacher_confirmed_at
                                    && $session->student_confirmed_at
                                    && in_array($session->status, ['upcoming', 'in_progress'], true)
                                    && $session->scheduled_at?->lessThanOrEqualTo(now())
                                    && $session->plannedEndAt()?->greaterThan(now()),
                                'join_url' => route('teacher.sessions.room.show', ['sessionId' => $session->id, 'autojoin' => 1]),
                                'student_summary_notes' => $session->student_summary_notes,
                                'recording_note' => $session->recording_expires_at
                                    ? ($session->recording_url
                                        ? 'سيتم حذف التسجيل تلقائيًا بعد 3 ساعات من انتهاء الجلسة.'
                                        : 'تم حذف التسجيل تلقائيًا بعد مرور 3 ساعات على انتهاء الجلسة.')
                                    : null,
                                'files' => $session->files->map(fn ($file) => [
                                    'name' => $file->original_name,
                                    'url' => $file->file_url,
                                ])->values()->all(),
                            ];
                        @endphp

                        <button
                            type="button"
                            class="teacher-session-item {{ (string) $selectedSessionId === (string) $session->id ? 'active' : '' }}"
                            data-session-trigger
                            data-session='@json($sessionPayload)'
                        >
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <div class="text-end">
                                    <div class="fw-bold">{{ $session->student_name }}</div>
                                    <div class="small text-muted">{{ $session->subject?->name ?? 'من دون مادة' }}</div>
                                </div>
                                <span class="teacher-chip {{ $session->status === 'completed' ? 'teacher-chip-success' : ($session->status === 'cancelled' ? 'teacher-chip-danger' : 'teacher-chip-warning') }}">
                                    {{ $session->status === 'completed' ? 'مكتملة' : ($session->status === 'cancelled' ? 'ملغية' : ($session->status === 'in_progress' ? 'جارية' : 'قادمة')) }}
                                </span>
                            </div>
                            <div class="small text-muted mt-2">{{ $session->scheduled_at?->format('Y-m-d H:i') }}</div>
                        </button>
                    @empty
                        <div class="text-muted">لا توجد جلسات حتى الآن.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-xl-7">
            <div class="teacher-list-card teacher-mobile-card" data-session-details>
                @if($selectedSession)
                    <div class="teacher-section-title mb-4">تفاصيل الجلسة</div>
                    <div class="teacher-session-detail-grid mb-4">
                        <div><span class="text-muted d-block small">اسم الطالب</span><strong data-session-student>{{ $selectedSession->student_name }}</strong></div>
                        <div><span class="text-muted d-block small">المادة</span><strong data-session-subject>{{ $selectedSession->subject?->name ?? 'من دون مادة' }}</strong></div>
                        <div><span class="text-muted d-block small">الحالة</span><strong data-session-status>{{ $selectedSession->status === 'completed' ? 'مكتملة' : ($selectedSession->status === 'cancelled' ? 'ملغية' : ($selectedSession->status === 'in_progress' ? 'جارية الآن' : 'قادمة')) }}</strong></div>
                        <div><span class="text-muted d-block small">موعد الجلسة</span><strong data-session-scheduled>{{ $selectedSession->scheduled_at?->format('Y-m-d H:i') }}</strong></div>
                    </div>

                    <div class="teacher-media-card mb-3">
                        <div class="fw-bold mb-2">ملاحظات الجلسة</div>
                        <p class="text-muted mb-0" data-session-notes>{{ $selectedSession->notes ?: 'لا توجد ملاحظات مضافة.' }}</p>
                    </div>

                    <div class="teacher-media-card mb-3" data-session-join-card @if(! ($selectedSession->teacher_confirmed_at && $selectedSession->student_confirmed_at && in_array($selectedSession->status, ['upcoming', 'in_progress'], true) && $selectedSession->scheduled_at?->lessThanOrEqualTo(now()) && $selectedSession->plannedEndAt()?->greaterThan(now()))) style="display:none" @endif>
                        <div class="fw-bold mb-2">الانضمام السريع</div>
                        <a href="{{ route('teacher.sessions.room.show', ['sessionId' => $selectedSession->id, 'autojoin' => 1]) }}" class="btn btn-primary w-100" data-session-join-link>الدخول إلى غرفة الجلسة</a>
                    </div>

                    <div class="teacher-media-card mb-3">
                        <div class="fw-bold mb-2">ملاحظات ستظهر للطالب</div>
                        <p class="text-muted mb-0" data-session-student-summary>{{ $selectedSession->student_summary_notes ?: 'لا توجد ملاحظات مضافة بعد.' }}</p>
                    </div>

                    <div class="teacher-media-card mb-3">
                        <div class="fw-bold mb-2">وسائط الجلسة</div>
                        <div class="small text-muted mb-2">رابط التسجيل</div>
                        <div data-session-recording>{{ $selectedSession->recording_public_url ? 'متاح' : 'غير متوفر' }}</div>
                        <div class="small text-muted mt-2" data-session-recording-note>
                            @if($selectedSession->recording_expires_at)
                                {{ $selectedSession->recording_url ? 'سيتم حذف التسجيل تلقائيًا بعد 3 ساعات من انتهاء الجلسة.' : 'تم حذف التسجيل تلقائيًا بعد مرور 3 ساعات على انتهاء الجلسة.' }}
                            @endif
                        </div>
                        <div class="small text-muted mt-3 mb-2">مقتطف المحادثة</div>
                        <div data-session-chat>{{ $selectedSession->chat_excerpt ?: 'لا توجد محادثة محفوظة.' }}</div>
                    </div>

                    <div class="teacher-media-card mb-3">
                        <div class="fw-bold mb-2">الملفات المرتبطة</div>
                        <div data-session-files>
                            @forelse($selectedSession->files as $file)
                                <div class="py-2 {{ $loop->last ? '' : 'border-bottom' }}">
                                    <a href="{{ $file->file_url }}" target="_blank" class="text-decoration-none">{{ $file->original_name }}</a>
                                </div>
                            @empty
                                <div class="text-muted">لا توجد ملفات مرفوعة لهذه الجلسة.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="teacher-media-card mb-3" data-confirm-card @if($selectedSession->status !== 'upcoming' || $selectedSession->teacher_confirmed_at) style="display:none" @endif>
                        <div class="fw-bold mb-3">تأكيد حضور الجلسة</div>
                        <form action="{{ route('teacher.sessions.confirm', $selectedSession->id) }}" method="POST" data-confirm-form>
                            @csrf
                            <button type="submit" class="btn btn-outline-primary w-100">تأكيد حضوري للجلسة</button>
                        </form>
                    </div>

                    <div class="teacher-media-card mb-3" data-cancellation-card @if(! $selectedSession->cancellation_reason) style="display:none" @endif>
                        <div class="fw-bold mb-2 text-danger">سبب الإلغاء</div>
                        <p class="mb-0" data-session-cancellation>{{ $selectedSession->cancellation_reason }}</p>
                    </div>

                    <form action="{{ $selectedSession->status === 'upcoming' ? route('teacher.sessions.cancel', $selectedSession->id) : '#' }}" method="POST" class="teacher-media-card mb-3" data-cancel-form @if($selectedSession->status !== 'upcoming') style="display:none" @endif>
                        @csrf
                        <input type="hidden" name="selected_session_id" value="{{ $selectedSession->id }}" data-cancel-session-id>
                        <div class="fw-bold mb-3">إلغاء الجلسة القادمة</div>
                        <input type="text" name="cancellation_reason" class="form-control teacher-form-control mb-3" placeholder="اكتب سبب الإلغاء">
                        <button type="submit" class="btn btn-outline-danger w-100">إلغاء الجلسة</button>
                    </form>

                    <div class="teacher-media-card">
                        <div class="fw-bold mb-3">الشكاوى المرتبطة</div>
                        <div data-session-complaints>
                            @forelse($selectedSession->complaints as $complaint)
                                <div class="d-flex justify-content-between align-items-center py-2 {{ $loop->last ? '' : 'border-bottom' }}">
                                    <div>
                                        <div class="fw-semibold">{{ $complaint->title }}</div>
                                        <div class="small text-muted">{{ $complaint->submitted_at?->format('Y-m-d H:i') }}</div>
                                    </div>
                                    <span class="teacher-chip teacher-chip-warning">{{ $complaint->status }}</span>
                                </div>
                            @empty
                                <div class="text-muted">لا توجد شكاوى مرتبطة بهذه الجلسة.</div>
                            @endforelse
                        </div>
                    </div>
                @else
                    <div class="text-muted">اختر جلسة لعرض تفاصيلها.</div>
                @endif
            </div>
        </div>
    </div>
@endsection
