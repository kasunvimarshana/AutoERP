<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\Serials;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\SerialServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\Serials\CreateSerialServiceInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class CreateSerialService implements CreateSerialServiceInterface
{
    public function __construct(private readonly SerialServiceInterface $serialService)
    {
    }

    public function execute(array $payload): Result
    {
        try {
            return $this->serialService->createSerial($payload);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
