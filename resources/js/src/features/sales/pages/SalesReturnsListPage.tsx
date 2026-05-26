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
import { useApproveSalesReturn, useReceiveSalesReturn, useSalesReturns } from '../hooks';
import type { SalesReturnRecord } from '../types';

export function SalesReturnsListPage() {
    const { tenantId } = useTenant();
    const [searchParams, setSearchParams] = useSearchParams();
    const [actionTarget, setActionTarget] = useState<{ record: SalesReturnRecord; action: 'approve' | 'receive' } | null>(null);
    const page = parsePositiveInteger(searchParams.get('page'), 1);
    const status = searchParams.get('status') ?? '';
    const customerId = searchParams.get('customerId') ?? '';
    const originalSalesOrderId = searchParams.get('originalSalesOrderId') ?? '';
    const salesReturnsQuery = useSalesReturns({
        tenant_id: tenantId,
        page,
        per_page: 10,
        status: status || undefined,
        customer_id: customerId ? Number(customerId) : undefined,
        original_sales_order_id: originalSalesOrderId ? Number(originalSalesOrderId) : undefined,
        sort: '-updated_at',
    });
    const customersQuery = useCustomers({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name' });
    const approveMutation = useApproveSalesReturn(actionTarget?.record.id ?? 0);
    const receiveMutation = useReceiveSalesReturn(actionTarget?.record.id ?? 0);

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

            if ('status' in updates || 'customerId' in updates || 'originalSalesOrderId' in updates) {
                next.set('page', '1');
            }

            return next;
        });
    }

    async function handleActionConfirm() {
        if (!actionTarget) {
            return;
        }

        if (actionTarget.action === 'approve') {
            await approveMutation.mutateAsync();
        } else {
            await receiveMutation.mutateAsync();
        }

        setActionTarget(null);
    }

    const columns: DataTableColumn<SalesReturnRecord>[] = [
        {
            key: 'return_number',
            header: 'Sales Return',
            render: (salesReturn) => (
                <div>
                    <Link className="font-medium text-stone-950 transition hover:text-stone-700" to={`/sales/returns/${salesReturn.id}`}>
                        {salesReturn.return_number}
                    </Link>
                    <p className="mt-1 text-xs text-stone-500">{formatDate(salesReturn.return_date)}</p>
                </div>
            ),
        },
        { key: 'customer_id', header: 'Customer', render: (salesReturn) => <span className="text-sm text-stone-700">#{salesReturn.customer_id}</span> },
        { key: 'original_sales_order_id', header: 'Original SO', render: (salesReturn) => <span className="text-sm text-stone-700">{salesReturn.original_sales_order_id ? `#${salesReturn.original_sales_order_id}` : '-'}</span> },
        { key: 'status', header: 'Status', render: (salesReturn) => <StatusBadge>{salesReturn.status.replaceAll('_', ' ')}</StatusBadge> },
        {
            key: 'actions',
            header: 'Actions',
            className: 'w-[16rem]',
            render: (salesReturn) => (
                <div className="flex flex-wrap gap-2">
                    <Link to={`/sales/returns/${salesReturn.id}`}>
                        <Button className="h-9 px-3 text-xs" type="button" variant="secondary">View</Button>
                    </Link>
                    {salesReturn.status === 'draft' ? (
                        <Button className="h-9 px-3 text-xs" onClick={() => setActionTarget({ record: salesReturn, action: 'approve' })} type="button" variant="secondary">
                            Approve
                        </Button>
                    ) : null}
                    {salesReturn.status === 'approved' ? (
                        <Button className="h-9 px-3 text-xs" onClick={() => setActionTarget({ record: salesReturn, action: 'receive' })} type="button" variant="secondary">
                            Receive
                        </Button>
                    ) : null}
                </div>
            ),
        },
    ];

    const lookupError = customersQuery.error;

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader breadcrumbs={[{ label: 'Sales' }, { label: 'Sales Returns' }]} description="Sales returns show customer, original order linkage, and the approve/receive workflow in the same dense operations grid." title="Sales Returns" />

            <ContentCard className="p-0">
                <TableToolbar description="Filter sales returns using the supported customer, original sales order, and status parameters from the backend list request." title="Sales return list">
                    <SearchFilterToolbar
                        filters={
                            <div className="flex flex-col gap-3 md:flex-row">
                                <Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ status: event.target.value || undefined })} value={status}>
                                    <option value="">All statuses</option>
                                    <option value="draft">Draft</option>
                                    <option value="approved">Approved</option>
                                    <option value="received">Received</option>
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
                                <Input className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ originalSalesOrderId: event.target.value || undefined })} placeholder="Original SO ID" value={originalSalesOrderId} />
                            </div>
                        }
                    />
                </TableToolbar>

                {salesReturnsQuery.isPending || customersQuery.isPending ? (
                    <LoadingState className="m-6" lines={8} />
                ) : salesReturnsQuery.isError || lookupError ? (
                    <ErrorState className="m-6" description={(salesReturnsQuery.error ?? lookupError)?.message ?? 'Unable to load sales returns.'} title="Unable to load sales returns" />
                ) : (
                    <DataTable
                        columns={columns}
                        emptyState={<EmptyState className="m-6" description="No sales returns match the current filters." title="No sales returns found" />}
                        footer={<TablePagination meta={salesReturnsQuery.data.meta} onPageChange={(nextPage) => updateParams({ page: nextPage })} />}
                        getRowKey={(salesReturn) => salesReturn.id}
                        rows={salesReturnsQuery.data.items}
                    />
                )}
            </ContentCard>

            <ConfirmWorkflowModal
                confirmLabel={actionTarget?.action === 'approve' ? 'Approve sales return' : 'Receive sales return'}
                description={actionTarget ? `${actionTarget.action === 'approve' ? 'Approve' : 'Receive'} ${actionTarget.record.return_number}?` : ''}
                isLoading={approveMutation.isPending || receiveMutation.isPending}
                onCancel={() => setActionTarget(null)}
                onConfirm={() => void handleActionConfirm()}
                open={Boolean(actionTarget)}
                title={actionTarget?.action === 'approve' ? 'Approve sales return' : 'Receive sales return'}
            />
        </div>
    );
}
