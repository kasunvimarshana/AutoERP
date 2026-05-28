<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts\Services;

use Modules\Core\Application\Results\Result;

interface StockReservationServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     * @return Result<\Modules\Core\Application\DTO\DataRecord>
     */
    public function reserve(array $payload): Result;

    /**
     * @param array<string, mixed> $payload
     * @return Result<\Modules\Core\Application\DTO\DataRecord>
     */
    public function updateReservation(int|string $reservationId, array $payload): Result;

    /**
     * @param array<string, mixed> $payload
     * @return Result<\Modules\Core\Application\DTO\DataRecord>
     */
    public function release(int|string $reservationId, array $payload = []): Result;

    /**
     * @param array<string, mixed> $payload
     * @return Result<\Modules\Core\Application\DTO\DataRecord>
     */
    public function consume(int|string $reservationId, array $payload = []): Result;
}
