import { useCallback } from 'react';
import { listInvoices } from '@/modules/invoice/invoiceApi';
import { lookupApi } from '@/shared/api/lookupApi';
import { listUoms, searchCurrencies, searchWarehouseLocations, searchWarehouses } from '@/shared/api/referenceApi';
import { LookupSelect } from '@/shared/components/LookupSelect';
import type { NamedResource } from '@/shared/types/common';
import { searchSalesDeliveries, searchSalesOrders } from '../salesApi';

interface LookupProps {
    value: NamedResource | null;
    onChange: (value: NamedResource | null) => void;
    error?: string;
}

export function CustomerLookupSelect(props: LookupProps) {
    return <LookupSelect label="Customer" search={lookupApi.customers} placeholder="Search customers..." {...props} />;
}
export function SalesItemLookupSelect(props: LookupProps) {
    return <LookupSelect label="Item" search={lookupApi.items} placeholder="Search active items..." {...props} />;
}
export function SalesUomLookupSelect(props: LookupProps) {
    return <LookupSelect label="UOM" search={listUoms} placeholder="Search UOMs..." {...props} />;
}
export function SalesWarehouseLookupSelect(props: LookupProps) {
    return <LookupSelect label="Warehouse" search={searchWarehouses} placeholder="Search warehouses..." {...props} />;
}
export function SalesCurrencyLookupSelect(props: LookupProps) {
    return <LookupSelect label="Currency" search={searchCurrencies} placeholder="Search currency..." {...props} />;
}
export function SalesOrderLookupSelect(props: LookupProps) {
    return <LookupSelect label="Sales order" search={searchSalesOrders} placeholder="Search order number or customer..." {...props} />;
}
export function SalesDeliveryLookupSelect(props: LookupProps) {
    return <LookupSelect label="Delivery" search={searchSalesDeliveries} placeholder="Search delivery number or customer..." {...props} />;
}
export function SalesInvoiceLookupSelect(props: LookupProps) {
    return <LookupSelect label="Customer invoice" search={searchCustomerInvoices} placeholder="Search invoice number..." {...props} />;
}
export function SalesWarehouseLocationLookupSelect({ warehouseId, ...props }: LookupProps & { warehouseId?: number | null }) {
    const search = useCallback(
        (query: string, signal: AbortSignal) => searchWarehouseLocations(query, signal, warehouseId),
        [warehouseId],
    );
    return <LookupSelect label="Location" search={search} placeholder="Search locations..." {...props} />;
}

async function searchCustomerInvoices(search: string, signal?: AbortSignal): Promise<NamedResource[]> {
    const response = await listInvoices({ search, invoice_type: 'sales', direction: 'outbound', per_page: 20 }, signal);
    return response.data.map((invoice) => ({
        id: invoice.id,
        code: invoice.invoice_number,
        name: `${invoice.invoice_number ?? 'Invoice'}${invoice.party?.name ? ` - ${invoice.party.name}` : ''}`,
    }));
}
