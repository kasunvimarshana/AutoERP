import { useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { approvePurchaseOrder, cancelPurchaseOrder, closePurchaseOrder, listPurchaseOrders, submitPurchaseOrder, type PurchaseOrder } from '../purchaseApi';
import { useAuth } from '@/modules/auth/AuthProvider';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Button, LinkButton } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { Pagination } from '@/shared/components/Pagination';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { formatDate } from '@/shared/utils/formatDate';
import { readableRelation } from '@/shared/utils/object';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import type { NamedResource } from '@/shared/types/common';
import { SupplierLookupSelect } from '../components/PurchaseLookups';
import { PurchaseOrderStatusBadge } from '../components/PurchaseOrderStatusBadge';
import { purchaseOrderCapabilities } from '../purchaseCapabilities';
import { hasPurchasePermission, purchasePermissions } from '../purchasePermissions';

const statuses = [
    'draft',
    'pending_approval',
    'approved',
    'closed',
    'cancelled',
].map((value) => ({ value, label: value.replaceAll('_', ' ') }));

export default function PurchaseOrderListPage() {
    const auth = useAuth();
    const [searchParams] = useSearchParams();
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState(searchParams.get('status') ?? '');
    const [supplier, setSupplier] = useState<NamedResource | null>(null);
    const [dateFrom, setDateFrom] = useState('');
    const [dateTo, setDateTo] = useState('');
    const [page, setPage] = useState(1);
    const [busyId, setBusyId] = useState<number | null>(null);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const debounced = useDebounce(search);
    const result = useApi((signal) => listPurchaseOrders({
        search: debounced || undefined,
        status: status || undefined,
        supplier_id: supplier?.id,
        date_from: dateFrom || undefined,
        date_to: dateTo || undefined,
        page,
        per_page: 25,
    }, signal), [debounced, status, supplier?.id, dateFrom, dateTo, page]);

    const canCreate = hasPurchasePermission(auth.permissions, purchasePermissions.ordersCreate);
    const runAction = async (order: PurchaseOrder, action: 'submit' | 'approve' | 'cancel' | 'close') => {
        if (!window.confirm(`Confirm ${action} for ${order.purchase_order_number ?? 'this purchase order'}?`)) return;
        setBusyId(order.id);
        setActionError(null);
        try {
            if (action === 'submit') await submitPurchaseOrder(order.id);
            if (action === 'approve') await approvePurchaseOrder(order.id);
            if (action === 'cancel') await cancelPurchaseOrder(order.id);
            if (action === 'close') await closePurchaseOrder(order.id);
            result.reload();
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setBusyId(null);
        }
    };

    const columns: DataColumn<PurchaseOrder>[] = [
        { key: 'number', header: 'Order', render: (row) => <Link className="font-semibold text-sky-700 hover:underline" to={`/purchase/orders/${row.id}`}>{row.purchase_order_number ?? 'Purchase order'}</Link> },
        { key: 'date', header: 'Date', render: (row) => formatDate(row.purchase_order_date) },
        { key: 'supplier', header: 'Supplier', render: (row) => readableRelation(row.supplier) },
        { key: 'warehouse', header: 'Warehouse', render: (row) => readableRelation(row.warehouse) },
        { key: 'total', header: 'Total', render: (row) => <MoneyDisplay value={row.grand_total ?? row.subtotal} currency={row.currency?.code ?? undefined} /> },
        { key: 'workflow', header: 'Workflow', render: (row) => <PurchaseOrderStatusBadge status={row.workflow_status ?? row.status} /> },
        { key: 'receipt', header: 'Receipt', render: (row) => row.receipt_status?.replaceAll('_', ' ') ?? '-' },
        { key: 'invoice', header: 'Invoice', render: (row) => row.invoice_status?.replaceAll('_', ' ') ?? '-' },
        {
            key: 'actions',
            header: 'Actions',
            render: (row) => {
                const capabilities = purchaseOrderCapabilities(row);
                return (
                    <div className="flex flex-wrap gap-2">
                        <LinkButton to={`/purchase/orders/${row.id}`} variant="ghost">View</LinkButton>
                        {capabilities.canEdit && hasPurchasePermission(auth.permissions, purchasePermissions.ordersUpdate) && <LinkButton to={`/purchase/orders/${row.id}/edit`} variant="secondary">Edit</LinkButton>}
                        {capabilities.canSubmit && hasPurchasePermission(auth.permissions, purchasePermissions.ordersSubmit) && <Button type="button" variant="secondary" loading={busyId === row.id} onClick={() => runAction(row, 'submit')}>Submit</Button>}
                        {capabilities.canApprove && hasPurchasePermission(auth.permissions, purchasePermissions.ordersApprove) && <Button type="button" loading={busyId === row.id} onClick={() => runAction(row, 'approve')}>Approve</Button>}
                        {capabilities.canCancel && hasPurchasePermission(auth.permissions, purchasePermissions.ordersCancel) && <Button type="button" variant="danger" loading={busyId === row.id} onClick={() => runAction(row, 'cancel')}>Cancel</Button>}
                        {capabilities.canClose && hasPurchasePermission(auth.permissions, purchasePermissions.ordersClose) && <Button type="button" variant="secondary" loading={busyId === row.id} onClick={() => runAction(row, 'close')}>Close</Button>}
                    </div>
                );
            },
        },
    ];

    return (
        <>
            <ContentHeader title="Purchase Orders" description="Server-paginated purchase order workspace." actions={canCreate ? <LinkButton to="/purchase/orders/create">Create Purchase Order</LinkButton> : undefined} />
            <div className="mb-4 grid gap-4 lg:grid-cols-5">
                <Input type="search" label="Search" placeholder="PO number or supplier" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
                <Select label="Status" value={status} options={statuses} onChange={(event) => { setStatus(event.target.value); setPage(1); }} />
                <SupplierLookupSelect value={supplier} onChange={(value) => { setSupplier(value); setPage(1); }} />
                <Input type="date" label="From" value={dateFrom} onChange={(event) => { setDateFrom(event.target.value); setPage(1); }} />
                <Input type="date" label="To" value={dateTo} onChange={(event) => { setDateTo(event.target.value); setPage(1); }} />
            </div>
            <ErrorAlert error={actionError ?? result.error} />
            {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
        </>
    );
}
