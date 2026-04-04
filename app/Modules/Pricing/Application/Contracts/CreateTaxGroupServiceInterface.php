<?php
namespace Modules\Pricing\Application\Contracts;
use Modules\Pricing\Application\DTOs\TaxGroupData;
use Modules\Pricing\Domain\Entities\TaxGroup;

interface CreateTaxGroupServiceInterface
{
    public function execute(TaxGroupData $data): TaxGroup;
}
