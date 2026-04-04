<?php
namespace Modules\UoM\Application\Services;

use Modules\UoM\Application\Contracts\UpdateUomConversionServiceInterface;
use Modules\UoM\Application\DTOs\UomConversionData;
use Modules\UoM\Domain\Entities\UomConversion;
use Modules\UoM\Domain\Events\UomConversionUpdated;
use Modules\UoM\Domain\RepositoryInterfaces\UomConversionRepositoryInterface;
use Modules\UoM\Domain\RepositoryInterfaces\UnitOfMeasureRepositoryInterface;

class UpdateUomConversionService implements UpdateUomConversionServiceInterface
{
    public function __construct(
        private readonly UomConversionRepositoryInterface $repository,
        private readonly UnitOfMeasureRepositoryInterface $uomRepository,
    ) {}

    public function execute(int $id, UomConversionData $data): UomConversion
    {
        $conversion = $this->repository->findById($id);
        if (!$conversion) {
            throw new \DomainException("UomConversion not found: {$id}");
        }
        $fromUom = $this->uomRepository->findById($data->fromUomId);
        if (!$fromUom) {
            throw new \DomainException("UnitOfMeasure not found: {$data->fromUomId}");
        }
        $updated = $this->repository->update($conversion, $data->toArray());
        event(new UomConversionUpdated($fromUom->tenantId, $id));
        return $updated;
    }
}
