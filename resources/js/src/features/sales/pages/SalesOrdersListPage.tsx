import { useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { Button } from '../../../components/ui/Button';
import { ContentCard } from '../../../components/ui/ContentCard';
import { PageHeader } from '../../../components/layout/PageHeader';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { Select } from '../../../components/forms/Select';
import { DataTable, SearchFilterToolbar, StatusBadge, TablePagination, TableToolbar, type DataTableColumn } from '../../../components/tables';
import { useTenant } from '../../auth/context/TenantContext';
import { useCustomers } from '../../customers/hooks';
import { ConfirmWorkflowModal } from '../../shared/workflow';
import { formatCurrency, formatDate, parsePositiveInteger } from '../../shared/utils';
import { useCancelSalesOrder, useConfirmSalesOrder, useSalesOrders } from '../hooks';
import type { SalesOrderRecord } from '../types';

export function SalesOrdersListPage() {
    const { tenantId } = useTenant();
    const [searchParams, setSearchParams] = useSearchParams();
    const [actionTarget, setActionTarget] = useState<{ record: SalesOrderRecord; action: 'confirm' | 'cancel' } | null>(null);
    const page = parsePositiveInteger(searchParams.get('page'), 1);
    const status = searchParams.get('status') ?? '';
    const customerId = searchParams.get('customerId') ?? '';
    const salesOrdersQuery = useSalesOrders({
        tenant_id: tenantId,
        page,
        per_page: 10,
        status: status || undefined,
        customer_id: customerId ? Number(customerId) : undefined,
        sort: '-updated_at',
    });
    const customersQuery = useCustomers({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name' });
    const confirmMutation = useConfirmSalesOrder(actionTarget?.record.id ?? 0);
    const cancelMutation = useCancelSalesOrder(actionTarget?.record.id ?? 0);

    function updateParams(updates: Record<string, string | number | undefined>) {
        setSearchParams((current) => {
            const next = new URLSearchParams(current);

            for (const [key, value] of Object.entries(updates)) {
                if (value === undefined || value === '') {
                    next.delete(key);
                } else {
                    next.set(key, String(value));
                }
            }

            if ('status' in updates || 'customerId' in updates) {
                next.set('page', '1');
            }

            return next;
        });
    }

    async function handleConfirmWorkflow() {
        if (!actionTarget) {
            return;
        }

        if (actionTarget.action === 'confirm') {
            await confirmMutation.mutateAsync();
        } else {
            await cancelMutation.mutateAsync();
        }

        setActionTarget(null);
    }

    const columns: DataTableColumn<SalesOrderRecord>[] = [
        {
            key: 'so_number',
            header: 'Sales Order',
            render: (order) => (
                <div>
                    <Link className="font-medium text-stone-950 transition hover:text-stone-700" to={`/sales/orders/${order.id}`}>
                        {order.so_number}
                    </Link>
                    <p className="mt-1 text-xs text-stone-500">{formatDate(order.order_date)}</p>
                </div>
            ),
        },
        { key: 'customer_id', header: 'Customer', render: (order) => <span className="text-sm text-stone-700">#{order.customer_id}</span> },
        { key: 'warehouse_id', header: 'Warehouse', render: (order) => <span className="text-sm text-stone-700">#{order.warehouse_id}</span> },
        { key: 'status', header: 'Status', render: (order) => <StatusBadge>{order.status.replaceAll('_', ' ')}</StatusBadge> },
        { key: 'grand_total', header: 'Grand Total', render: (order) => <span className="text-sm text-stone-700">{formatCurrency(order.grand_total)}</span> },
        {
            key: 'actions',
            header: 'Actions',
            className: 'w-[16rem]',
            render: (order) => (
                <div className="flex flex-wrap gap-2">
                    <Link to={`/sales/orders/${order.id}`}>
                        <Button className="h-9 px-3 text-xs" type="button" variant="secondary">View</Button>
                    </Link>
                    {order.status === 'draft' ? (
                        <Button className="h-9 px-3 text-xs" onClick={() => setActionTarget({ record: order, action: 'confirm' })} type="button" variant="secondary">
                            Confirm
                        </Button>
                    ) : null}
                    {(order.status === 'draft' || order.status === 'confirmed' || order.status === 'partial') ? (
                        <Button className="h-9 px-3 text-xs" onClick={() => setActionTarget({ record: order, action: 'cancel' })} type="button" variant="secondary">
                            Cancel
                        </Button>
                    ) : null}
                </div>
            ),
        },
    ];

    const lookupError = customersQuery.error;

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader actions={<Link to="/sales/orders/new"><Button>Add Sales Order</Button></Link>} breadcrumbs={[{ label: 'Sales' }, { label: 'Sales Orders' }]} description="Sales orders are organized into the same dense workflow table pattern, with backend-safe filters and status actions." title="Sales Orders" />

            <ContentCard className="p-0">
                <TableToolbar description="Filter sales orders using the supported customer and status parameters from the backend list request." title="Sales order list">
                    <SearchFilterToolbar
                        filters={
                            <div className="flex flex-col gap-3 md:flex-row">
                                <Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ status: event.target.value || undefined })} value={status}>
                                    <option value="">All statuses</option>
                                    <option value="draft">Draft</option>
                                    <option value="confirmed">Confirmed</option>
                                    <option value="partial">Partial</option>
                                    <option value="shipped">Shipped</option>
                                    <option value="invoiced">Invoiced</option>
                                    <option value="closed">Closed</option>
                                    <option value="cancelled">Cancelled</option>
                                </Select>
                                <Select className="w-full md:max-w-[14rem]" onChange={(event) => updateParams({ customerId: event.target.value || undefined })} value={customerId}>
                                    <option value="">All customers</option>
                                    {customersQuery.data?.items.map((customer) => (
                                        <option key={customer.id} value={customer.id}>
                                            {customer.name}
                                        </option>
                                    ))}
                                </Select>
                            </div>
                        }
                    />
                </TableToolbar>

                {salesOrdersQuery.isPending || customersQuery.isPending ? (
                    <LoadingState className="m-6" lines={8} />
                ) : salesOrdersQuery.isError || lookupError ? (
                    <ErrorState className="m-6" description={(salesOrdersQuery.error ?? lookupError)?.message ?? 'Unable to load sales orders.'} title="Unable to load sales orders" />
                ) : (
                    <DataTable
                        columns={columns}
                        emptyState={<EmptyState className="m-6" description="No sales orders match the current filters." title="No sales orders found" />}
                        footer={<TablePagination meta={salesOrdersQuery.data.meta} onPageChange={(nextPage) => updateParams({ page: nextPage })} />}
                        getRowKey={(order) => order.id}
                        rows={salesOrdersQuery.data.items}
                    />
                )}
            </ContentCard>

            <ConfirmWorkflowModal
                confirmLabel={actionTarget?.action === 'confirm' ? 'Confirm sales order' : 'Cancel sales order'}
                description={actionTarget ? `${actionTarget.action === 'confirm' ? 'Confirm' : 'Cancel'} ${actionTarget.record.so_number}?` : ''}
                isLoading={confirmMutation.isPending || cancelMutation.isPending}
                onCancel={() => setActionTarget(null)}
                onConfirm={() => void handleConfirmWorkflow()}
                open={Boolean(actionTarget)}
                title={actionTarget?.action === 'confirm' ? 'Confirm sales order' : 'Cancel sales order'}
            />
        </div>
    );
}
