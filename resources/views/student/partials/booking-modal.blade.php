<div class="modal fade" id="studentBookingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content student-modal-card">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h2 class="h4 fw-bold mb-1">حجز جلسة جديدة</h2>
                    <div class="text-muted">اختر نوع الجلسة والمادة ثم أكمل الحجز بسرعة.</div>
                </div>
                <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-3">
                <form action="{{ route('student.sessions.book') }}" method="POST" class="row g-3" data-booking-mode-root data-booking-preview-url="{{ route('student.sessions.book.preview') }}" data-device-check-form>
                    @csrf
                    <div class="col-12">
                        <label class="form-label fw-semibold">نوع الجلسة</label>
                        <div class="student-choice-group">
                            <label class="student-choice-card">
                                <input type="radio" name="booking_mode" value="instant" checked data-booking-mode-input>
                                <span>
                                    <strong class="d-block">جلسة مباشرة</strong>
                                    <small class="text-muted">بحث عن أستاذ متاح الآن</small>
                                </span>
                            </label>
                            <label class="student-choice-card">
                                <input type="radio" name="booking_mode" value="scheduled" data-booking-mode-input>
                                <span>
                                    <strong class="d-block">حسب وقت معين</strong>
                                    <small class="text-muted">حجز أول موعد مناسب متاح</small>
                                </span>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">المادة</label>
                        <select name="subject_name" class="form-select student-form-select">
                            @foreach($studentSubjectOptions as $subjectName)
                                <option value="{{ $subjectName }}">{{ $subjectName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">ملاحظة</label>
                        <input type="text" name="note" class="form-control student-form-control" placeholder="معلومة قصيرة عن الطلب">
                    </div>
                    <div class="col-12" data-scheduled-booking-fields style="display:none;">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">اليوم المطلوب</label>
                                <select name="preferred_day_of_week" class="form-select student-form-select">
                                    @foreach($studentDayOptions as $dayValue => $dayLabel)
                                        <option value="{{ $dayValue }}">{{ $dayLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">الساعة المطلوبة</label>
                                <select name="preferred_starts_at" class="form-select student-form-select">
                                    @foreach($studentHourOptions as $hourOption)
                                        <option value="{{ $hourOption }}">{{ $hourOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">عدد الساعات</label>
                                <select name="duration_hours" class="form-select student-form-select">
                                    @foreach($studentDurationOptions as $durationOption)
                                        <option value="{{ $durationOption }}">{{ $durationOption }} ساعة</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2 pt-2">
                        <button type="button" class="btn student-btn-soft" data-bs-dismiss="modal">إغلاق</button>
                        <button type="submit" class="btn student-btn-primary">تأكيد الحجز</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
