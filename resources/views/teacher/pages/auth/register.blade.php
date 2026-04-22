@extends('teacher.layouts.auth')

@section('title', 'إنشاء حساب أستاذ')

@section('content')
    <div class="teacher-auth-shell">
        <div class="row g-0">
            <div class="col-lg-4">
                <div class="teacher-auth-brand">
                    <h1 class="fw-bold mb-4">تسجيل جديد</h1>
                    <div class="teacher-auth-visual teacher-auth-visual-compact">
                        <div class="teacher-auth-orb teacher-auth-orb-lg"></div>
                        <div class="teacher-auth-orb teacher-auth-orb-sm"></div>
                        <div class="teacher-auth-device">
                            <i class="fas fa-user-tie"></i>
                            <span>تدريس</span>
                        </div>
                        <div class="teacher-auth-chip-floating teacher-auth-chip-top"><i class="fas fa-book"></i></div>
                        <div class="teacher-auth-chip-floating teacher-auth-chip-bottom"><i class="fas fa-graduation-cap"></i></div>
                    </div>

                    <div class="teacher-glass text-center mt-4">
                        <strong>إنشاء حساب الأستاذ</strong>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
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
                            <div class="text-muted">إنشاء ملف أستاذ</div>
                            <h2 class="fw-bold mb-0">البيانات الشخصية والمؤهل</h2>
                        </div>
                        <a href="{{ route('teacher.login') }}" class="teacher-btn-soft">لدي حساب</a>
                    </div>

                    <form action="{{ route('teacher.register.store') }}" method="POST" enctype="multipart/form-data" class="row g-3">
                        @csrf
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">الاسم الكامل</label>
                            <input type="text" name="name" value="{{ old('name') }}" class="form-control teacher-form-control" placeholder="الاسم الثلاثي">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">رقم الموبايل</label>
                            <input type="tel" inputmode="numeric" name="phone" value="{{ old('phone') }}" class="form-control teacher-form-control" placeholder="09xxxxxxxx" data-phone-input>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">كلمة السر</label>
                            <input type="password" name="password" class="form-control teacher-form-control" placeholder="••••••••">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">تأكيد كلمة السر</label>
                            <input type="password" name="password_confirmation" class="form-control teacher-form-control" placeholder="••••••••">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">المستوى التعليمي الذي وصل له المدرّس</label>
                            <select name="education_stage" class="form-select teacher-form-select">
                                @foreach($educationStages as $value => $label)
                                    <option value="{{ $value }}" @selected(old('education_stage') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">رفع ملف الشهادة</label>
                            <input type="file" name="certificate" class="form-control teacher-form-control" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">نبذة قصيرة</label>
                            <textarea name="about" class="form-control teacher-form-control" rows="4" placeholder="نبذة عن خبرتك وطريقتك في الشرح">{{ old('about') }}</textarea>
                        </div>
                        <div class="col-12 pt-2">
                            <button type="submit" class="btn teacher-btn-primary w-100">إنشاء الحساب</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
