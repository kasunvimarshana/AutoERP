<?php

declare(strict_types=1);

namespace Modules\Product\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Product\Application\Contracts\DeleteUomConversionServiceInterface;
use Modules\Product\Domain\Exceptions\UomConversionNotFoundException;
use Modules\Product\Domain\RepositoryInterfaces\UomConversionRepositoryInterface;

class DeleteUomConversionService extends BaseService implements DeleteUomConversionServiceInterface
{
    public function __construct(private readonly UomConversionRepositoryInterface $uomConversionRepository)
    {
        parent::__construct($uomConversionRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $uomConversion = $this->uomConversionRepository->find($id);

        if (! $uomConversion) {
            throw new UomConversionNotFoundException($id);
        }

        return $this->uomConversionRepository->delete($id);
    }
}
