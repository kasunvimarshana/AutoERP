<?php

declare(strict_types=1);

namespace Modules\GS1\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\GS1\Application\Contracts\FindGs1BarcodeServiceInterface;
use Modules\GS1\Domain\RepositoryInterfaces\Gs1BarcodeRepositoryInterface;

class FindGs1BarcodeService extends BaseService implements FindGs1BarcodeServiceInterface
{
    public function __construct(private readonly Gs1BarcodeRepositoryInterface $barcodeRepository)
    {
        parent::__construct($barcodeRepository);
    }

    protected function handle(array $data): mixed
    {
        return $this->barcodeRepository->find($data['id'] ?? null);
    }
}
