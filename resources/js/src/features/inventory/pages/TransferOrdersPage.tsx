import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Link } from 'react-router-dom';
import { Button } from '../../../components/ui/Button';
import { ContentCard } from '../../../components/ui/ContentCard';
import { PageHeader } from '../../../components/layout/PageHeader';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { DataTable, StatusBadge, type DataTableColumn } from '../../../components/tables';
import { ValidationError } from '../../../api/client';
import { useToast } from '../../../app/providers/ToastProvider';
import { applyValidationErrors } from '../../../lib/applyValidationErrors';
import { useTenant } from '../../auth/context/TenantContext';
import { useProducts, useUnitsOfMeasure } from '../../products/hooks';
import { useWarehouses } from '../../warehouse/hooks';
import { ConfirmWorkflowModal } from '../../shared/workflow';
import { formatDate, formatQuantity } from '../../shared/utils';
import { TransferOrderForm } from '../components/TransferOrderForm';
import { useApproveTransferOrder, useCreateTransferOrder, useReceiveTransferOrder, useTransferOrders } from '../hooks';
import { transferOrderFormSchema, type TransferOrderFormInput, type TransferOrderFormValues } from '../schemas';
import type { TransferOrderRecord } from '../types';

type WorkflowTarget =
    | { type: 'approve'; order: TransferOrderRecord }
    | { type: 'receive'; order: TransferOrderRecord }
    | null;

function buildReceivePayload(order: TransferOrderRecord, tenantId: number) {
    return {
        tenant_id: tenantId,
        lines: order.lines.map((line) => {
            const remaining = Math.max(Number(line.requested_qty) - Number(line.received_qty ?? 0), 0);
            return {
                line_id: line.id,
                received_qty: remaining > 0 ? remaining : Number(line.requested_qty),
            };
        }),
    };
}

