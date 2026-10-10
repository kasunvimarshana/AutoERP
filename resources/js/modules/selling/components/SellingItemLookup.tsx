import { useCallback } from 'react';
import { lookupApi, type ItemLookupResource } from '@/shared/api/lookupApi';
import { LookupSelect } from '@/shared/components/LookupSelect';
import type { LookupLoadParams } from '@/shared/types/lookup';

export function SellingItemLookup({
    value,
    warehouseId,
    warehouseLocationId,
    saleDate,
    onChange,
}: {
    value: ItemLookupResource | null;
    warehouseId: number | null;
    warehouseLocationId: number | null;
    saleDate: string;
    onChange: (item: ItemLookupResource | null) => void;
}) {
    const search = useCallback((params: LookupLoadParams) =>
        lookupApi.sellingStockableItems(params, warehouseId, saleDate, warehouseLocationId), [saleDate, warehouseId, warehouseLocationId]);

    return (
        <LookupSelect
            label="Item"
            value={value}
            onChange={onChange}
            search={search}
            renderOption={(item) => (
                <div className="flex items-start justify-between gap-3">
                    <div className="min-w-0">
                        <div className="truncate font-medium">{item.code} - {item.name}</div>
                        <div className={`mt-1 text-xs ${Number(item.available_stock_quantity ?? 0) > 0 ? 'text-emerald-700' : 'text-rose-600'}`}>
                            Available: {item.available_stock_quantity ?? '0.000000'} {item.base_uom?.code ?? item.base_uom?.name ?? ''}
                        </div>
                        <div className="mt-1 text-xs font-semibold text-sky-700">
                            Sale price: {item.tenant_base_currency?.symbol ?? item.tenant_base_currency?.code ?? ''} {item.resolved_sales_unit_price ?? 'Not configured'}
                        </div>
                        {item.available_batches && item.available_batches.length > 0 && (
                            <div className="mt-1 text-xs text-slate-600">
                                Batches: {item.available_batches.map((batch) => `${batch.batch_number}${batch.lot_number ? ` / Lot ${batch.lot_number}` : ''} (${batch.available_quantity} ${item.base_uom?.code ?? item.base_uom?.name ?? ''})`).join(', ')}
                            </div>
                        )}
                    </div>
                    <span className="shrink-0 rounded-full bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700">Inventory item</span>
                </div>
            )}
            placeholder={warehouseId ? 'Search items...' : 'Choose a warehouse first'}
            disabled={warehouseId === null}
        />
    );
}
