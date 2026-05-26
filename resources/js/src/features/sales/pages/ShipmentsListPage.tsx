import { useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { Button } from '../../../components/ui/Button';
import { ContentCard } from '../../../components/ui/ContentCard';
import { PageHeader } from '../../../components/layout/PageHeader';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { Input } from '../../../components/forms/Input';
import { Select } from '../../../components/forms/Select';
import { DataTable, SearchFilterToolbar, StatusBadge, TablePagination, TableToolbar, type DataTableColumn } from '../../../components/tables';
import { useTenant } from '../../auth/context/TenantContext';
import { useCustomers } from '../../customers/hooks';
import { ConfirmWorkflowModal } from '../../shared/workflow';
import { formatDate, parsePositiveInteger } from '../../shared/utils';
import { useProcessShipment, useShipments } from '../hooks';
import type { ShipmentRecord } from '../types';

export function ShipmentsListPage() {
    const { tenantId } = useTenant();
    const [searchParams, setSearchParams] = useSearchParams();
    const [processTarget, setProcessTarget] = useState<ShipmentRecord | null>(null);
    const page = parsePositiveInteger(searchParams.get('page'), 1);
    const status = searchParams.get('status') ?? '';
    const customerId = searchParams.get('customerId') ?? '';
    const salesOrderId = searchParams.get('salesOrderId') ?? '';
    const shipmentsQuery = useShipments({
        tenant_id: tenantId,
        page,
        per_page: 10,
        status: status || undefined,
        customer_id: customerId ? Number(customerId) : undefined,
        sales_order_id: salesOrderId ? Number(salesOrderId) : undefined,
        sort: '-updated_at',
    });
    const customersQuery = useCustomers({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name' });
    const processMutation = useProcessShipment(processTarget?.id ?? 0);

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

            if ('status' in updates || 'customerId' in updates || 'salesOrderId' in updates) {
                next.set('page', '1');
            }

            return next;
        });
    }

    async function handleProcessShipment() {
        await processMutation.mutateAsync();
        setProcessTarget(null);
    }

    const columns: DataTableColumn<ShipmentRecord>[] = [
        {
            key: 'shipment_number',
            header: 'Shipment',
            render: (shipment) => (
                <div>
                    <Link className="font-medium text-stone-950 transition hover:text-stone-700" to={`/sales/shipments/${shipment.id}`}>
                        {shipment.shipment_number}
                    </Link>
                    <p className="mt-1 text-xs text-stone-500">{formatDate(shipment.shipped_date)}</p>
                </div>
            ),
        },
        { key: 'customer_id', header: 'Customer', render: (shipment) => <span className="text-sm text-stone-700">#{shipment.customer_id}</span> },
        { key: 'sales_order_id', header: 'Sales Order', render: (shipment) => <span className="text-sm text-stone-700">{shipment.sales_order_id ? `#${shipment.sales_order_id}` : '-'}</span> },
        { key: 'status', header: 'Status', render: (shipment) => <StatusBadge>{shipment.status.replaceAll('_', ' ')}</StatusBadge> },
        { key: 'carrier', header: 'Carrier', render: (shipment) => <span className="text-sm text-stone-700">{shipment.carrier || '-'}</span> },
        {
            key: 'actions',
            header: 'Actions',
            className: 'w-[14rem]',
            render: (shipment) => (
                <div className="flex flex-wrap gap-2">
                    <Link to={`/sales/shipments/${shipment.id}`}>
                        <Button className="h-9 px-3 text-xs" type="button" variant="secondary">View</Button>
                    </Link>
                    {(shipment.status === 'draft' || shipment.status === 'picking' || shipment.status === 'packed') ? (
                        <Button className="h-9 px-3 text-xs" onClick={() => setProcessTarget(shipment)} type="button" variant="secondary">
                            Process
                        </Button>
                    ) : null}
                </div>
            ),
        },
    ];

    const lookupError = customersQuery.error;

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader breadcrumbs={[{ label: 'Sales' }, { label: 'Shipments' }]} description="Shipments are presented in the same dense ERP workflow grid, with customer, order, carrier, and process action visibility." title="Shipments" />

            <ContentCard className="p-0">
                <TableToolbar description="Filter shipments using only the supported customer, sales order, and status parameters from the backend request contract." title="Shipment list">
                    <SearchFilterToolbar
                        filters={
                            <div className="flex flex-col gap-3 md:flex-row">
                                <Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ status: event.target.value || undefined })} value={status}>
                                    <option value="">All statuses</option>
                                    <option value="draft">Draft</option>
                                    <option value="picking">Picking</option>
                                    <option value="packed">Packed</option>
                                    <option value="shipped">Shipped</option>
                                    <option value="delivered">Delivered</option>
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
                                <Input className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ salesOrderId: event.target.value || undefined })} placeholder="Sales order ID" value={salesOrderId} />
                            </div>
                        }
                    />
                </TableToolbar>

                {shipmentsQuery.isPending || customersQuery.isPending ? (
                    <LoadingState className="m-6" lines={8} />
                ) : shipmentsQuery.isError || lookupError ? (
                    <ErrorState className="m-6" description={(shipmentsQuery.error ?? lookupError)?.message ?? 'Unable to load shipments.'} title="Unable to load shipments" />
                ) : (
                    <DataTable
                        columns={columns}
                        emptyState={<EmptyState className="m-6" description="No shipments match the current filters." title="No shipments found" />}
                        footer={<TablePagination meta={shipmentsQuery.data.meta} onPageChange={(nextPage) => updateParams({ page: nextPage })} />}
                        getRowKey={(shipment) => shipment.id}
                        rows={shipmentsQuery.data.items}
                    />
                )}
            </ContentCard>

            <ConfirmWorkflowModal confirmLabel="Process shipment" description={processTarget ? `Process ${processTarget.shipment_number}?` : ''} isLoading={processMutation.isPending} onCancel={() => setProcessTarget(null)} onConfirm={() => void handleProcessShipment()} open={Boolean(processTarget)} title="Process shipment" />
        </div>
    );
}
