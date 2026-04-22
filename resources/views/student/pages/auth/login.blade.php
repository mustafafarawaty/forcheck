@extends('student.layouts.auth')

@section('title', 'تسجيل دخول الطالب')

@section('content')
    <div class="student-auth-shell">
        <div class="row g-0">
            <div class="col-lg-5">
                <div class="student-auth-brand d-flex flex-column justify-content-between">
                    <div>
                        <h1 class="display-6 fw-bold mb-4">تسجيل الدخول</h1>
                        <div class="student-auth-visual">
                            <div class="student-auth-orb student-auth-orb-lg"></div>
                            <div class="student-auth-orb student-auth-orb-sm"></div>
                            <div class="student-auth-device">
                                <i class="fas fa-user-graduate"></i>
                                <span>طالب</span>
                            </div>
                            <div class="student-auth-chip-floating student-auth-chip-top"><i class="fas fa-book-open"></i></div>
                            <div class="student-auth-chip-floating student-auth-chip-bottom"><i class="fas fa-video"></i></div>
                        </div>
                    </div>

                    <div class="student-glass text-center">
                        <strong>الدخول إلى حساب الطالب</strong>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
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
                            <div class="text-muted">مرحبًا بك</div>
                            <h2 class="fw-bold mb-0">تسجيل دخول الطالب</h2>
                        </div>
                        <a href="{{ route('student.register') }}" class="student-btn-soft">حساب جديد</a>
                    </div>

                    <form action="{{ route('student.login.attempt') }}" method="POST" class="row g-3">
                        @csrf
                        <div class="col-12">
                            <label class="form-label fw-semibold">رقم الموبايل</label>
                            <input type="tel" inputmode="numeric" name="phone" value="{{ old('phone') }}" class="form-control student-form-control" placeholder="09xxxxxxxx" data-phone-input>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">كلمة السر</label>
                            <input type="password" name="password" class="form-control student-form-control" placeholder="••••••••">
                        </div>
                        <div class="col-12 pt-2">
                            <button type="submit" class="btn student-btn-primary w-100">دخول</button>
                        </div>
                        <div class="col-12 text-center text-muted small">
                            للأستاذ؟ <a href="{{ route('teacher.login') }}">افتح قسم الأستاذ</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
