import { Link } from 'react-router-dom';
import { Button } from '@/shared/components/Button';
import type { PurchaseOrder, PurchaseOrderStatus } from '../purchaseApi';

const readOnlyStatuses: Array<PurchaseOrderStatus | undefined> = ['closed', 'cancelled', 'received', 'invoiced', 'partially_invoiced'];

export function PurchaseOrderActions({ order, busy, onApprove, onCancel, onClose, onDelete }: {
    order: PurchaseOrder;
    busy?: boolean;
    onApprove?: () => void;
    onCancel?: () => void;
    onClose?: () => void;
    onDelete?: () => void;
}) {
    const status = order.status;
    const canEdit = status === 'draft';
    const canApprove = status === 'draft';
    const canCancel = status === 'draft' || status === 'approved';
    const canClose = status === 'approved';
    const isReadOnly = readOnlyStatuses.includes(status);

    return (
        <div className="flex flex-wrap justify-end gap-2">
            {canEdit && <Link to={`/purchase/orders/${order.id}/edit`}><Button type="button" variant="secondary">Edit</Button></Link>}
            {canApprove && <Button type="button" loading={busy} onClick={onApprove}>Approve</Button>}
            {canClose && <Button type="button" variant="secondary" loading={busy} onClick={onClose}>Close</Button>}
            {canCancel && <Button type="button" variant="danger" loading={busy} onClick={onCancel}>Cancel</Button>}
            {canEdit && onDelete && <Button type="button" variant="ghost" loading={busy} onClick={onDelete}>Delete</Button>}
            {isReadOnly && <span className="inline-flex min-h-10 items-center px-2 text-sm text-slate-500">Read-only</span>}
        </div>
    );
}
