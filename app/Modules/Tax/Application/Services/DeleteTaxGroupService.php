<?php

declare(strict_types=1);

namespace Modules\Tax\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Tax\Application\Contracts\DeleteTaxGroupServiceInterface;
use Modules\Tax\Domain\RepositoryInterfaces\TaxGroupRepositoryInterface;

class DeleteTaxGroupService extends BaseService implements DeleteTaxGroupServiceInterface
{
    public function __construct(private readonly TaxGroupRepositoryInterface $taxGroupRepository)
    {
        parent::__construct($taxGroupRepository);
    }

    protected function handle(array $data): bool
    {
        return $this->taxGroupRepository->delete((int) $data['id']);
    }
}
