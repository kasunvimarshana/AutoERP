<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\Serials;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\Services\SerialServiceInterface;
use Modules\Inventory\Application\Contracts\UseCases\Serials\UpdateSerialServiceInterface;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class UpdateSerialService implements UpdateSerialServiceInterface
{
    public function __construct(private readonly SerialServiceInterface $serialService)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            return $this->serialService->updateSerial($id, $payload);
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
