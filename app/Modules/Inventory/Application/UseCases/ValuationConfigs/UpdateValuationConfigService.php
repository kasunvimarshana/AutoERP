<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\ValuationConfigs;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\ValuationConfigServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\ValuationConfigs\UpdateValuationConfigServiceInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class UpdateValuationConfigService implements UpdateValuationConfigServiceInterface
{
    public function __construct(private readonly ValuationConfigServiceInterface $valuationConfigService)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            return $this->valuationConfigService->updateConfig($id, $payload);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
