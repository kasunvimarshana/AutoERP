import { useCallback } from 'react';
import { listInvoices } from '@/modules/invoice/invoiceApi';
import { LookupSelect } from '@/shared/components/LookupSelect';
import { lookupApi } from '@/shared/api/lookupApi';
import { listUoms, searchCurrencies, searchWarehouseLocations, searchWarehouses } from '@/shared/api/referenceApi';
import type { NamedResource } from '@/shared/types/common';
import type { LookupBehaviorOptions, LookupLoadParams, LookupResult } from '@/shared/types/lookup';
import { searchGoodsReceipts, searchPurchaseOrders } from '../purchaseApi';

interface LookupProps extends LookupBehaviorOptions {
    value: NamedResource | null;
    onChange: (value: NamedResource | null) => void;
    error?: string;
    disabled?: boolean;
    required?: boolean;
}

interface WarehouseLocationLookupProps extends LookupProps {
    warehouseId?: number | null;
}

export function SupplierLookupSelect(props: LookupProps) {
    return <LookupSelect label="Supplier" search={lookupApi.suppliers} placeholder="Search suppliers..." {...props} />;
}

export function ItemLookupSelect(props: LookupProps) {
    return <LookupSelect label="Item" search={lookupApi.items} placeholder="Search items..." {...props} />;
}

export function UomLookupSelect(props: LookupProps) {
    return <LookupSelect label="UOM" search={listUoms} placeholder="Search UOMs..." loadOnOpen minSearchLength={0} {...props} />;
}

export function WarehouseLookupSelect(props: LookupProps) {
    return <LookupSelect label="Warehouse" search={searchWarehouses} placeholder="Search warehouses..." loadOnOpen minSearchLength={0} {...props} />;
}

export function WarehouseLocationLookupSelect({ warehouseId, ...props }: WarehouseLocationLookupProps) {
    const search = useCallback(
        (params: LookupLoadParams) => searchWarehouseLocations(params, warehouseId),
        [warehouseId],
    );

    return (
        <LookupSelect
            label="Location"
            search={search}
            placeholder="Search locations..."
            {...props}
            disabled={!warehouseId || props.disabled}
            loadOnOpen={props.loadOnOpen ?? true}
            minSearchLength={props.minSearchLength ?? 0}
        />
    );
}

export function CurrencyLookupSelect(props: LookupProps) {
    return <LookupSelect label="Currency" search={searchCurrencies} placeholder="Search currency code..." loadOnOpen minSearchLength={0} {...props} />;
}

export function PurchaseOrderLookupSelect(props: LookupProps) {
    return <LookupSelect label="Purchase order" search={searchPurchaseOrders} placeholder="Search PO number or supplier..." {...props} />;
}

export function GoodsReceiptLookupSelect(props: LookupProps) {
    return <LookupSelect label="Goods receipt" search={searchGoodsReceipts} placeholder="Search GRN number or supplier..." {...props} />;
}

export function PurchaseInvoiceLookupSelect({ partyId, ...props }: LookupProps & { partyId?: number | null }) {
    const search = useCallback(
        async ({ search, page, perPage, signal }: LookupLoadParams): Promise<LookupResult<NamedResource>> => {
            const response = await listInvoices({
                search,
                page,
                invoice_type: 'purchase',
                direction: 'inbound',
                party_id: partyId ?? undefined,
                per_page: perPage,
            }, signal);

            return {
                data: response.data.map((invoice) => ({
                    id: invoice.id,
                    code: invoice.invoice_number,
                    name: `${invoice.invoice_number ?? 'Invoice'}${invoice.party?.name ? ` - ${invoice.party.name}` : ''}`,
                })),
                links: response.links,
                meta: response.meta,
            };
        },
        [partyId],
    );

    return <LookupSelect label="Supplier invoice" search={search} placeholder="Search invoice number..." {...props} />;
}
