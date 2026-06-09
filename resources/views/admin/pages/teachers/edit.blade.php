@extends('admin.layouts.app')

@section('title', 'تعديل أستاذ')
@section('page_title', 'تعديل بيانات الأستاذ')

@section('content')
    <div class="card content-card border-0">
        <div class="card-body p-4">
            <form action="{{ route('admin.teachers.update', $teacher) }}" method="POST" class="row g-4">
                @csrf
                @method('PUT')

                <div class="col-md-6">
                    <label class="form-label fw-semibold">الاسم</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $teacher->name) }}" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">الهاتف</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $teacher->phone) }}" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">الاختصاص</label>
                    <input type="text" name="specialization" class="form-control" value="{{ old('specialization', $teacher->specialization) }}">
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">نبذة</label>
                    <textarea name="about" class="form-control" rows="5">{{ old('about', $teacher->about) }}</textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">كلمة مرور جديدة</label>
                    <input type="password" name="password" class="form-control" autocomplete="new-password">
                    <div class="form-text">اتركها فارغة إذا لا تريد تغيير كلمة المرور.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">تأكيد كلمة المرور</label>
                    <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4">حفظ التعديل</button>
                    <a href="{{ route('admin.teachers.index') }}" class="btn btn-outline-secondary">رجوع</a>
                </div>
            </form>
        </div>
    </div>
@endsection
