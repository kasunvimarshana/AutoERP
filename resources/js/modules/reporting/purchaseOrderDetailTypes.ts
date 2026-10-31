import type { ApiCollection } from '@/shared/types/api';
import type { ReportDefinition } from './genericReportTypes';

export interface PurchaseOrderDetailItem {
    id: number;
    description: string;
    item_code: string;
    variant: string;
    uom: string;
    unit_price: string;
    ordered_quantity: string;
    grn_quantity: string;
    grn_cost: string;
}

export interface PurchaseOrderDetailOrder {
    id: number;
    purchase_order_number: string;
    purchase_order_date: string;
    status: string;
    status_action: { label: string; actor: { name: string } | null };
    supplier: { id: number | null; name: string; code: string };
    warehouse: string;
    currency: string;
    po_amount: string;
    grn_cost: string;
    ordered_quantity: string | null;
    grn_quantity: string | null;
    uom: string | null;
    items: PurchaseOrderDetailItem[];
}

export interface PurchaseOrderDetailTotal {
    currency: string;
    po_amount: string;
    grn_cost: string;
    ordered_quantity: string | null;
    grn_quantity: string | null;
    uom: string | null;
}

export interface PurchaseOrderDetailSupplierTotal extends PurchaseOrderDetailTotal {
    supplier_id: number | null;
    supplier: string;
    order_count: number;
}

export interface PurchaseOrderDetailResult extends ApiCollection<PurchaseOrderDetailOrder> {
    report: ReportDefinition;
    summary: {
        total_orders: number;
        currencies: PurchaseOrderDetailTotal[];
        suppliers: PurchaseOrderDetailSupplierTotal[];
    };
}
