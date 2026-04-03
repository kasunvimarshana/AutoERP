<?php

declare(strict_types=1);

namespace Modules\UoM\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\UoM\Application\Contracts\CreateUomConversionServiceInterface;
use Modules\UoM\Application\DTOs\UomConversionData;
use Modules\UoM\Domain\Entities\UomConversion;
use Modules\UoM\Domain\Events\UomConversionCreated;
use Modules\UoM\Domain\RepositoryInterfaces\UomConversionRepositoryInterface;

class CreateUomConversionService extends BaseService implements CreateUomConversionServiceInterface
{
    private UomConversionRepositoryInterface $conversionRepository;

    public function __construct(UomConversionRepositoryInterface $repository)
    {
        parent::__construct($repository);
        $this->conversionRepository = $repository;
    }

    protected function handle(array $data): UomConversion
    {
        $dto = UomConversionData::fromArray($data);

        $conversion = new UomConversion(
            tenantId:  $dto->tenantId,
            fromUomId: $dto->fromUomId,
            toUomId:   $dto->toUomId,
            factor:    $dto->factor,
            isActive:  $dto->isActive,
        );

        $saved = $this->conversionRepository->save($conversion);
        $this->addEvent(new UomConversionCreated($saved));

        return $saved;
    }
}
