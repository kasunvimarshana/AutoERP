<?php
declare(strict_types=1);
namespace Modules\Tenant\Application\Services;

use Modules\Tenant\Application\Contracts\CreateTenantServiceInterface;
use Modules\Tenant\Application\DTOs\CreateTenantData;
use Modules\Tenant\Domain\Entities\Tenant;
use Modules\Tenant\Domain\Events\TenantCreated;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantRepositoryInterface;

class CreateTenantService implements CreateTenantServiceInterface
{
    public function __construct(private readonly TenantRepositoryInterface $repo) {}

    public function execute(CreateTenantData $data): Tenant
    {
        $tenant = $this->repo->create([
            'name' => $data->name,
            'slug' => $data->slug,
            'status' => $data->status ?? 'trial',
            'plan_type' => $data->plan_type,
            'settings' => $data->settings,
            'trial_ends_at' => $data->trial_ends_at,
        ]);
        event(new TenantCreated($tenant->getId(), $tenant->getId(), $tenant->getName()));
        return $tenant;
    }
}
