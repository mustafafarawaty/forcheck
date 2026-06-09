<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Schema;

/**
 * Reads and writes application-level financial settings.
 */
class AppSettingsService
{
    public function agoraAppId(): ?string
    {
        return $this->string('agora_app_id', config('services.agora.app_id'));
    }

    public function agoraAppCertificate(): ?string
    {
        return $this->string('agora_app_certificate', config('services.agora.app_certificate'));
    }

    public function updateAgoraCredentials(?string $appId, ?string $appCertificate): void
    {
        AppSetting::query()->updateOrCreate(
            ['key' => 'agora_app_id'],
            ['value' => (string) $appId]
        );

        AppSetting::query()->updateOrCreate(
            ['key' => 'agora_app_certificate'],
            ['value' => (string) $appCertificate]
        );
    }

    public function adminCommissionPercentage(): float
    {
        return $this->float('admin_commission_percentage', 0);
    }

    public function updateAdminCommissionPercentage(float $percentage): void
    {
        $percentage = max(0, min(100, $percentage));

        AppSetting::query()->updateOrCreate(
            ['key' => 'admin_commission_percentage'],
            ['value' => (string) $percentage]
        );
    }

    /**
     * @return array{gross:float, admin_commission_percentage:float, admin_commission_amount:float, teacher_earning_amount:float}
     */
    public function sessionPricing(float $grossAmount): array
    {
        $percentage = $this->adminCommissionPercentage();
        $commission = round($grossAmount * ($percentage / 100), 2);

        return [
            'gross' => round($grossAmount, 2),
            'admin_commission_percentage' => $percentage,
            'admin_commission_amount' => $commission,
            'teacher_earning_amount' => round($grossAmount - $commission, 2),
        ];
    }

    private function float(string $key, float $default): float
    {
        if (! Schema::hasTable('app_settings')) {
            return $default;
        }

        $value = AppSetting::query()->where('key', $key)->value('value');

        return $value === null ? $default : (float) $value;
    }

    private function string(string $key, ?string $default = null): ?string
    {
        if (! Schema::hasTable('app_settings')) {
            return $default;
        }

        $value = AppSetting::query()->where('key', $key)->value('value');

        return $value === null || $value === '' ? $default : (string) $value;
    }
}
