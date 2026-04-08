<?php

declare(strict_types=1);

namespace Modules\Taxation\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Taxation\Application\Contracts\ActivateTaxRateServiceInterface;
use Modules\Taxation\Domain\Entities\TaxRate;
use Modules\Taxation\Domain\Events\TaxRateActivated;
use Modules\Taxation\Domain\Exceptions\TaxRateNotFoundException;
use Modules\Taxation\Domain\RepositoryInterfaces\TaxRateRepositoryInterface;

class ActivateTaxRateService extends BaseService implements ActivateTaxRateServiceInterface
{
    public function __construct(private readonly TaxRateRepositoryInterface $taxRateRepository)
    {
        parent::__construct($taxRateRepository);
    }

    protected function handle(array $data): TaxRate
    {
        $id = $data['id'];

        $taxRate = $this->taxRateRepository->find($id);
        if (!$taxRate) {
            throw new TaxRateNotFoundException($id);
        }

        $taxRate->activate();
        $saved = $this->taxRateRepository->save($taxRate);
        $this->addEvent(new TaxRateActivated($saved));

        return $saved;
    }
}
