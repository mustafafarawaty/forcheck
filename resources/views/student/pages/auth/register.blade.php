@extends('student.layouts.auth')

@section('title', 'إنشاء حساب طالب')

@section('content')
    <div class="student-auth-shell">
        <div class="row g-0">
            <div class="col-lg-4">
                <div class="student-auth-brand">
                    <h1 class="fw-bold mb-4">تسجيل جديد</h1>
                    <div class="student-auth-visual student-auth-visual-compact">
                        <div class="student-auth-orb student-auth-orb-lg"></div>
                        <div class="student-auth-orb student-auth-orb-sm"></div>
                        <div class="student-auth-device">
                            <i class="fas fa-graduation-cap"></i>
                            <span>تعلم</span>
                        </div>
                        <div class="student-auth-chip-floating student-auth-chip-top"><i class="fas fa-book"></i></div>
                        <div class="student-auth-chip-floating student-auth-chip-bottom"><i class="fas fa-pen-ruler"></i></div>
                    </div>

                    <div class="student-glass text-center mt-4">
                        <strong>إنشاء حساب الطالب</strong>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="student-auth-card">
                    @if($errors->any())
                        <div class="alert alert-danger mb-4">
                            <ul class="mb-0 ps-3">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <div class="text-muted">إنشاء ملف طالب</div>
                            <h2 class="fw-bold mb-0">البيانات الأساسية</h2>
                        </div>
                        <a href="{{ route('student.login') }}" class="student-btn-soft">لدي حساب</a>
                    </div>

                    <form action="{{ route('student.register.store') }}" method="POST" class="row g-3">
                        @csrf
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">الاسم الكامل</label>
                            <input type="text" name="name" value="{{ old('name') }}" class="form-control student-form-control" placeholder="الاسم الثلاثي">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">رقم الموبايل</label>
                            <input type="tel" inputmode="numeric" name="phone" value="{{ old('phone') }}" class="form-control student-form-control" placeholder="09xxxxxxxx" data-phone-input>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">كلمة السر</label>
                            <input type="password" name="password" class="form-control student-form-control" placeholder="••••••••">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">تأكيد كلمة السر</label>
                            <input type="password" name="password_confirmation" class="form-control student-form-control" placeholder="••••••••">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">الفئة الدراسية</label>
                            <select name="study_level" class="form-select student-form-select">
                                @foreach($studyLevels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('study_level') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">نبذة قصيرة</label>
                            <textarea name="about" class="form-control student-form-control" rows="4" placeholder="مثلاً: أبحث عن جلسات تقوية بمادة معينة">{{ old('about') }}</textarea>
                        </div>
                        <div class="col-12 pt-2">
                            <button type="submit" class="btn student-btn-primary w-100">إنشاء الحساب</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
