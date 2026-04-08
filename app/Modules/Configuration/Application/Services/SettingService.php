<?php

declare(strict_types=1);

namespace Modules\Configuration\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Configuration\Application\Contracts\SettingServiceInterface;
use Modules\Configuration\Domain\Entities\Setting;
use Modules\Configuration\Domain\Events\SettingUpdated;
use Modules\Configuration\Domain\RepositoryInterfaces\SettingRepositoryInterface;
use Modules\Core\Domain\Exceptions\NotFoundException;

class SettingService implements SettingServiceInterface
{
    public function __construct(
        private readonly SettingRepositoryInterface $settingRepository,
    ) {}

    public function get(string $tenantId, string $key): mixed
    {
        $setting = $this->settingRepository->findByKey($tenantId, $key);

        return $setting?->getValue();
    }

    public function set(string $tenantId, string $key, mixed $value): void
    {
        DB::transaction(function () use ($tenantId, $key, $value): void {
            $setting = $this->settingRepository->findByKey($tenantId, $key);

            if ($setting === null) {
                throw new NotFoundException("Setting [{$key}] not found for tenant [{$tenantId}].");
            }

            $updated = $setting->setValue($value);
            $this->settingRepository->save($updated);

            Event::dispatch(new SettingUpdated($updated));
        });
    }

    public function getSetting(string $tenantId, string $id): Setting
    {
        $setting = $this->settingRepository->findById($tenantId, $id);

        if ($setting === null) {
            throw new NotFoundException("Setting [{$id}] not found.");
        }

        return $setting;
    }

    public function createSetting(string $tenantId, array $data): Setting
    {
        return DB::transaction(function () use ($tenantId, $data): Setting {
            $now = now();
            $setting = new Setting(
                id: (string) Str::uuid(),
                tenantId: $tenantId,
                key: $data['key'],
                value: $this->encodeValue($data['value'] ?? '', $data['type'] ?? 'string'),
                group: $data['group'] ?? 'general',
                type: $data['type'] ?? 'string',
                isPublic: (bool) ($data['is_public'] ?? false),
                description: $data['description'] ?? null,
                createdAt: $now,
                updatedAt: $now,
            );

            $this->settingRepository->save($setting);

            return $setting;
        });
    }

    public function updateSetting(string $tenantId, string $id, array $data): Setting
    {
        return DB::transaction(function () use ($tenantId, $id, $data): Setting {
            $existing = $this->getSetting($tenantId, $id);

            $type = $data['type'] ?? $existing->type;
            $rawValue = array_key_exists('value', $data) ? $data['value'] : $existing->getValue();

            $updated = new Setting(
                id: $existing->id,
                tenantId: $existing->tenantId,
                key: $data['key'] ?? $existing->key,
                value: $this->encodeValue($rawValue, $type),
                group: $data['group'] ?? $existing->group,
                type: $type,
                isPublic: (bool) ($data['is_public'] ?? $existing->isPublic),
                description: $data['description'] ?? $existing->description,
                createdAt: $existing->createdAt,
                updatedAt: now(),
            );

            $this->settingRepository->save($updated);

            Event::dispatch(new SettingUpdated($updated));

            return $updated;
        });
    }

    public function deleteSetting(string $tenantId, string $id): void
    {
        DB::transaction(function () use ($tenantId, $id): void {
            $this->getSetting($tenantId, $id);
            $this->settingRepository->delete($tenantId, $id);
        });
    }

    public function getAllSettings(string $tenantId): array
    {
        return $this->settingRepository->findAll($tenantId);
    }

    public function getSettingsByGroup(string $tenantId, string $group): array
    {
        return $this->settingRepository->findByGroup($tenantId, $group);
    }

    private function encodeValue(mixed $value, string $type): string
    {
        return match ($type) {
            'json', 'array' => is_string($value) ? $value : json_encode($value, JSON_THROW_ON_ERROR),
            'boolean'       => ($value === true || $value === 'true' || $value === 1) ? 'true' : 'false',
            default         => (string) $value,
        };
    }
}
