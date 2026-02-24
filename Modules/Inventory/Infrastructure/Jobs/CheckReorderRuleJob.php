<?php

namespace Modules\Inventory\Infrastructure\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Event;
use Modules\Inventory\Domain\Events\LowStockAlert;
use Modules\Inventory\Infrastructure\Models\ReorderRuleModel;
use Modules\Inventory\Infrastructure\Models\StockLevelModel;

class CheckReorderRuleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public int $timeout = 60;

    public function __construct(
        public readonly string $reorderRuleId,
    ) {}

    private const QUANTITY_PRECISION = 8;

    private const ZERO_QTY = '0.00000000';

    public function handle(): void
    {
        $rule = ReorderRuleModel::find($this->reorderRuleId);

        if (! $rule || ! $rule->is_active) {
            return;
        }

        $query = StockLevelModel::where('product_id', $rule->product_id)
            ->where('tenant_id', $rule->tenant_id);

        if ($rule->location_id) {
            $query->where('location_id', $rule->location_id);
        }

        $stockLevel = $query->first();
        $currentQty = $stockLevel ? $stockLevel->qty : self::ZERO_QTY;

        if (bccomp($currentQty, (string) $rule->reorder_point, self::QUANTITY_PRECISION) < 0) {
            Event::dispatch(new LowStockAlert(
                $rule->product_id,
                $currentQty,
                (string) $rule->reorder_point,
            ));
        }
    }
}
