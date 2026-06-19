import type {
    PurchaseCapabilityDetail,
    PurchaseOrder,
    PurchaseOrderStatus,
    PurchaseReturn,
} from './purchaseTypes';

export function capabilityDetail(
    capabilities: { details?: Record<string, PurchaseCapabilityDetail | undefined> } | undefined,
    key: string,
) {
    return capabilities?.details?.[key];
}

export function purchaseOrderCapabilities(orderOrStatus?: PurchaseOrder | PurchaseOrderStatus) {
    const status = typeof orderOrStatus === 'string' ? orderOrStatus : orderOrStatus?.status;
    const server = typeof orderOrStatus === 'string' ? undefined : orderOrStatus?.capabilities;

    return {
        canEdit: Boolean(server?.can_edit),
        canSubmit: Boolean(server?.can_submit),
        canApprove: Boolean(server?.can_approve),
        canReceive: Boolean(server?.can_receive),
        canInvoice: Boolean(server?.can_invoice),
        canCancel: Boolean(server?.can_cancel),
        canClose: server?.can_close ?? false,
        canForceClose: server?.can_force_close ?? false,
        canDelete: Boolean(server?.can_delete),
        isReadOnly: ['closed', 'cancelled'].includes(status ?? ''),
    };
}

export function purchaseReturnCapabilities(returnOrStatus?: PurchaseReturn | string) {
    const server = typeof returnOrStatus === 'string' ? undefined : returnOrStatus?.capabilities;

    return {
        canApprove: Boolean(server?.can_approve),
        canPost: Boolean(server?.can_post),
        canCancel: Boolean(server?.can_cancel),
    };
}
