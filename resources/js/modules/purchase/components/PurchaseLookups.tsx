import { LookupSelect } from '@/shared/components/LookupSelect';
import { lookupApi } from '@/shared/api/lookupApi';
import { listUoms, searchCurrencies, searchWarehouseLocations, searchWarehouses } from '@/shared/api/referenceApi';
import type { NamedResource } from '@/shared/types/common';
import { listInvoices } from '@/modules/invoice/invoiceApi';
import { searchGoodsReceipts, searchPurchaseOrders } from '../purchaseApi';

interface LookupProps {
    value: NamedResource | null;
    onChange: (value: NamedResource | null) => void;
    error?: string;
}

export function SupplierLookupSelect(props: LookupProps) {
    return <LookupSelect label="Supplier" search={lookupApi.suppliers} placeholder="Search suppliers..." {...props} />;
}

export function ItemLookupSelect(props: LookupProps) {
    return <LookupSelect label="Item" search={lookupApi.items} placeholder="Search items..." {...props} />;
}

export function UomLookupSelect(props: LookupProps) {
    return <LookupSelect label="UOM" search={listUoms} placeholder="Search UOMs..." {...props} />;
}

export function WarehouseLookupSelect(props: LookupProps) {
    return <LookupSelect label="Warehouse" search={searchWarehouses} placeholder="Search warehouses..." {...props} />;
}

export function WarehouseLocationLookupSelect(props: LookupProps) {
    return <LookupSelect label="Location" search={searchWarehouseLocations} placeholder="Search locations..." {...props} />;
}

export function CurrencyLookupSelect(props: LookupProps) {
    return <LookupSelect label="Currency" search={searchCurrencies} placeholder="Search currency code..." {...props} />;
}

export function PurchaseOrderLookupSelect(props: LookupProps) {
    return <LookupSelect label="Purchase order" search={searchPurchaseOrders} placeholder="Search PO number or supplier..." {...props} />;
}

export function GoodsReceiptLookupSelect(props: LookupProps) {
    return <LookupSelect label="Goods receipt" search={searchGoodsReceipts} placeholder="Search GRN number or supplier..." {...props} />;
}

export function InvoiceLookupSelect(props: LookupProps) {
    return <LookupSelect label="Supplier invoice" search={searchInvoices} placeholder="Search invoice number..." {...props} />;
}

async function searchInvoices(search: string, signal?: AbortSignal): Promise<NamedResource[]> {
    const response = await listInvoices({ search, invoice_type: 'purchase', direction: 'inbound', per_page: 20 }, signal);
    return response.data.map((invoice) => ({
        id: invoice.id,
        code: invoice.invoice_number ?? `INV-${invoice.id}`,
        name: `${invoice.invoice_number ?? `Invoice #${invoice.id}`}${invoice.party?.name ? ` - ${invoice.party.name}` : ''}`,
    }));
}
