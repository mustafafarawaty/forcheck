<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AppSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSettingsController extends Controller
{
    public function __construct(
        private readonly AppSettingsService $settings,
    ) {
    }

    public function index(): View
    {
        return view('admin.pages.settings.index', [
            'adminCommissionPercentage' => $this->settings->adminCommissionPercentage(),
            'agoraAppId' => $this->settings->agoraAppId(),
            'agoraAppCertificate' => $this->settings->agoraAppCertificate(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'admin_commission_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'agora_app_id' => ['nullable', 'string', 'max:255'],
            'agora_app_certificate' => ['nullable', 'string', 'max:255'],
        ]);

        $this->settings->updateAdminCommissionPercentage((float) $validated['admin_commission_percentage']);
        $this->settings->updateAgoraCredentials(
            $validated['agora_app_id'] ?? null,
            $validated['agora_app_certificate'] ?? null,
        );

        return back()->with('status', 'تم حفظ إعدادات التطبيق.');
    }
}
