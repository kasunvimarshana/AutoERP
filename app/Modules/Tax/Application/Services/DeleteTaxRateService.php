<?php

declare(strict_types=1);

namespace Modules\Tax\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Tax\Application\Contracts\DeleteTaxRateServiceInterface;
use Modules\Tax\Domain\RepositoryInterfaces\TaxRateRepositoryInterface;

class DeleteTaxRateService extends BaseService implements DeleteTaxRateServiceInterface
{
    public function __construct(private readonly TaxRateRepositoryInterface $taxRateRepository)
    {
        parent::__construct($taxRateRepository);
    }

    protected function handle(array $data): bool
    {
        return $this->taxRateRepository->delete((int) $data['id']);
    }
}
