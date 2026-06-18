import type {
    PurchaseOrder,
    PurchaseOrderStatus,
    PurchaseReturnStatus,
} from './purchaseTypes';

export function purchaseOrderCapabilities(orderOrStatus?: PurchaseOrder | PurchaseOrderStatus) {
    const status = typeof orderOrStatus === 'string' ? orderOrStatus : orderOrStatus?.status;
    const server = typeof orderOrStatus === 'string' ? undefined : orderOrStatus?.capabilities;

    return {
        canEdit: Boolean(server?.can_edit),
        canSubmit: Boolean(server?.can_submit),
        canApprove: Boolean(server?.can_approve),
        canReceive: Boolean(server?.can_receive),
        canInvoice: Boolean(server?.can_invoice),
        canReturn: Boolean(server?.can_return),
        canCancel: Boolean(server?.can_cancel),
        canClose: server?.can_close ?? false,
        canDelete: Boolean(server?.can_delete),
        isReadOnly: ['closed', 'cancelled'].includes(status ?? ''),
    };
}

export function purchaseReturnCapabilities(status?: PurchaseReturnStatus | string) {
    return {
        canApprove: status === 'draft',
        canPost: status === 'draft' || status === 'approved',
        canCancel: status !== 'posted' && status !== 'cancelled',
    };
}
