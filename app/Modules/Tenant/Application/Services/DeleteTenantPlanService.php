<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Tenant\Application\Contracts\DeleteTenantPlanServiceInterface;
use Modules\Tenant\Domain\Events\TenantPlanDeleted;
use Modules\Tenant\Domain\Exceptions\TenantPlanNotFoundException;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantPlanRepositoryInterface;

class DeleteTenantPlanService extends BaseService implements DeleteTenantPlanServiceInterface
{
    public function __construct(
        private readonly TenantPlanRepositoryInterface $planRepository
    ) {
        parent::__construct($planRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) $data['id'];
        $existing = $this->planRepository->find($id);

        if (! $existing) {
            throw new TenantPlanNotFoundException($id);
        }

        $deleted = $this->planRepository->delete($id);
        if ($deleted) {
            $this->addEvent(new TenantPlanDeleted($id));
        }

        return $deleted;
    }
}
