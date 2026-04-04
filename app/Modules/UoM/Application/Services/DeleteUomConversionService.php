<?php
namespace Modules\UoM\Application\Services;

use Modules\UoM\Application\Contracts\DeleteUomConversionServiceInterface;
use Modules\UoM\Domain\Events\UomConversionDeleted;
use Modules\UoM\Domain\RepositoryInterfaces\UomConversionRepositoryInterface;
use Modules\UoM\Domain\RepositoryInterfaces\UnitOfMeasureRepositoryInterface;

class DeleteUomConversionService implements DeleteUomConversionServiceInterface
{
    public function __construct(
        private readonly UomConversionRepositoryInterface $repository,
        private readonly UnitOfMeasureRepositoryInterface $uomRepository,
    ) {}

    public function execute(int $id): bool
    {
        $conversion = $this->repository->findById($id);
        if (!$conversion) {
            throw new \DomainException("UomConversion not found: {$id}");
        }
        $fromUom = $this->uomRepository->findById($conversion->fromUomId);
        $tenantId = $fromUom ? $fromUom->tenantId : 0;
        $result = $this->repository->delete($conversion);
        event(new UomConversionDeleted($tenantId, $id));
        return $result;
    }
}
