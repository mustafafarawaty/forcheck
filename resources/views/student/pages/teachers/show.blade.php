@extends('student.layouts.app')

@section('title', 'تفاصيل الأستاذ')
@section('page_title', 'تفاصيل الأستاذ')

@section('content')
    <section class="student-panel student-mobile-card mb-4">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="student-profile-avatar">
                        @if($teacherProfile->avatar_url)
                            <img src="{{ $teacherProfile->avatar_url }}" alt="{{ $teacherProfile->name }}">
                        @else
                            <i class="fas fa-user-tie"></i>
                        @endif
                    </div>
                    <div>
                        <h2 class="fw-bold mb-1">{{ $teacherProfile->name }}</h2>
                        <div class="text-muted">{{ $teacherProfile->is_accepting_instant_sessions ? 'يستقبل جلسات مباشرة الآن' : 'الحجز عنده يكون عبر المواعيد المتاحة فقط' }}</div>
                        <div class="student-rating-row justify-content-start mt-2">
                            <div class="student-rating-stars">
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="fas fa-star {{ $i <= round((float) $teacherProfile->rating_average) ? 'is-active' : '' }}"></i>
                                @endfor
                            </div>
                            <div class="small fw-semibold">
                                {{ $teacherProfile->ratings_count > 0 ? number_format((float) $teacherProfile->rating_average, 1) . ' من 5' : 'لا يوجد تقييم بعد' }}
                            </div>
                        </div>
                    </div>
                </div>
                @if($teacherProfile->about)
                    <p class="mb-3 text-muted">{{ $teacherProfile->about }}</p>
                @endif
                <div class="d-flex flex-wrap gap-2">
                    @foreach($teacherProfile->subjects as $subject)
                        <span class="student-chip student-chip-soft">
                            {{ $subject->name }} - {{ \App\Services\Teacher\TeacherSubjectService::levelLabels()[$subject->level] ?? $subject->level }} - {{ number_format((float) $subject->hourly_rate_syp, 0) }}/ساعة
                        </span>
                    @endforeach
                </div>
            </div>
            <div class="col-lg-4">
                <div class="student-list-card h-100">
                    <div class="small text-muted mb-2">الحجز</div>
                    <div class="fw-semibold mb-1">{{ $teacherProfile->is_accepting_instant_sessions ? 'يمكن طلب جلسة مباشرة' : 'هذا الأستاذ لا يوفّر جلسات مباشرة الآن' }}</div>
                    <div class="small text-muted">عند اختيار موعد محدد، سيتم الحجز فقط من بين الأوقات المتاحة وغير المحجوزة عند هذا الأستاذ.</div>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-4">
        <div class="col-xl-7">
            <div class="student-list-card student-mobile-card">
                <div class="student-section-title mb-3">الساعات المتاحة</div>
                @forelse($availabilityWindows as $window)
                    <div class="d-flex justify-content-between align-items-center py-3 {{ $loop->last ? '' : 'border-bottom' }}">
                        <div>
                            <div class="fw-semibold">{{ $window['label'] }}</div>
                            <div class="small text-muted">الحجز يتم فقط ضمن الساعات غير المحجوزة فعليًا</div>
                        </div>
                        <span class="student-chip student-chip-soft">{{ $window['subject_name'] ?? 'عام' }}</span>
                    </div>
                @empty
                    <div class="text-muted">لا توجد ساعات متاحة حاليًا.</div>
                @endforelse
            </div>
        </div>

        <div class="col-xl-5">
            <div class="student-list-card student-mobile-card">
                <div class="student-section-title mb-3">فورم حجز الجلسة</div>
                <form action="{{ route('student.teachers.book', $teacherProfile->id) }}" method="POST" class="row g-3" data-booking-mode-root data-booking-preview-url="{{ route('student.sessions.book.preview') }}" data-device-check-form>
                    @csrf
                    <input type="hidden" name="teacher_id" value="{{ $teacherProfile->id }}">
                    <input type="hidden" name="teacher_availability_id" value="{{ $availableSlots->first()['availability_id'] ?? '' }}" data-availability-hidden>
                    <input type="hidden" name="scheduled_slot_at" value="{{ $availableSlots->first()['starts_at'] ?? '' }}" data-slot-hidden>
                    <input
                        type="hidden"
                        data-booking-balance-check
                        data-student-balance="{{ (float) ($currentStudent->balance ?? 0) }}"
                        data-subject-rates='@json($teacherProfile->subjects->mapWithKeys(fn ($subject) => [$subject->name => (float) $subject->hourly_rate_syp]))'
                    >
                    <div class="col-12">
                        <label class="form-label fw-semibold">نوع الجلسة</label>
                        <div class="student-choice-group">
                            <label class="student-choice-card {{ $teacherProfile->is_accepting_instant_sessions ? '' : 'student-choice-card-disabled' }}">
                                <input type="radio" name="booking_mode" value="instant" data-booking-mode-input {{ $teacherProfile->is_accepting_instant_sessions ? '' : 'disabled' }}>
                                <span>
                                    <strong class="d-block">جلسة مباشرة</strong>
                                    <small class="text-muted">{{ $teacherProfile->is_accepting_instant_sessions ? 'متاحة الآن مع هذا الأستاذ' : 'هذا الأستاذ لا يوفّر جلسات مباشرة الآن' }}</small>
                                </span>
                            </label>
                            <label class="student-choice-card">
                                <input type="radio" name="booking_mode" value="scheduled" data-booking-mode-input checked>
                                <span>
                                    <strong class="d-block">حسب وقت معين</strong>
                                    <small class="text-muted">اختر من المواعيد المحددة والمتاحة فقط</small>
                                </span>
                            </label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">المادة</label>
                        <select name="subject_name" class="form-select student-form-select" data-booking-subject>
                            @foreach($teacherProfile->subjects as $subject)
                                <option value="{{ $subject->name }}">{{ $subject->name }} - {{ number_format((float) $subject->hourly_rate_syp, 0) }} / ساعة</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12" data-scheduled-booking-fields>
                        <div
                            data-teacher-slot-config
                            data-slots='@json($availableSlots->values())'
                        >
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">اليوم</label>
                                    <select class="form-select student-form-select" data-slot-day>
                                        <option value="">اختر اليوم</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">ساعة البداية</label>
                                    <select class="form-select student-form-select" data-slot-start>
                                        <option value="">اختر الساعة</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">عدد الساعات</label>
                                    <select name="duration_hours" class="form-select student-form-select" data-slot-duration data-booking-duration>
                                        @foreach($durationOptions as $durationOption)
                                            <option value="{{ $durationOption }}">{{ $durationOption }} ساعة</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="student-inline-warning mt-3 d-none" data-slot-warning></div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="student-inline-warning d-none" data-booking-balance-warning></div>
                        <div class="student-list-card mt-2" data-booking-balance-summary>
                            <div class="small text-muted mb-2">ملخص التكلفة</div>
                            <div class="d-flex flex-wrap justify-content-between gap-2">
                                <span>سعر الساعة: <strong data-booking-hourly-rate>0</strong></span>
                                <span>إجمالي الجلسة: <strong data-booking-total>0</strong></span>
                                <span>رصيدك الحالي: <strong data-booking-current-balance>{{ number_format((float) ($currentStudent->balance ?? 0), 0) }}</strong></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">ملاحظة</label>
                        <textarea name="note" rows="4" class="form-control student-form-control" placeholder="مثلاً: أحتاج جلسة مراجعة لموضوع معين"></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn student-btn-primary w-100" data-booking-submit @disabled($availableSlots->isEmpty() && ! $teacherProfile->is_accepting_instant_sessions)>
                            تأكيد الحجز مع الأستاذ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
