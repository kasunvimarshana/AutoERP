import type {
    PurchaseOrder,
    PurchaseOrderStatus,
    PurchaseReturnStatus,
} from './purchaseTypes';

export function purchaseOrderCapabilities(orderOrStatus?: PurchaseOrder | PurchaseOrderStatus) {
    const status = typeof orderOrStatus === 'string' ? orderOrStatus : orderOrStatus?.status;
    const server = typeof orderOrStatus === 'string' ? undefined : orderOrStatus?.capabilities;

    return {
        canEdit: server?.can_edit ?? status === 'draft',
        canSubmit: server?.can_submit ?? status === 'draft',
        canApprove: server?.can_approve ?? status === 'pending_approval',
        canReceive: server?.can_receive ?? status === 'approved',
        canInvoice: server?.can_invoice ?? status === 'approved',
        canReturn: server?.can_return ?? status === 'approved',
        canCancel: server?.can_cancel ?? (status === 'draft' || status === 'pending_approval' || status === 'approved'),
        canClose: server?.can_close ?? false,
        canDelete: server?.can_delete ?? status === 'draft',
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
