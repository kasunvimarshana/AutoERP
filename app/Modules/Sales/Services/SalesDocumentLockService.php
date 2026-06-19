<?php

declare(strict_types=1);

namespace Modules\Sales\Services;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Modules\Sales\Models\SalesCreditNote;
use Modules\Sales\Models\SalesDelivery;
use Modules\Sales\Models\SalesDeliveryLine;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderLine;
use Modules\Sales\Models\SalesReturn;
use Modules\Sales\Models\SalesReturnLine;

final class SalesDocumentLockService
{
    /**
     * @param  list<int>  $ids
     * @return EloquentCollection<int, SalesOrder>
     */
    public function salesOrders(array $ids): EloquentCollection
    {
        return SalesOrder::query()->whereIn('id', $this->ids($ids))->orderBy('id')->lockForUpdate()->get();
    }

    /**
     * @param  list<int>  $ids
     * @return EloquentCollection<int, SalesOrderLine>
     */
    public function salesOrderLines(array $ids): EloquentCollection
    {
        return SalesOrderLine::query()->whereIn('id', $this->ids($ids))->orderBy('id')->lockForUpdate()->get();
    }

    /**
     * @param  list<int>  $ids
     * @return EloquentCollection<int, SalesDelivery>
     */
    public function salesDeliveries(array $ids): EloquentCollection
    {
        return SalesDelivery::query()->whereIn('id', $this->ids($ids))->orderBy('id')->lockForUpdate()->get();
    }

    /**
     * @param  list<int>  $ids
     * @return EloquentCollection<int, SalesDeliveryLine>
     */
    public function salesDeliveryLines(array $ids): EloquentCollection
    {
        return SalesDeliveryLine::query()->whereIn('id', $this->ids($ids))->orderBy('id')->lockForUpdate()->get();
    }

    /**
     * @param  list<int>  $ids
     * @return EloquentCollection<int, SalesReturn>
     */
    public function salesReturns(array $ids): EloquentCollection
    {
        return SalesReturn::query()->whereIn('id', $this->ids($ids))->orderBy('id')->lockForUpdate()->get();
    }

    /**
     * @param  list<int>  $returnIds
     * @return EloquentCollection<int, SalesReturnLine>
     */
    public function salesReturnLinesForReturns(array $returnIds): EloquentCollection
    {
        return SalesReturnLine::query()->whereIn('sales_return_id', $this->ids($returnIds))->orderBy('id')->lockForUpdate()->get();
    }

    /**
     * @param  list<int>  $ids
     * @return EloquentCollection<int, SalesCreditNote>
     */
    public function salesCreditNotes(array $ids): EloquentCollection
    {
        return SalesCreditNote::query()->whereIn('id', $this->ids($ids))->orderBy('id')->lockForUpdate()->get();
    }

    /**
     * @param  list<int>  $ids
     * @return list<int>
     */
    private function ids(array $ids): array
    {
        $normalized = array_values(array_unique(array_filter(
            array_map(static fn (int $id): int => $id, $ids),
            static fn (int $id): bool => $id > 0,
        )));
        sort($normalized);

        return $normalized;
    }
}
