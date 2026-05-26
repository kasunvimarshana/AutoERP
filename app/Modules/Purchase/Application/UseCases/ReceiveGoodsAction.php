<?php

declare(strict_types=1);

namespace Modules\Purchase\Application\UseCases;

use Modules\Purchase\Application\DTOs\ReceiveGoodsDTO;
use Modules\Purchase\Domain\Services\GoodsReceiptService;

final class ReceiveGoodsAction
{
    public function __construct(private readonly GoodsReceiptService $service)
    {
    }

    public function execute(ReceiveGoodsDTO $dto): array
    {
        return $this->service->create($dto->payload);
    }

    public function confirm(int $grnHeaderId): array
    {
        return $this->service->confirm($grnHeaderId);
    }
}
