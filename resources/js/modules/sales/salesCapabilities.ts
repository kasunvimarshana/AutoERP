import type { SalesCapabilities, SalesOrder } from './salesTypes';

const allowed = (capabilities: SalesCapabilities | undefined, key: keyof SalesCapabilities) => Boolean(capabilities?.[key]);

export function salesOrderCapabilities(order: SalesOrder) {
    const capabilities = order.capabilities;

    return {
        canEdit: allowed(capabilities, 'can_edit'),
        canSubmit: allowed(capabilities, 'can_submit'),
        canApprove: allowed(capabilities, 'can_approve'),
        canAllocate: allowed(capabilities, 'can_allocate'),
        canDeliver: allowed(capabilities, 'can_deliver'),
        canInvoice: allowed(capabilities, 'can_invoice'),
        canReceivePayment: allowed(capabilities, 'can_receive_payment'),
        canReturn: allowed(capabilities, 'can_return'),
        canCancel: allowed(capabilities, 'can_cancel'),
        canClose: allowed(capabilities, 'can_close'),
        canDelete: allowed(capabilities, 'can_delete'),
        isReadOnly: Boolean(capabilities?.read_only),
    };
}
