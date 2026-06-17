import { useCallback } from 'react';
import { listInvoices } from '@/modules/invoice/invoiceApi';
import { lookupApi } from '@/shared/api/lookupApi';
import { listUoms, searchCurrencies, searchWarehouseLocations, searchWarehouses } from '@/shared/api/referenceApi';
import { LookupSelect } from '@/shared/components/LookupSelect';
import type { NamedResource } from '@/shared/types/common';
import type { LookupBehaviorOptions, LookupLoadParams, LookupResult } from '@/shared/types/lookup';
import { searchSalesDeliveries, searchSalesOrders } from '../salesApi';

interface LookupProps extends LookupBehaviorOptions {
    value: NamedResource | null;
    onChange: (value: NamedResource | null) => void;
    error?: string;
    disabled?: boolean;
    required?: boolean;
}

export function CustomerLookupSelect(props: LookupProps) {
    return <LookupSelect label="Customer" search={lookupApi.customers} placeholder="Search customers..." {...props} />;
}
export function SalesItemLookupSelect(props: LookupProps) {
    return <LookupSelect label="Item" search={lookupApi.items} placeholder="Search active items..." {...props} />;
}
export function SalesUomLookupSelect(props: LookupProps) {
    return <LookupSelect label="UOM" search={listUoms} placeholder="Search UOMs..." loadOnOpen minSearchLength={0} {...props} />;
}
export function SalesWarehouseLookupSelect(props: LookupProps) {
    return <LookupSelect label="Warehouse" search={searchWarehouses} placeholder="Search warehouses..." loadOnOpen minSearchLength={0} {...props} />;
}
export function SalesCurrencyLookupSelect(props: LookupProps) {
    return <LookupSelect label="Currency" search={searchCurrencies} placeholder="Search currency..." loadOnOpen minSearchLength={0} {...props} />;
}
export function SalesOrderLookupSelect(props: LookupProps) {
    return <LookupSelect label="Sales order" search={searchSalesOrders} placeholder="Search order number or customer..." {...props} />;
}
export function SalesDeliveryLookupSelect(props: LookupProps) {
    return <LookupSelect label="Delivery" search={searchSalesDeliveries} placeholder="Search delivery number or customer..." {...props} />;
}
export function SalesInvoiceLookupSelect({ partyId, ...props }: LookupProps & { partyId?: number | null }) {
    const search = useCallback(
        (params: LookupLoadParams) => searchCustomerInvoices(params, partyId),
        [partyId],
    );
    return <LookupSelect label="Customer invoice" search={search} placeholder="Search invoice number..." {...props} />;
}
export function SalesWarehouseLocationLookupSelect({ warehouseId, ...props }: LookupProps & { warehouseId?: number | null }) {
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

async function searchCustomerInvoices(
    { search, page, perPage, signal }: LookupLoadParams,
    partyId?: number | null,
): Promise<LookupResult<NamedResource>> {
    const response = await listInvoices({
        search,
        page,
        invoice_type: 'sales',
        direction: 'outbound',
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
}
