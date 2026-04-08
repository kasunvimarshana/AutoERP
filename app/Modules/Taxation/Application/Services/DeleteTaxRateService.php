<?php

declare(strict_types=1);

namespace Modules\Taxation\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Taxation\Application\Contracts\DeleteTaxRateServiceInterface;
use Modules\Taxation\Domain\Exceptions\TaxRateNotFoundException;
use Modules\Taxation\Domain\RepositoryInterfaces\TaxRateRepositoryInterface;

class DeleteTaxRateService extends BaseService implements DeleteTaxRateServiceInterface
{
    public function __construct(private readonly TaxRateRepositoryInterface $taxRateRepository)
    {
        parent::__construct($taxRateRepository);
    }

    protected function handle(array $data): bool
    {
        $id = $data['id'];

        $taxRate = $this->taxRateRepository->find($id);
        if (!$taxRate) {
            throw new TaxRateNotFoundException($id);
        }

        $this->taxRateRepository->delete($id);

        return true;
    }
}
