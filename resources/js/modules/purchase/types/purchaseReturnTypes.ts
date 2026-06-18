import type { NamedResource } from '@/shared/types/common';
import type { PurchaseCapabilityDetails, SourceSummary } from '../purchaseTypes';

export type PurchaseReturnStatus = 'draft' | 'approved' | 'posted' | 'cancelled';

export interface ReturnableLine {
    id: number;
    source_line_type: string;
    source_line_id: number;
    item?: NamedResource | null;
    uom?: NamedResource | null;
    returnable_quantity: string;
    unit_price: string;
}

export interface PurchaseReturnLine {
    id?: number;
    line_number?: number | null;
    client_line_key?: string | null;
    source_line_type?: string | null;
    source_line_id?: number | null;
    item?: NamedResource | null;
    item_id?: number | null;
    item_variant?: NamedResource | null;
    uom?: NamedResource | null;
    returned_quantity: string;
    source_quantity?: string;
    previously_returned_quantity?: string;
    remaining_quantity?: string;
    unit_price: string;
    cost_basis?: string | null;
    base_amount?: string;
    discount_amount?: string;
    tax_amount?: string;
    charge_amount?: string;
    line_total?: string;
    reason?: string | null;
}

export interface PurchaseReturn {
    id: number;
    return_number?: string;
    return_date?: string;
    return_type?: 'referenced' | 'manual_supplier_return' | string;
    source_type?: string | null;
    source_id?: number | null;
    source?: SourceSummary | null;
    status?: PurchaseReturnStatus | string;
    capabilities?: PurchaseCapabilityDetails<'can_approve' | 'can_post' | 'can_cancel' | 'read_only'> & {
        can_approve?: boolean;
        can_post?: boolean;
        can_cancel?: boolean;
        read_only?: boolean;
    };
    supplier?: NamedResource | null;
    warehouse?: NamedResource | null;
    warehouse_location?: NamedResource | null;
    approval_required?: boolean;
    affects_supplier_balance?: boolean;
    cost_basis?: string | null;
    reason?: string | null;
    subtotal?: string;
    adjustment_return_total?: string;
    grand_total?: string;
    debit_note_id?: number | null;
    debit_note?: { id: number; debit_note_number?: string; status?: string } | null;
    lines?: PurchaseReturnLine[];
    adjustment_allocations?: Array<Record<string, unknown>>;
}

export interface ReferencedPurchaseReturnPayload {
    return_date: string;
    reason?: string;
    return_type?: 'referenced';
    source_id?: number;
    lines: Array<{
        source_line_type: 'goods_receipt_note_line';
        source_line_id: number;
        returned_quantity: string;
        reason?: string;
    }>;
}

export interface ManualPurchaseReturnPayload {
    return_date: string;
    warehouse_id: number;
    warehouse_location_id?: number;
    supplier_id?: number;
    reason: string;
    return_type?: 'manual_supplier_return';
    cost_basis?: string;
    lines: Array<{
        client_line_key: string;
        returned_quantity: string;
        item_id?: number;
        item_variant_id?: number;
        uom_id?: number;
        unit_price?: string;
        cost_basis: string;
        reason?: string;
    }>;
}

export type PurchaseReturnPayload = ReferencedPurchaseReturnPayload | ManualPurchaseReturnPayload;
