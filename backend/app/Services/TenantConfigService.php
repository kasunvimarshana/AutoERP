<?php
namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantConfig;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;

class TenantConfigService
{
    public function applyConfigs(Tenant $tenant): void
    {
        $configs = Cache::remember(
            "tenant_config_{$tenant->id}",
            3600,
            fn() => $tenant->configs()->get()
        );

        foreach ($configs as $config) {
            Config::set("tenant.{$config->key}", $config->getCastedValue());

            match($config->group) {
                'mail' => Config::set("mail.{$config->key}", $config->getCastedValue()),
                'payment' => Config::set("services.payment.{$config->key}", $config->getCastedValue()),
                'notification' => Config::set("services.notification.{$config->key}", $config->getCastedValue()),
                default => null,
            };
        }
    }

    public function getConfig(int $tenantId, string $key, mixed $default = null): mixed
    {
        $configs = Cache::remember(
            "tenant_config_{$tenantId}",
            3600,
            fn() => TenantConfig::where('tenant_id', $tenantId)->get()
        );

        $config = $configs->firstWhere('key', $key);
        return $config ? $config->getCastedValue() : $default;
    }

    public function setConfig(int $tenantId, string $key, mixed $value, string $group = 'general', string $type = 'string'): void
    {
        TenantConfig::updateOrCreate(
            ['tenant_id' => $tenantId, 'key' => $key],
            ['value' => is_array($value) ? json_encode($value) : (string) $value, 'group' => $group, 'type' => $type]
        );

        Cache::forget("tenant_config_{$tenantId}");
    }

    public function invalidateCache(int $tenantId): void
    {
        Cache::forget("tenant_config_{$tenantId}");
    }
}
