import type {
    PurchaseOrderStatus,
    PurchaseReturnStatus,
} from './purchaseTypes';

export function purchaseOrderCapabilities(status?: PurchaseOrderStatus) {
    return {
        canEdit: status === 'draft',
        canApprove: status === 'draft',
        canCancel: status === 'draft' || status === 'approved',
        canClose: status === 'approved',
        isReadOnly: ['closed', 'cancelled', 'received', 'invoiced', 'partially_invoiced']
            .includes(status ?? ''),
    };
}

export function purchaseReturnCapabilities(status?: PurchaseReturnStatus | string) {
    return {
        canApprove: status === 'draft',
        canPost: status === 'draft' || status === 'approved',
        canCancel: status !== 'posted' && status !== 'cancelled',
    };
}
