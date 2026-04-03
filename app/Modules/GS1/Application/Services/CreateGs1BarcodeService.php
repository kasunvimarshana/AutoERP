<?php

declare(strict_types=1);

namespace Modules\GS1\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\GS1\Application\Contracts\CreateGs1BarcodeServiceInterface;
use Modules\GS1\Application\DTOs\Gs1BarcodeData;
use Modules\GS1\Domain\Entities\Gs1Barcode;
use Modules\GS1\Domain\Events\Gs1BarcodeCreated;
use Modules\GS1\Domain\RepositoryInterfaces\Gs1BarcodeRepositoryInterface;

class CreateGs1BarcodeService extends BaseService implements CreateGs1BarcodeServiceInterface
{
    public function __construct(private readonly Gs1BarcodeRepositoryInterface $barcodeRepository)
    {
        parent::__construct($barcodeRepository);
    }

    protected function handle(array $data): Gs1Barcode
    {
        $dto = Gs1BarcodeData::fromArray($data);

        $barcode = new Gs1Barcode(
            tenantId:               $dto->tenantId,
            gs1IdentifierId:        $dto->gs1IdentifierId,
            barcodeType:            $dto->barcodeType,
            barcodeData:            $dto->barcodeData,
            applicationIdentifiers: $dto->applicationIdentifiers,
            isPrimary:              $dto->isPrimary,
            isActive:               $dto->isActive,
            metadata:               $dto->metadata ? new Metadata($dto->metadata) : null,
        );

        $saved = $this->barcodeRepository->save($barcode);
        $this->addEvent(new Gs1BarcodeCreated($saved));

        return $saved;
    }
}
