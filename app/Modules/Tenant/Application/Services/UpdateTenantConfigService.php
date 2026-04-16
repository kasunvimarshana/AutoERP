<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Tenant\Application\Contracts\UpdateTenantConfigServiceInterface;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\Events\TenantConfigChanged;
use Modules\Tenant\Domain\Exceptions\TenantNotFoundException;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;

class UpdateTenantConfigService extends BaseService implements UpdateTenantConfigServiceInterface
{
    private const CONFIG_KEYS = [
        'database_config',
        'mail_config',
        'cache_config',
        'queue_config',
        'feature_flags',
        'api_keys',
        'settings',
        'active',
    ];

    public function __construct(private readonly TenantRepositoryInterface $tenantRepository)
    {
        parent::__construct($tenantRepository);
    }

    protected function handle(array $data): Tenant
    {
        $id = (int) $data['id'];

        $tenant = $this->tenantRepository->find($id);
        if (! $tenant) {
            throw new TenantNotFoundException($id);
        }

        $configValues = [];
        foreach (self::CONFIG_KEYS as $key) {
            if (array_key_exists($key, $data)) {
                $configValues[$key] = $data[$key];
            }
        }

        $tenant->updateConfig($configValues);
        $saved = $this->tenantRepository->save($tenant);
        $this->addEvent(new TenantConfigChanged($saved));

        return $saved;
    }
}
