<?php
namespace Modules\Pricing\Application\Services;

use Illuminate\Support\Facades\Event;
use Modules\Pricing\Application\Contracts\CreateTaxRateServiceInterface;
use Modules\Pricing\Application\DTOs\TaxRateData;
use Modules\Pricing\Domain\Entities\TaxRate;
use Modules\Pricing\Domain\Events\TaxRateCreated;
use Modules\Pricing\Domain\RepositoryInterfaces\TaxRateRepositoryInterface;

class CreateTaxRateService implements CreateTaxRateServiceInterface
{
    public function __construct(private readonly TaxRateRepositoryInterface $repository) {}

    public function execute(TaxRateData $data): TaxRate
    {
        $taxRate = $this->repository->create([
            'tenant_id'   => $data->tenantId,
            'name'        => $data->name,
            'code'        => $data->code,
            'rate'        => $data->rate,
            'type'        => $data->type,
            'is_active'   => $data->isActive,
            'description' => $data->description,
            'region'      => $data->region,
            'tax_class'   => $data->taxClass,
        ]);

        Event::dispatch(new TaxRateCreated($data->tenantId, $taxRate->id));

        return $taxRate;
    }
}
