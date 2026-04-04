<?php
namespace Modules\Pricing\Application\Contracts;
use Modules\Pricing\Application\DTOs\TaxRateData;
use Modules\Pricing\Domain\Entities\TaxRate;

interface CreateTaxRateServiceInterface
{
    public function execute(TaxRateData $data): TaxRate;
}
