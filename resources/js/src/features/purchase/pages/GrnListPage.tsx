import { Link, useSearchParams } from 'react-router-dom';
import { useState } from 'react';
import { ContentCard } from '../../../components/ui/ContentCard';
import { PageHeader } from '../../../components/layout/PageHeader';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { Button } from '../../../components/ui/Button';
import { Select } from '../../../components/forms/Select';
import { DataTable, SearchFilterToolbar, StatusBadge, TableToolbar, type DataTableColumn } from '../../../components/tables';
import { useTenant } from '../../auth/context/TenantContext';
import { ConfirmWorkflowModal } from '../../shared/workflow';
import { formatDate, parsePositiveInteger } from '../../shared/utils';
import { useWarehouses } from '../../warehouse/hooks';
import { useSuppliers } from '../../suppliers/hooks';
import { useGrns, usePostGrn } from '../hooks';
import type { GrnRecord } from '../types';

export function GrnListPage() {
    const { tenantId } = useTenant();
    const [searchParams, setSearchParams] = useSearchParams();
    const [postTarget, setPostTarget] = useState<GrnRecord | null>(null);
    const status = searchParams.get('status') ?? '';
    const supplierId = searchParams.get('supplierId') ?? '';
    const warehouseId = searchParams.get('warehouseId') ?? '';
    const page = parsePositiveInteger(searchParams.get('page'), 1);
    const grnsQuery = useGrns({ tenant_id: tenantId, page, per_page: 10, status: status || undefined, supplier_id: supplierId ? Number(supplierId) : undefined, warehouse_id: warehouseId ? Number(warehouseId) : undefined, sort: '-updated_at' });
    const suppliersQuery = useSuppliers({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name' });
    const warehousesQuery = useWarehouses({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name:asc' });
    const postMutation = usePostGrn(postTarget?.id ?? 0);

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

    async function handlePostConfirm() {
        await postMutation.mutateAsync();
        setPostTarget(null);
    }

    const columns: DataTableColumn<GrnRecord>[] = [
        { key: 'grn_number', header: 'GRN', render: (grn) => <Link className="font-medium text-stone-950 transition hover:text-stone-700" to={`/purchase/grns/${grn.id}`}>{grn.grn_number}</Link> },
        { key: 'supplier_id', header: 'Supplier', render: (grn) => <span className="text-sm text-stone-700">#{grn.supplier_id}</span> },
        { key: 'warehouse_id', header: 'Warehouse', render: (grn) => <span className="text-sm text-stone-700">#{grn.warehouse_id}</span> },
        { key: 'received_date', header: 'Received Date', render: (grn) => <span className="text-sm text-stone-700">{formatDate(grn.received_date)}</span> },
        { key: 'status', header: 'Status', render: (grn) => <StatusBadge>{grn.status}</StatusBadge> },
        { key: 'actions', header: 'Actions', render: (grn) => <div className="flex flex-wrap gap-2"><Link to={`/purchase/grns/${grn.id}`}><Button className="h-9 px-3 text-xs" type="button" variant="secondary">View</Button></Link>{grn.status !== 'posted' ? <Link to={`/purchase/grns/${grn.id}/edit`}><Button className="h-9 px-3 text-xs" type="button" variant="secondary">Edit</Button></Link> : null}{grn.status !== 'posted' ? <Button className="h-9 px-3 text-xs" onClick={() => setPostTarget(grn)} type="button" variant="secondary">Post</Button> : null}</div> },
    ];

    const lookupError = suppliersQuery.error ?? warehousesQuery.error;

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader actions={<Link to="/purchase/grns/new"><Button>Add GRN</Button></Link>} breadcrumbs={[{ label: 'Purchase' }, { label: 'GRNs' }]} description="Goods received notes are now available as postable operational documents with supplier and warehouse context." title="GRNs" />
            <ContentCard className="p-0">
                <TableToolbar description="Filter GRNs by supplier, warehouse, and status using the current backend list request." title="Goods received notes">
                    <SearchFilterToolbar filters={<div className="flex flex-col gap-3 md:flex-row"><Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ status: event.target.value || undefined })} value={status}><option value="">All statuses</option><option value="draft">Draft</option><option value="partial">Partial</option><option value="complete">Complete</option><option value="posted">Posted</option></Select><Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ supplierId: event.target.value || undefined })} value={supplierId}><option value="">All suppliers</option>{suppliersQuery.data?.items.map((supplier) => <option key={supplier.id} value={supplier.id}>{supplier.name}</option>)}</Select><Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ warehouseId: event.target.value || undefined })} value={warehouseId}><option value="">All warehouses</option>{warehousesQuery.data?.items.map((warehouse) => <option key={warehouse.id} value={warehouse.id}>{warehouse.name}</option>)}</Select></div>} />
                </TableToolbar>
                {grnsQuery.isPending || suppliersQuery.isPending || warehousesQuery.isPending ? <LoadingState className="m-6" lines={8} /> : grnsQuery.isError || lookupError ? <ErrorState className="m-6" description={(grnsQuery.error ?? lookupError)?.message ?? 'Unable to load GRNs.'} title="Unable to load GRNs" /> : <DataTable columns={columns} emptyState={<EmptyState className="m-6" description="No GRNs match the current filters." title="No GRNs found" />} getRowKey={(grn) => grn.id} rows={grnsQuery.data.items} />}
            </ContentCard>
            <ConfirmWorkflowModal confirmLabel="Post GRN" description={postTarget ? `Post ${postTarget.grn_number}?` : ''} isLoading={postMutation.isPending} onCancel={() => setPostTarget(null)} onConfirm={() => void handlePostConfirm()} open={Boolean(postTarget)} title="Post GRN" />
        </div>
    );
}
