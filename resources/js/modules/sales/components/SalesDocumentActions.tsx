import { ActionMenu } from '@/shared/components/ActionMenu';
import { Button, LinkButton } from '@/shared/components/Button';
import type { SalesOrder } from '../salesTypes';
import { salesOrderCapabilities } from '../salesCapabilities';

export function SalesOrderActions({ order, busy, onSubmit, onApprove, onCancel, onClose, onDelete }: {
    order: SalesOrder;
    busy?: boolean;
    onSubmit?: () => void;
    onApprove?: () => void;
    onCancel?: () => void;
    onClose?: () => void;
    onDelete?: () => void;
}) {
    const capabilities = salesOrderCapabilities(order);

    return (
        <div className="flex flex-wrap justify-end gap-2">
            {capabilities.canEdit && <LinkButton to={`/sales/orders/${order.id}/edit`} variant="secondary">Edit</LinkButton>}
            {capabilities.canAllocate && <LinkButton to={`/sales/allocations/create?order_id=${order.id}`} variant="secondary">Allocate</LinkButton>}
            {capabilities.canDeliver && <LinkButton to={`/sales/deliveries/create?order_id=${order.id}`}>Deliver</LinkButton>}
            {capabilities.canInvoice && <LinkButton to={`/sales/invoices/create?order_id=${order.id}`} variant="secondary">Invoice</LinkButton>}
            {capabilities.canSubmit && onSubmit && <Button type="button" variant="secondary" loading={busy} onClick={onSubmit}>Submit</Button>}
            {capabilities.canApprove && onApprove && <Button type="button" loading={busy} onClick={onApprove}>Approve</Button>}
            {capabilities.canClose && onClose && <Button type="button" variant="secondary" loading={busy} onClick={onClose}>Close</Button>}
            {(capabilities.canCancel || capabilities.canDelete) && (
                <ActionMenu>
                    {capabilities.canCancel && <Button className="w-full justify-start text-rose-700" type="button" variant="ghost" loading={busy} onClick={onCancel}>Cancel order</Button>}
                    {capabilities.canDelete && onDelete && <Button className="w-full justify-start text-rose-700" type="button" variant="ghost" loading={busy} onClick={onDelete}>Delete draft</Button>}
                </ActionMenu>
            )}
        </div>
    );
}
