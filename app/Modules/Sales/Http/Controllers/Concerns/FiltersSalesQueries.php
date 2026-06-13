<?php

declare(strict_types=1);

namespace Modules\Sales\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Modules\Sales\Http\Requests\ListSalesRequest;

trait FiltersSalesQueries
{
    private function applySalesFilters(
        Builder $query,
        ListSalesRequest $request,
        string $numberColumn,
        string $dateColumn,
    ): void {
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function (Builder $builder) use ($numberColumn, $search): void {
                $builder->where($numberColumn, 'like', "%{$search}%")
                    ->orWhereHas('customer', fn (Builder $customer) => $customer
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('display_name', 'like', "%{$search}%")
                        ->orWhere('customer_number', 'like', "%{$search}%"));
            });
        }

        foreach (['status', 'customer_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }
        if ($request->filled('date_from')) {
            $query->whereDate($dateColumn, '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate($dateColumn, '<=', $request->input('date_to'));
        }
    }
}
