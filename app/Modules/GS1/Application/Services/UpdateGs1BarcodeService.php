<?php

declare(strict_types=1);

namespace Modules\GS1\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\GS1\Application\Contracts\UpdateGs1BarcodeServiceInterface;
use Modules\GS1\Application\DTOs\UpdateGs1BarcodeData;
use Modules\GS1\Domain\Entities\Gs1Barcode;
use Modules\GS1\Domain\Events\Gs1BarcodeUpdated;
use Modules\GS1\Domain\Exceptions\Gs1BarcodeNotFoundException;
use Modules\GS1\Domain\RepositoryInterfaces\Gs1BarcodeRepositoryInterface;

class UpdateGs1BarcodeService extends BaseService implements UpdateGs1BarcodeServiceInterface
{
    public function __construct(private readonly Gs1BarcodeRepositoryInterface $barcodeRepository)
    {
        parent::__construct($barcodeRepository);
    }

    protected function handle(array $data): Gs1Barcode
    {
        $dto = UpdateGs1BarcodeData::fromArray($data);

        /** @var Gs1Barcode|null $barcode */
        $barcode = $this->barcodeRepository->find($dto->id);
        if (! $barcode) {
            throw new Gs1BarcodeNotFoundException($dto->id);
        }

        $barcode->updateDetails(
            barcodeType:            $dto->barcodeType ?? $barcode->getBarcodeType(),
            barcodeData:            $dto->barcodeData ?? $barcode->getBarcodeData(),
            applicationIdentifiers: $dto->applicationIdentifiers ?? $barcode->getApplicationIdentifiers(),
            isPrimary:              $dto->isPrimary ?? $barcode->isPrimary(),
            isActive:               $dto->isActive ?? $barcode->isActive(),
            metadata:               $dto->metadata ? new Metadata($dto->metadata) : null,
        );

        $saved = $this->barcodeRepository->save($barcode);
        $this->addEvent(new Gs1BarcodeUpdated($saved));

        return $saved;
    }
}
