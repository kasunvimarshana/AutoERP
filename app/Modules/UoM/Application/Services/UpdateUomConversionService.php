<?php

declare(strict_types=1);

namespace Modules\UoM\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\UoM\Application\Contracts\UpdateUomConversionServiceInterface;
use Modules\UoM\Application\DTOs\UpdateUomConversionData;
use Modules\UoM\Domain\Entities\UomConversion;
use Modules\UoM\Domain\Events\UomConversionUpdated;
use Modules\UoM\Domain\Exceptions\UomConversionNotFoundException;
use Modules\UoM\Domain\RepositoryInterfaces\UomConversionRepositoryInterface;

class UpdateUomConversionService extends BaseService implements UpdateUomConversionServiceInterface
{
    private UomConversionRepositoryInterface $conversionRepository;

    public function __construct(UomConversionRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->conversionRepository = $repository;
    }

    protected function handle(array $data): UomConversion
    {
        $dto        = UpdateUomConversionData::fromArray($data);
        $id         = (int) ($dto->id ?? 0);
        $conversion = $this->conversionRepository->find($id);

        if (! $conversion) {
            throw new UomConversionNotFoundException($id);
        }

        $fromUomId = $dto->isProvided('fromUomId')
            ? (int) $dto->fromUomId
            : $conversion->getFromUomId();

        $toUomId = $dto->isProvided('toUomId')
            ? (int) $dto->toUomId
            : $conversion->getToUomId();

        $factor = $dto->isProvided('factor')
            ? (float) $dto->factor
            : $conversion->getFactor();

        $isActive = $dto->isProvided('isActive')
            ? (bool) $dto->isActive
            : $conversion->isActive();

        $updated = new UomConversion(
            tenantId:  $conversion->getTenantId(),
            fromUomId: $fromUomId,
            toUomId:   $toUomId,
            factor:    $factor,
            isActive:  $isActive,
            id:        $conversion->getId(),
        );

        $saved = $this->conversionRepository->save($updated);
        $this->addEvent(new UomConversionUpdated($saved));

        return $saved;
    }
}
