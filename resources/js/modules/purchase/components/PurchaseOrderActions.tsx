import { ActionMenu } from '@/shared/components/ActionMenu';
import { Button, LinkButton } from '@/shared/components/Button';
import type { PurchaseOrder } from '../purchaseApi';
import { purchaseOrderCapabilities } from '../purchaseCapabilities';

export function PurchaseOrderActions({ order, busy, downloadingPdf, canUpdate = true, onDownloadPdf, onSubmit, onApprove, onCancel, onClose, onDelete }: {
    order: PurchaseOrder;
    busy?: boolean;
    downloadingPdf?: boolean;
    canUpdate?: boolean;
    onDownloadPdf?: () => void;
    onSubmit?: () => void;
    onApprove?: () => void;
    onCancel?: () => void;
    onClose?: () => void;
    onDelete?: () => void;
}) {
    const { canEdit, canSubmit, canApprove, canCancel, canClose, canDelete, isReadOnly } = purchaseOrderCapabilities(order);
    const showCancel = canCancel && onCancel !== undefined;
    const showDelete = canDelete && onDelete !== undefined;

    return (
        <div className="flex flex-wrap justify-end gap-2">
            {onDownloadPdf && <Button type="button" variant="secondary" loading={downloadingPdf} loadingLabel="Preparing PDF..." onClick={onDownloadPdf}>Download PDF</Button>}
            {canEdit && canUpdate && <LinkButton to={`/purchase/orders/${order.id}/edit`} variant="secondary">Edit</LinkButton>}
            {canSubmit && onSubmit && <Button type="button" variant="secondary" loading={busy} onClick={onSubmit}>Submit</Button>}
            {canApprove && onApprove && <Button type="button" loading={busy} onClick={onApprove}>Approve</Button>}
            {canClose && onClose && <Button type="button" variant="secondary" loading={busy} onClick={onClose}>Close</Button>}
            {(showCancel || showDelete) && (
                <ActionMenu>
                    {showCancel && <Button className="w-full justify-start text-rose-700" type="button" variant="ghost" loading={busy} onClick={onCancel}>Cancel order</Button>}
                    {showDelete && <Button className="w-full justify-start text-rose-700" type="button" variant="ghost" loading={busy} onClick={onDelete}>Delete draft</Button>}
                </ActionMenu>
            )}
            {isReadOnly && <span className="inline-flex min-h-10 items-center px-2 text-sm text-slate-500">Read-only</span>}
        </div>
    );
}
