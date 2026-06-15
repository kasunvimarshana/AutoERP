import { Button, LinkButton } from '@/shared/components/Button';
import type { PurchaseOrder } from '../purchaseApi';
import { purchaseOrderCapabilities } from '../purchaseCapabilities';

export function PurchaseOrderActions({ order, busy, onApprove, onCancel, onClose, onDelete }: {
    order: PurchaseOrder;
    busy?: boolean;
    onApprove?: () => void;
    onCancel?: () => void;
    onClose?: () => void;
    onDelete?: () => void;
}) {
    const { canEdit, canApprove, canCancel, canClose, isReadOnly } = purchaseOrderCapabilities(order.status);

    return (
        <div className="flex flex-wrap justify-end gap-2">
            {canEdit && <LinkButton to={`/purchase/orders/${order.id}/edit`} variant="secondary">Edit</LinkButton>}
            {canApprove && <Button type="button" loading={busy} onClick={onApprove}>Approve</Button>}
            {canClose && <Button type="button" variant="secondary" loading={busy} onClick={onClose}>Close</Button>}
            {canCancel && <Button type="button" variant="danger" loading={busy} onClick={onCancel}>Cancel</Button>}
            {canEdit && onDelete && <Button type="button" variant="ghost" loading={busy} onClick={onDelete}>Delete</Button>}
            {isReadOnly && <span className="inline-flex min-h-10 items-center px-2 text-sm text-slate-500">Read-only</span>}
        </div>
    );
}
