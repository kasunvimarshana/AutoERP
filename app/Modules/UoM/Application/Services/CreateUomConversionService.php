<?php
namespace Modules\UoM\Application\Services;

use Modules\UoM\Application\Contracts\CreateUomConversionServiceInterface;
use Modules\UoM\Application\DTOs\UomConversionData;
use Modules\UoM\Domain\Entities\UomConversion;
use Modules\UoM\Domain\Events\UomConversionCreated;
use Modules\UoM\Domain\RepositoryInterfaces\UomConversionRepositoryInterface;
use Modules\UoM\Domain\RepositoryInterfaces\UnitOfMeasureRepositoryInterface;

class CreateUomConversionService implements CreateUomConversionServiceInterface
{
    public function __construct(
        private readonly UomConversionRepositoryInterface $repository,
        private readonly UnitOfMeasureRepositoryInterface $uomRepository,
    ) {}

    public function execute(UomConversionData $data): UomConversion
    {
        $fromUom = $this->uomRepository->findById($data->fromUomId);
        if (!$fromUom) {
            throw new \DomainException("UnitOfMeasure not found: {$data->fromUomId}");
        }
        $conversion = $this->repository->create($data->toArray());
        event(new UomConversionCreated($fromUom->tenantId, $conversion->id));
        return $conversion;
    }
}
