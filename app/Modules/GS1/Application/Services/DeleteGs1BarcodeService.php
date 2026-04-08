<?php

declare(strict_types=1);

namespace Modules\GS1\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\GS1\Application\Contracts\DeleteGs1BarcodeServiceInterface;
use Modules\GS1\Domain\Entities\Gs1Barcode;
use Modules\GS1\Domain\Events\Gs1BarcodeDeleted;
use Modules\GS1\Domain\Exceptions\Gs1BarcodeNotFoundException;
use Modules\GS1\Domain\RepositoryInterfaces\Gs1BarcodeRepositoryInterface;

class DeleteGs1BarcodeService extends BaseService implements DeleteGs1BarcodeServiceInterface
{
    public function __construct(private readonly Gs1BarcodeRepositoryInterface $barcodeRepository)
    {
        parent::__construct($barcodeRepository);
    }

    protected function handle(array $data): bool
    {
        $id = $data['id'];

        /** @var Gs1Barcode|null $barcode */
        $barcode = $this->barcodeRepository->find($id);
        if (! $barcode) {
            throw new Gs1BarcodeNotFoundException($id);
        }

        $this->barcodeRepository->delete($id);
        $this->addEvent(new Gs1BarcodeDeleted($barcode));

        return true;
    }
}
