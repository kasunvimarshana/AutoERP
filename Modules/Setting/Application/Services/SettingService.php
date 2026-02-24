<?php
namespace Modules\Setting\Application\Services;
use Illuminate\Support\Facades\Cache;
use Modules\Setting\Domain\Contracts\SettingRepositoryInterface;
class SettingService
{
    public function __construct(private SettingRepositoryInterface $repo) {}
    public function get(string $key, string $tenantId, mixed $default = null): mixed
    {
        return Cache::remember("settings.{$tenantId}.{$key}", config('setting.cache_ttl', 3600), function () use ($key, $tenantId, $default) {
            return $this->repo->get($key, $tenantId, $default);
        });
    }
    public function set(string $key, mixed $value, string $tenantId, string $group, string $type = 'string'): void
    {
        $this->repo->set($key, $value, $tenantId, $group, $type);
        Cache::forget("settings.{$tenantId}.{$key}");
        Cache::forget("settings.{$tenantId}.group.{$group}");
    }
    public function getGroup(string $group, string $tenantId): array
    {
        return Cache::remember("settings.{$tenantId}.group.{$group}", config('setting.cache_ttl', 3600), function () use ($group, $tenantId) {
            return $this->repo->getGroup($group, $tenantId);
        });
    }
}
