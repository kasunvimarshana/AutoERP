import { Link, useSearchParams } from 'react-router-dom';
import { useState } from 'react';
import { Button } from '../../../components/ui/Button';
import { ContentCard } from '../../../components/ui/ContentCard';
import { PageHeader } from '../../../components/layout/PageHeader';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { Select } from '../../../components/forms/Select';
import { DataTable, SearchFilterToolbar, StatusBadge, TablePagination, TableToolbar, type DataTableColumn } from '../../../components/tables';
import { useTenant } from '../../auth/context/TenantContext';
import { ConfirmWorkflowModal } from '../../shared/workflow';
import { formatCurrency, formatDate, parsePositiveInteger } from '../../shared/utils';
import { useWarehouses } from '../../warehouse/hooks';
import { useSuppliers } from '../../suppliers/hooks';
import { useConfirmPurchaseOrder, usePurchaseOrders } from '../hooks';
import type { PurchaseOrderRecord } from '../types';

export function PurchaseOrdersListPage() {
    const { tenantId } = useTenant();
    const [searchParams, setSearchParams] = useSearchParams();
    const [confirmTarget, setConfirmTarget] = useState<PurchaseOrderRecord | null>(null);
    const page = parsePositiveInteger(searchParams.get('page'), 1);
    const status = searchParams.get('status') ?? '';
    const supplierId = searchParams.get('supplierId') ?? '';
    const warehouseId = searchParams.get('warehouseId') ?? '';
    const purchaseOrdersQuery = usePurchaseOrders({
        tenant_id: tenantId,
        page,
        per_page: 10,
        status: status || undefined,
        supplier_id: supplierId ? Number(supplierId) : undefined,
        warehouse_id: warehouseId ? Number(warehouseId) : undefined,
        sort: '-updated_at',
    });
    const suppliersQuery = useSuppliers({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name' });
    const warehousesQuery = useWarehouses({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name:asc' });
    const confirmMutation = useConfirmPurchaseOrder(confirmTarget?.id ?? 0);

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

            if ('status' in updates || 'supplierId' in updates || 'warehouseId' in updates) {
                next.set('page', '1');
            }

            return next;
        });
    }

    async function handleConfirmWorkflow() {
        await confirmMutation.mutateAsync();
        setConfirmTarget(null);
    }

    const columns: DataTableColumn<PurchaseOrderRecord>[] = [
        {
            key: 'po_number',
            header: 'Purchase Order',
            render: (order) => (
                <div>
                    <Link className="font-medium text-stone-950 transition hover:text-stone-700" to={`/purchase/orders/${order.id}`}>
                        {order.po_number}
                    </Link>
                    <p className="mt-1 text-xs text-stone-500">{formatDate(order.order_date)}</p>
                </div>
            ),
        },
        { key: 'supplier_id', header: 'Supplier', render: (order) => <span className="text-sm text-stone-700">#{order.supplier_id}</span> },
        { key: 'warehouse_id', header: 'Warehouse', render: (order) => <span className="text-sm text-stone-700">#{order.warehouse_id}</span> },
        { key: 'status', header: 'Status', render: (order) => <StatusBadge>{order.status.replaceAll('_', ' ')}</StatusBadge> },
        { key: 'grand_total', header: 'Grand Total', render: (order) => <span className="text-sm text-stone-700">{formatCurrency(order.grand_total)}</span> },
        {
            key: 'actions',
            header: 'Actions',
            className: 'w-[14rem]',
            render: (order) => (
                <div className="flex flex-wrap gap-2">
                    <Link to={`/purchase/orders/${order.id}`}><Button className="h-9 px-3 text-xs" type="button" variant="secondary">View</Button></Link>
                    <Link to={`/purchase/orders/${order.id}/edit`}><Button className="h-9 px-3 text-xs" type="button" variant="secondary">Edit</Button></Link>
                    <Link to={`/purchase/grns/new?purchaseOrderId=${order.id}`}><Button className="h-9 px-3 text-xs" type="button" variant="secondary">GRN</Button></Link>
                    {order.status === 'draft' ? <Button className="h-9 px-3 text-xs" onClick={() => setConfirmTarget(order)} type="button" variant="secondary">Confirm</Button> : null}
                </div>
            ),
        },
    ];

    const lookupError = suppliersQuery.error ?? warehousesQuery.error;

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader actions={<Link to="/purchase/orders/new"><Button>Add Purchase Order</Button></Link>} breadcrumbs={[{ label: 'Purchase' }, { label: 'Purchase Orders' }]} description="Purchase orders are now available as workflow-aware operational records with supplier, warehouse, status, and totals visibility." title="Purchase Orders" />

            <ContentCard className="p-0">
                <TableToolbar description="Filter purchase orders with the supported supplier, warehouse, and status parameters from the backend list request." title="Purchase order list">
                    <SearchFilterToolbar
                        filters={
                            <div className="flex flex-col gap-3 md:flex-row">
                                <Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ status: event.target.value || undefined })} value={status}>
                                    <option value="">All statuses</option>
                                    <option value="draft">Draft</option>
                                    <option value="confirmed">Confirmed</option>
                                    <option value="partially_received">Partially Received</option>
                                    <option value="received">Received</option>
                                    <option value="cancelled">Cancelled</option>
                                </Select>
                                <Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ supplierId: event.target.value || undefined })} value={supplierId}>
                                    <option value="">All suppliers</option>
                                    {suppliersQuery.data?.items.map((supplier) => <option key={supplier.id} value={supplier.id}>{supplier.name}</option>)}
                                </Select>
                                <Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ warehouseId: event.target.value || undefined })} value={warehouseId}>
                                    <option value="">All warehouses</option>
                                    {warehousesQuery.data?.items.map((warehouse) => <option key={warehouse.id} value={warehouse.id}>{warehouse.name}</option>)}
                                </Select>
                            </div>
                        }
                    />
                </TableToolbar>

                {purchaseOrdersQuery.isPending || suppliersQuery.isPending || warehousesQuery.isPending ? <LoadingState className="m-6" lines={8} /> : purchaseOrdersQuery.isError || lookupError ? <ErrorState className="m-6" description={(purchaseOrdersQuery.error ?? lookupError)?.message ?? 'Unable to load purchase orders.'} title="Unable to load purchase orders" /> : <DataTable columns={columns} emptyState={<EmptyState className="m-6" description="No purchase orders match the current filters." title="No purchase orders found" />} footer={<TablePagination meta={purchaseOrdersQuery.data.meta} onPageChange={(nextPage) => updateParams({ page: nextPage })} />} getRowKey={(order) => order.id} rows={purchaseOrdersQuery.data.items} />}
            </ContentCard>

            <ConfirmWorkflowModal confirmLabel="Confirm purchase order" description={confirmTarget ? `Confirm ${confirmTarget.po_number}?` : ''} isLoading={confirmMutation.isPending} onCancel={() => setConfirmTarget(null)} onConfirm={() => void handleConfirmWorkflow()} open={Boolean(confirmTarget)} title="Confirm purchase order" />
        </div>
    );
}
