<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Tenant\Application\Contracts\DeleteTenantDomainServiceInterface;
use Modules\Tenant\Domain\Events\TenantDomainDeleted;
use Modules\Tenant\Domain\Exceptions\TenantDomainNotFoundException;
use Modules\Tenant\Domain\RepositoryInterfaces\TenantDomainRepositoryInterface;

class DeleteTenantDomainService extends BaseService implements DeleteTenantDomainServiceInterface
{
    public function __construct(
        private readonly TenantDomainRepositoryInterface $tenantDomainRepository,
    ) {
        parent::__construct($tenantDomainRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) $data['id'];
        $tenantDomain = $this->tenantDomainRepository->find($id);

        if (! $tenantDomain || $tenantDomain->getId() === null) {
            throw new TenantDomainNotFoundException($id);
        }

        $deleted = $this->tenantDomainRepository->delete($tenantDomain->getId());

        if ($deleted) {
            $this->addEvent(new TenantDomainDeleted($tenantDomain->getTenantId(), $tenantDomain->getId()));
        }

        return $deleted;
    }
}
