<?php

declare(strict_types=1);

namespace Modules\Taxation\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Taxation\Application\Contracts\FindTaxRateServiceInterface;
use Modules\Taxation\Domain\RepositoryInterfaces\TaxRateRepositoryInterface;

class FindTaxRateService extends BaseService implements FindTaxRateServiceInterface
{
    public function __construct(private readonly TaxRateRepositoryInterface $taxRateRepository)
    {
        parent::__construct($taxRateRepository);
    }

    protected function handle(array $data): mixed
    {
        return $this->taxRateRepository->find($data['id'] ?? null);
    }
}
