@extends('teacher.layouts.auth')

@section('title', 'تسجيل الدخول')

@section('content')
    <div class="teacher-auth-shell">
        <div class="row g-0">
            <div class="col-lg-5">
                <div class="teacher-auth-brand d-flex flex-column justify-content-between">
                    <div>
                        <h1 class="display-6 fw-bold mb-4">تسجيل الدخول</h1>
                        <div class="teacher-auth-visual">
                            <div class="teacher-auth-orb teacher-auth-orb-lg"></div>
                            <div class="teacher-auth-orb teacher-auth-orb-sm"></div>
                            <div class="teacher-auth-device">
                                <i class="fas fa-chalkboard-teacher"></i>
                                <span>أستاذ</span>
                            </div>
                            <div class="teacher-auth-chip-floating teacher-auth-chip-top"><i class="fas fa-book-open"></i></div>
                            <div class="teacher-auth-chip-floating teacher-auth-chip-bottom"><i class="fas fa-calendar-check"></i></div>
                        </div>
                    </div>

                    <div class="teacher-glass text-center">
                        <strong>الدخول إلى حساب الأستاذ</strong>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="teacher-auth-card">
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
                            <div class="text-muted">أهلًا بعودتك</div>
                            <h2 class="fw-bold mb-0">تسجيل دخول الأستاذ</h2>
                        </div>
                        <a href="{{ route('teacher.register') }}" class="teacher-btn-soft">حساب جديد</a>
                    </div>

                    <form action="{{ route('teacher.login.attempt') }}" method="POST" class="row g-3">
                        @csrf
                        <div class="col-12">
                            <label class="form-label fw-semibold">رقم الموبايل</label>
                            <input type="tel" inputmode="numeric" name="phone" value="{{ old('phone') }}" class="form-control teacher-form-control" placeholder="09xxxxxxxx" data-phone-input>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">كلمة السر</label>
                            <input type="password" name="password" class="form-control teacher-form-control" placeholder="••••••••">
                        </div>
                        <div class="col-12 pt-2">
                            <button type="submit" class="btn teacher-btn-primary w-100">دخول إلى اللوحة</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
