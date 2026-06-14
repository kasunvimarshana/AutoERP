<?php

declare(strict_types=1);

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\DTOs\StockMovementData;
use Modules\Inventory\DTOs\StockPostingResult;
use Modules\Inventory\Models\InventoryMovement;

final class StockMovementService
{
    public function __construct(
        private readonly InventoryMovementRecorder $recorder,
        private readonly InventoryMovementPoster $poster,
        private readonly InventoryMovementReversalService $reversals,
    ) {}

    public function create(StockMovementData $data): InventoryMovement
    {
        return $this->recorder->create($data);
    }

    public function record(StockMovementData $data, ?int $postedBy = null): InventoryMovement
    {
        return DB::transaction(
            fn (): InventoryMovement => $this->poster->post($this->recorder->create($data), $postedBy),
        );
    }

    public function post(InventoryMovement $movement, ?int $postedBy = null): InventoryMovement
    {
        return $this->poster->post($movement, $postedBy);
    }

    public function reverse(InventoryMovement $movement, ?int $reversedBy = null): InventoryMovement
    {
        return $this->reversals->reverse($movement, $reversedBy);
    }

    public function result(InventoryMovement $movement): StockPostingResult
    {
        return $this->recorder->result($movement);
    }
}
