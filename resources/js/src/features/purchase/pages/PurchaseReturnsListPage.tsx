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
import { DataTable, SearchFilterToolbar, StatusBadge, TableToolbar, type DataTableColumn } from '../../../components/tables';
import { useTenant } from '../../auth/context/TenantContext';
import { useSuppliers } from '../../suppliers/hooks';
import { ConfirmWorkflowModal } from '../../shared/workflow';
import { formatCurrency, formatDate, parsePositiveInteger } from '../../shared/utils';
import { usePostPurchaseReturn, usePurchaseReturns } from '../hooks';
import type { PurchaseReturnRecord } from '../types';

export function PurchaseReturnsListPage() {
    const { tenantId } = useTenant();
    const [searchParams, setSearchParams] = useSearchParams();
    const [postTarget, setPostTarget] = useState<PurchaseReturnRecord | null>(null);
    const status = searchParams.get('status') ?? '';
    const supplierId = searchParams.get('supplierId') ?? '';
    const returnNumber = searchParams.get('returnNumber') ?? '';
    const page = parsePositiveInteger(searchParams.get('page'), 1);
    const returnsQuery = usePurchaseReturns({ tenant_id: tenantId, page, per_page: 10, status: status || undefined, supplier_id: supplierId ? Number(supplierId) : undefined, return_number: returnNumber || undefined, sort: '-updated_at' });
    const suppliersQuery = useSuppliers({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name' });
    const postMutation = usePostPurchaseReturn(postTarget?.id ?? 0);

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
            return next;
        });
    }

    async function handlePostConfirm() {
        await postMutation.mutateAsync();
        setPostTarget(null);
    }

    const columns: DataTableColumn<PurchaseReturnRecord>[] = [
        { key: 'return_number', header: 'Return', render: (purchaseReturn) => <Link className="font-medium text-stone-950 transition hover:text-stone-700" to={`/purchase/returns/${purchaseReturn.id}`}>{purchaseReturn.return_number}</Link> },
        { key: 'supplier_id', header: 'Supplier', render: (purchaseReturn) => <span className="text-sm text-stone-700">#{purchaseReturn.supplier_id}</span> },
        { key: 'return_date', header: 'Return Date', render: (purchaseReturn) => <span className="text-sm text-stone-700">{formatDate(purchaseReturn.return_date)}</span> },
        { key: 'status', header: 'Status', render: (purchaseReturn) => <StatusBadge>{purchaseReturn.status.replaceAll('_', ' ')}</StatusBadge> },
        { key: 'grand_total', header: 'Grand Total', render: (purchaseReturn) => <span className="text-sm text-stone-700">{formatCurrency(purchaseReturn.grand_total)}</span> },
        { key: 'actions', header: 'Actions', render: (purchaseReturn) => <div className="flex flex-wrap gap-2"><Link to={`/purchase/returns/${purchaseReturn.id}`}><Button className="h-9 px-3 text-xs" type="button" variant="secondary">View</Button></Link>{(purchaseReturn.status === 'draft' || purchaseReturn.status === 'approved') ? <Button className="h-9 px-3 text-xs" onClick={() => setPostTarget(purchaseReturn)} type="button" variant="secondary">Post</Button> : null}</div> },
    ];

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader actions={<Link to="/purchase/returns/new"><Button>Create Return</Button></Link>} breadcrumbs={[{ label: 'Purchase' }, { label: 'Purchase Returns' }]} description="Purchase returns now surface as return-to-vendor workflow records with posting controls where supported." title="Purchase Returns" />
            <ContentCard className="p-0">
                <TableToolbar description="Use the supported supplier, status, and return-number filters from the backend list request." title="Purchase return list">
                    <SearchFilterToolbar filters={<div className="flex flex-col gap-3 md:flex-row"><Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ status: event.target.value || undefined })} value={status}><option value="">All statuses</option><option value="draft">Draft</option><option value="approved">Approved</option><option value="shipped">Shipped</option><option value="closed">Closed</option><option value="cancelled">Cancelled</option></Select><Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ supplierId: event.target.value || undefined })} value={supplierId}><option value="">All suppliers</option>{suppliersQuery.data?.items.map((supplier) => <option key={supplier.id} value={supplier.id}>{supplier.name}</option>)}</Select></div>} search={<Input className="w-full md:max-w-sm" label={undefined} onChange={(event) => updateParams({ returnNumber: event.target.value || undefined })} placeholder="Filter return number" value={returnNumber} />} />
                </TableToolbar>
                {returnsQuery.isPending || suppliersQuery.isPending ? <LoadingState className="m-6" lines={8} /> : returnsQuery.isError || suppliersQuery.isError ? <ErrorState className="m-6" description={(returnsQuery.error ?? suppliersQuery.error)?.message ?? 'Unable to load purchase returns.'} title="Unable to load purchase returns" /> : <DataTable columns={columns} emptyState={<EmptyState className="m-6" description="No purchase returns match the current filters." title="No purchase returns found" />} getRowKey={(purchaseReturn) => purchaseReturn.id} rows={returnsQuery.data.items} />}
            </ContentCard>
            <ConfirmWorkflowModal confirmLabel="Post purchase return" description={postTarget ? `Post ${postTarget.return_number}?` : ''} isLoading={postMutation.isPending} onCancel={() => setPostTarget(null)} onConfirm={() => void handlePostConfirm()} open={Boolean(postTarget)} title="Post purchase return" />
        </div>
    );
}
