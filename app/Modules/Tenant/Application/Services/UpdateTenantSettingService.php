<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Tenant\Application\Contracts\UpdateTenantSettingServiceInterface;
use Modules\Tenant\Domain\Entities\TenantSetting;
use Modules\Tenant\Domain\Events\TenantSettingUpdated;
use Modules\Tenant\Domain\Exceptions\TenantSettingNotFoundException;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantSettingRepositoryInterface;

class UpdateTenantSettingService extends BaseService implements UpdateTenantSettingServiceInterface
{
    public function __construct(
        private readonly TenantSettingRepositoryInterface $settingRepository
    ) {
        parent::__construct($settingRepository);
    }

    protected function handle(array $data): TenantSetting
    {
        $tenantId = (int) $data['tenant_id'];
        $key = (string) $data['key'];

        $existing = $this->settingRepository->findByTenantAndKey($tenantId, $key);
        if (! $existing) {
            throw new TenantSettingNotFoundException("{$tenantId}:{$key}");
        }

        $value = array_key_exists('value', $data)
            ? $data['value']
            : $existing->getValue();

        $group = array_key_exists('group', $data)
            ? (string) $data['group']
            : $existing->getGroup();

        $isPublic = array_key_exists('is_public', $data)
            ? (bool) $data['is_public']
            : $existing->isPublic();

        $existing->update(
            value: $value,
            group: $group,
            isPublic: $isPublic
        );

        $saved = $this->settingRepository->save($existing);
        $this->addEvent(new TenantSettingUpdated($saved));

        return $saved;
    }
}
