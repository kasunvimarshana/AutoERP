<?php

declare(strict_types=1);

namespace Modules\UoM\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\UoM\Application\Contracts\FindUomConversionServiceInterface;
use Modules\UoM\Domain\Entities\UomConversion;
use Modules\UoM\Domain\RepositoryInterfaces\UomConversionRepositoryInterface;

class FindUomConversionService extends BaseService implements FindUomConversionServiceInterface
{
    public function __construct(private readonly UomConversionRepositoryInterface $conversionRepository)
    {
        parent::__construct($conversionRepository);
    }

    public function findConversion(int $tenantId, int $fromUomId, int $toUomId): ?UomConversion
    {
        return $this->conversionRepository->findConversion($tenantId, $fromUomId, $toUomId);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
