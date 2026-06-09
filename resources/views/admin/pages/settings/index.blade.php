@extends('admin.layouts.app')

@section('title', 'الإعدادات')
@section('page_title', 'إعدادات التطبيق')

@section('content')
    <div class="card content-card border-0">
        <div class="card-body p-4">
            <form action="{{ route('admin.settings.update') }}" method="POST" class="row g-4">
                @csrf
                <div class="col-lg-5">
                    <label class="form-label fw-semibold">نسبة ربح الإدارة من الجلسة</label>
                    <div class="input-group">
                        <input
                            type="number"
                            name="admin_commission_percentage"
                            value="{{ old('admin_commission_percentage', $adminCommissionPercentage) }}"
                            min="0"
                            max="100"
                            step="0.01"
                            class="form-control"
                            required
                        >
                        <span class="input-group-text">%</span>
                    </div>
                    <div class="form-text">تطبق النسبة على الجلسات الجديدة.</div>
                </div>

                <div class="col-12"><hr></div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Agora App ID</label>
                    <input type="text" name="agora_app_id" value="{{ old('agora_app_id', $agoraAppId) }}" class="form-control" autocomplete="off">
                    <div class="form-text">تخزن القيمة في إعدادات التطبيق وتستخدم قبل قيم ملف env.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Agora App Certificate</label>
                    <input type="text" name="agora_app_certificate" value="{{ old('agora_app_certificate', $agoraAppCertificate) }}" class="form-control" autocomplete="off">
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-floppy-disk"></i>
                        حفظ الإعدادات
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