export function TransferOrdersPage() {
    const { showToast } = useToast();
    const { tenantId } = useTenant();
    const [formError, setFormError] = useState<string | null>(null);
    const [workflowTarget, setWorkflowTarget] = useState<WorkflowTarget>(null);
    const transferOrdersQuery = useTransferOrders({ tenant_id: tenantId, page: 1, per_page: 100 });
    const warehousesQuery = useWarehouses({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name:asc' });
    const productsQuery = useProducts({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name' });
    const unitsQuery = useUnitsOfMeasure({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name' });
    const createMutation = useCreateTransferOrder();
    const selectedWorkflowOrderId = workflowTarget?.order.id ?? 0;
    const approveMutation = useApproveTransferOrder(selectedWorkflowOrderId, tenantId);
    const receiveMutation = useReceiveTransferOrder(selectedWorkflowOrderId, tenantId);
    const form = useForm<TransferOrderFormInput, unknown, TransferOrderFormValues>({
        resolver: zodResolver(transferOrderFormSchema),
        defaultValues: {
            from_warehouse_id: '',
            to_warehouse_id: '',
            transfer_number: '',
            status: 'draft',
            request_date: new Date().toISOString().slice(0, 10),
            expected_date: '',
            notes: '',
            lines: [{ product_id: 0, uom_id: 0, requested_qty: 1, unit_cost: '', from_location_id: '', to_location_id: '' }],
        },
    });

    async function onSubmit(values: TransferOrderFormValues) {
        setFormError(null);

        try {
            await createMutation.mutateAsync({
                tenant_id: tenantId,
                from_warehouse_id: values.from_warehouse_id,
                to_warehouse_id: values.to_warehouse_id,
                transfer_number: values.transfer_number,
                status: values.status,
                request_date: values.request_date,
                expected_date: values.expected_date ?? null,
                notes: values.notes ?? null,
                lines: values.lines.map((line) => ({
                    product_id: line.product_id,
                    uom_id: line.uom_id,
                    requested_qty: line.requested_qty,
                    unit_cost: line.unit_cost ?? null,
                    from_location_id: line.from_location_id ?? null,
                    to_location_id: line.to_location_id ?? null,
                })),
            });

            form.reset({
                from_warehouse_id: '',
                to_warehouse_id: '',
                transfer_number: '',
                status: 'draft',
                request_date: new Date().toISOString().slice(0, 10),
                expected_date: '',
                notes: '',
                lines: [{ product_id: 0, uom_id: 0, requested_qty: 1, unit_cost: '', from_location_id: '', to_location_id: '' }],
            });
            showToast({ title: 'Transfer order created', description: 'The new transfer order is ready for approval and receipt workflows.', tone: 'success' });
        } catch (error) {
            if (error instanceof ValidationError) {
                applyValidationErrors(error.errors, form.setError, { onUnhandled: (message) => setFormError(message) });
                return;
            }

            setFormError(error instanceof Error ? error.message : 'Unable to create transfer order.');
        }
    }

    async function handleWorkflowConfirm() {
        if (!workflowTarget) {
            return;
        }

        if (workflowTarget.type === 'approve') {
            await approveMutation.mutateAsync();
            showToast({ title: 'Transfer order approved', description: `${workflowTarget.order.transfer_number} moved to the next workflow state.`, tone: 'success' });
        } else {
            await receiveMutation.mutateAsync(buildReceivePayload(workflowTarget.order, tenantId));
            showToast({ title: 'Transfer order received', description: `${workflowTarget.order.transfer_number} was received using the remaining requested quantities.`, tone: 'success' });
        }

        setWorkflowTarget(null);
    }

    const columns: DataTableColumn<TransferOrderRecord>[] = [
        {
            key: 'transfer_number',
            header: 'Transfer Order',
            render: (order) => (
                <div>
                    <Link className="font-medium text-stone-950 transition hover:text-stone-700" to={`/inventory/transfer-orders/${order.id}`}>
                        {order.transfer_number}
                    </Link>
                    <p className="mt-1 text-xs text-stone-500">{formatDate(order.request_date)}</p>
                </div>
            ),
        },
        {
            key: 'route',
            header: 'Route',
            render: (order) => <span className="text-sm text-stone-700">{`#${order.from_warehouse_id} -> #${order.to_warehouse_id}`}</span>,
        },
        { key: 'status', header: 'Status', render: (order) => <StatusBadge>{order.status.replaceAll('_', ' ')}</StatusBadge> },
        {
            key: 'lines',
            header: 'Lines',
            render: (order) => <span className="text-sm text-stone-700">{order.lines.length} lines / {formatQuantity(order.lines.reduce((sum, line) => sum + Number(line.requested_qty), 0))} qty</span>,
        },
        {
            key: 'actions',
            header: 'Actions',
            className: 'w-[18rem]',
            render: (order) => (
                <div className="flex flex-wrap gap-2">
                    <Link to={`/inventory/transfer-orders/${order.id}`}>
                        <Button className="h-9 px-3 text-xs" type="button" variant="secondary">View</Button>
                    </Link>
                    {order.status === 'draft' ? <Button className="h-9 px-3 text-xs" onClick={() => setWorkflowTarget({ type: 'approve', order })} type="button" variant="secondary">Approve</Button> : null}
                    {(order.status === 'approved' || order.status === 'in_transit') ? <Button className="h-9 px-3 text-xs" onClick={() => setWorkflowTarget({ type: 'receive', order })} type="button" variant="secondary">Receive</Button> : null}
                </div>
            ),
        },
    ];

    const lookupError = transferOrdersQuery.error ?? warehousesQuery.error ?? productsQuery.error ?? unitsQuery.error;

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader breadcrumbs={[{ label: 'Inventory' }, { label: 'Transfer Orders' }]} description="Transfer orders use the confirmed backend workflow endpoints for create, approve, receive, and detail review." title="Transfer Orders" />

            <div className="grid gap-4 xl:grid-cols-[1.15fr_0.85fr]">
                <ContentCard className="p-0">
                    {transferOrdersQuery.isPending ? (
                        <LoadingState className="m-6" lines={8} />
                    ) : transferOrdersQuery.isError ? (
                        <ErrorState className="m-6" description={transferOrdersQuery.error.message} title="Unable to load transfer orders" />
                    ) : (
                        <DataTable columns={columns} emptyState={<EmptyState className="m-6" description="No transfer orders are available yet." title="No transfer orders found" />} getRowKey={(order) => order.id} rows={transferOrdersQuery.data.items} />
                    )}
                </ContentCard>

                <ContentCard>
                    {warehousesQuery.isPending || productsQuery.isPending || unitsQuery.isPending ? (
                        <LoadingState lines={8} />
                    ) : lookupError ? (
                        <ErrorState description={lookupError.message} title="Unable to load transfer order setup" />
                    ) : (
                        <TransferOrderForm form={form} formError={formError} isSubmitting={createMutation.isPending} onSubmit={onSubmit} products={productsQuery.data?.items ?? []} units={unitsQuery.data?.items ?? []} warehouses={warehousesQuery.data?.items ?? []} />
                    )}
                </ContentCard>
            </div>

            <ConfirmWorkflowModal
                confirmLabel={workflowTarget?.type === 'approve' ? 'Approve transfer order' : 'Receive transfer order'}
                description={workflowTarget ? `${workflowTarget.type === 'approve' ? 'Approve' : 'Receive'} ${workflowTarget.order.transfer_number}?` : ''}
                isLoading={approveMutation.isPending || receiveMutation.isPending}
                onCancel={() => setWorkflowTarget(null)}
                onConfirm={() => void handleWorkflowConfirm()}
                open={Boolean(workflowTarget)}
                title={workflowTarget?.type === 'approve' ? 'Approve transfer order' : 'Receive transfer order'}
            />
        </div>
    );
}
