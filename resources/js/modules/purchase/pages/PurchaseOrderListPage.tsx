import { useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { approvePurchaseOrder, cancelPurchaseOrder, closePurchaseOrder, listPurchaseOrders, submitPurchaseOrder, type PurchaseOrder } from '../purchaseApi';
import { useAuth } from '@/modules/auth/AuthProvider';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Button, LinkButton } from '@/shared/components/Button';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
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
import { PURCHASE_ORDER_APPROVALS_CHANGED_EVENT } from '../purchaseOrderEvents';
import { ActionMenu } from '@/shared/components/ActionMenu';

const statuses = [
    'draft',
    'pending_approval',
    'approved',
    'closed',
    'cancelled',
].map((value) => ({ value, label: value.replaceAll('_', ' ') }));

export default function PurchaseOrderListPage() {
    const { confirm, confirmDialog } = useConfirmDialog();
    const auth = useAuth();
    const [searchParams] = useSearchParams();
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState(searchParams.get('status') ?? '');
    const [supplier, setSupplier] = useState<NamedResource | null>(null);
    const [dateFrom, setDateFrom] = useState('');
    const [dateTo, setDateTo] = useState('');
    const [filtersOpen, setFiltersOpen] = useState(false);
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

    const canCreate = hasPurchasePermission(auth, purchasePermissions.ordersCreate);
    const activeFilterCount = [status, supplier, dateFrom, dateTo].filter(Boolean).length;
    const runAction = async (order: PurchaseOrder, action: 'submit' | 'approve' | 'cancel' | 'close') => {
        if (!await confirm({
            title: `${action[0].toUpperCase()}${action.slice(1)} purchase order`,
            message: `Confirm ${action} for ${order.purchase_order_number ?? 'this purchase order'}?`,
            confirmLabel: action[0].toUpperCase() + action.slice(1),
            danger: action === 'cancel',
        })) return;
        setBusyId(order.id);
        setActionError(null);
        try {
            const payload = { expected_version: order.row_version };
            if (action === 'submit') await submitPurchaseOrder(order.id, payload);
            if (action === 'approve') await approvePurchaseOrder(order.id, payload);
            if (action === 'cancel') await cancelPurchaseOrder(order.id, payload);
            if (action === 'close') await closePurchaseOrder(order.id, payload);
            result.reload();
            window.dispatchEvent(new Event(PURCHASE_ORDER_APPROVALS_CHANGED_EVENT));
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setBusyId(null);
        }
    };

    const renderActions = (row: PurchaseOrder) => {
        const capabilities = purchaseOrderCapabilities(row);
        return (
            <div className="flex flex-wrap gap-2">
                <LinkButton to={`/purchase/orders/${row.id}`} variant="ghost">View</LinkButton>
                {capabilities.canEdit && hasPurchasePermission(auth, purchasePermissions.ordersUpdate) && <LinkButton to={`/purchase/orders/${row.id}/edit`} variant="secondary">Edit</LinkButton>}
                {capabilities.canSubmit && hasPurchasePermission(auth, purchasePermissions.ordersSubmit) && <Button type="button" variant="secondary" loading={busyId === row.id} onClick={() => runAction(row, 'submit')}>Submit</Button>}
                {capabilities.canApprove && hasPurchasePermission(auth, purchasePermissions.ordersApprove) && <Button type="button" loading={busyId === row.id} onClick={() => runAction(row, 'approve')}>Approve</Button>}
                {capabilities.canCancel && hasPurchasePermission(auth, purchasePermissions.ordersCancel) && <Button type="button" variant="danger" loading={busyId === row.id} onClick={() => runAction(row, 'cancel')}>Cancel</Button>}
                {capabilities.canClose && hasPurchasePermission(auth, purchasePermissions.ordersClose) && <Button type="button" variant="secondary" loading={busyId === row.id} onClick={() => runAction(row, 'close')}>Close</Button>}
            </div>
        );
    };

    const renderMobileActions = (row: PurchaseOrder) => {
        const capabilities = purchaseOrderCapabilities(row);
        const canApprove = capabilities.canApprove && hasPurchasePermission(auth, purchasePermissions.ordersApprove);
        const canSubmit = capabilities.canSubmit && hasPurchasePermission(auth, purchasePermissions.ordersSubmit);
        const hasSecondaryActions = (capabilities.canEdit && hasPurchasePermission(auth, purchasePermissions.ordersUpdate))
            || (capabilities.canCancel && hasPurchasePermission(auth, purchasePermissions.ordersCancel))
            || (capabilities.canClose && hasPurchasePermission(auth, purchasePermissions.ordersClose))
            || (canSubmit && canApprove);

        if (!canApprove && !canSubmit && !hasSecondaryActions) return null;

        return (
            <div className="flex gap-2">
                {canApprove && <Button className="min-w-0 flex-1" type="button" loading={busyId === row.id} onClick={() => runAction(row, 'approve')}>Approve</Button>}
                {!canApprove && canSubmit && <Button className="min-w-0 flex-1" type="button" variant="secondary" loading={busyId === row.id} onClick={() => runAction(row, 'submit')}>Submit</Button>}
                {hasSecondaryActions && (
                    <ActionMenu label="More" className="w-24 shrink-0" triggerClassName="w-full justify-center">
                        {capabilities.canEdit && hasPurchasePermission(auth, purchasePermissions.ordersUpdate) && <LinkButton className="w-full justify-start" to={`/purchase/orders/${row.id}/edit`} variant="ghost">Edit</LinkButton>}
                        {canSubmit && canApprove && <Button className="w-full justify-start" type="button" variant="ghost" loading={busyId === row.id} onClick={() => runAction(row, 'submit')}>Submit</Button>}
                        {capabilities.canCancel && hasPurchasePermission(auth, purchasePermissions.ordersCancel) && <Button className="w-full justify-start text-rose-700" type="button" variant="ghost" loading={busyId === row.id} onClick={() => runAction(row, 'cancel')}>Cancel order</Button>}
                        {capabilities.canClose && hasPurchasePermission(auth, purchasePermissions.ordersClose) && <Button className="w-full justify-start" type="button" variant="ghost" loading={busyId === row.id} onClick={() => runAction(row, 'close')}>Close</Button>}
                    </ActionMenu>
                )}
            </div>
        );
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
            render: (row) => renderActions(row),
        },
    ];

    return (
        <>
            <ContentHeader title="Purchase Orders List" description="Server-paginated purchase order workspace." actions={canCreate ? <LinkButton className="w-full sm:w-auto" to="/purchase/orders/create">Create Purchase Order</LinkButton> : undefined} />
            <div className="mb-4 space-y-3">
                <div className="grid grid-cols-[minmax(0,1fr)_auto] items-end gap-3">
                    <Input type="search" label="Search" placeholder="PO number or supplier" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
                    <button
                        type="button"
                        className="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 md:hidden"
                        aria-expanded={filtersOpen}
                        aria-controls="purchase-order-filters"
                        onClick={() => setFiltersOpen((open) => !open)}
                    >
                        Filters{activeFilterCount > 0 ? ` (${activeFilterCount})` : ''}
                    </button>
                </div>
                <div id="purchase-order-filters" className={`${filtersOpen ? 'grid' : 'hidden'} gap-3 sm:grid-cols-2 md:grid md:grid-cols-2 lg:grid-cols-4`}>
                    <Select label="Status" value={status} options={statuses} onChange={(event) => { setStatus(event.target.value); setPage(1); }} />
                    <SupplierLookupSelect value={supplier} onChange={(value) => { setSupplier(value); setPage(1); }} />
                    <Input type="date" label="From" value={dateFrom} onChange={(event) => { setDateFrom(event.target.value); setPage(1); }} />
                    <Input type="date" label="To" value={dateTo} onChange={(event) => { setDateTo(event.target.value); setPage(1); }} />
                    {activeFilterCount > 0 && <button type="button" className="justify-self-start rounded-md px-2 py-1 text-sm font-semibold text-blue-700 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500" onClick={() => { setStatus(''); setSupplier(null); setDateFrom(''); setDateTo(''); setPage(1); }}>Clear filters</button>}
                </div>
            </div>
            <ErrorAlert error={actionError ?? result.error} />
            {result.loading ? <LoadingState /> : <DataTable
                rows={result.data?.data ?? []}
                columns={columns}
                rowKey={(row) => row.id}
                mobileSummary={(row) => <Link className="break-words text-base font-semibold text-sky-700 hover:underline" to={`/purchase/orders/${row.id}`}>{row.purchase_order_number ?? 'Purchase order'}</Link>}
                rowBadge={(row) => <PurchaseOrderStatusBadge status={row.workflow_status ?? row.status} />}
                mobileDetails={(row) => (
                    <div className="space-y-3">
                        <div className="flex items-start justify-between gap-3">
                            <div className="min-w-0">
                                <p className="text-xs font-medium text-slate-500">Supplier</p>
                                <p className="break-words font-medium text-slate-900">{readableRelation(row.supplier)}</p>
                            </div>
                            <div className="shrink-0 text-right">
                                <p className="text-xs font-medium text-slate-500">Total</p>
                                <p className="font-semibold text-slate-950"><MoneyDisplay value={row.grand_total ?? row.subtotal} currency={row.currency?.code ?? undefined} /></p>
                            </div>
                        </div>
                        <p className="text-sm text-slate-600">{formatDate(row.purchase_order_date)}</p>
                        <details className="group rounded-lg bg-slate-50 px-3 py-2 text-sm">
                            <summary className="cursor-pointer list-none font-medium text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                                More details
                                <span className="float-right text-slate-400 group-open:rotate-180" aria-hidden="true">⌄</span>
                            </summary>
                            <dl className="mt-3 grid grid-cols-2 gap-x-3 gap-y-2 border-t border-slate-200 pt-3">
                                <div><dt className="text-xs text-slate-500">Warehouse</dt><dd className="break-words text-slate-800">{readableRelation(row.warehouse)}</dd></div>
                                <div><dt className="text-xs text-slate-500">Receipt</dt><dd className="capitalize text-slate-800">{row.receipt_status?.replaceAll('_', ' ') ?? '-'}</dd></div>
                                <div><dt className="text-xs text-slate-500">Invoice</dt><dd className="capitalize text-slate-800">{row.invoice_status?.replaceAll('_', ' ') ?? '-'}</dd></div>
                            </dl>
                        </details>
                    </div>
                )}
                mobileActions={renderMobileActions}
            />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
            {confirmDialog}
        </>
    );
}
