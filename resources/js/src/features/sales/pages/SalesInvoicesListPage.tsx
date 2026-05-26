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
import { formatCurrency, formatDate, parsePositiveInteger } from '../../shared/utils';
import { usePostSalesInvoice, useSalesInvoices } from '../hooks';
import type { SalesInvoiceRecord } from '../types';

export function SalesInvoicesListPage() {
    const { tenantId } = useTenant();
    const [searchParams, setSearchParams] = useSearchParams();
    const [postTarget, setPostTarget] = useState<SalesInvoiceRecord | null>(null);
    const page = parsePositiveInteger(searchParams.get('page'), 1);
    const status = searchParams.get('status') ?? '';
    const customerId = searchParams.get('customerId') ?? '';
    const salesOrderId = searchParams.get('salesOrderId') ?? '';
    const salesInvoicesQuery = useSalesInvoices({
        tenant_id: tenantId,
        page,
        per_page: 10,
        status: status || undefined,
        customer_id: customerId ? Number(customerId) : undefined,
        sales_order_id: salesOrderId ? Number(salesOrderId) : undefined,
        sort: '-updated_at',
    });
    const customersQuery = useCustomers({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name' });
    const postMutation = usePostSalesInvoice(postTarget?.id ?? 0);

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

    async function handlePostInvoice() {
        await postMutation.mutateAsync();
        setPostTarget(null);
    }

    const columns: DataTableColumn<SalesInvoiceRecord>[] = [
        {
            key: 'invoice_number',
            header: 'Invoice',
            render: (invoice) => (
                <div>
                    <Link className="font-medium text-stone-950 transition hover:text-stone-700" to={`/sales/invoices/${invoice.id}`}>
                        {invoice.invoice_number}
                    </Link>
                    <p className="mt-1 text-xs text-stone-500">{formatDate(invoice.invoice_date)}</p>
                </div>
            ),
        },
        { key: 'customer_id', header: 'Customer', render: (invoice) => <span className="text-sm text-stone-700">#{invoice.customer_id}</span> },
        { key: 'sales_order_id', header: 'Sales Order', render: (invoice) => <span className="text-sm text-stone-700">{invoice.sales_order_id ? `#${invoice.sales_order_id}` : '-'}</span> },
        { key: 'status', header: 'Status', render: (invoice) => <StatusBadge>{invoice.status.replaceAll('_', ' ')}</StatusBadge> },
        { key: 'grand_total', header: 'Grand Total', render: (invoice) => <span className="text-sm text-stone-700">{formatCurrency(invoice.grand_total)}</span> },
        {
            key: 'actions',
            header: 'Actions',
            className: 'w-[14rem]',
            render: (invoice) => (
                <div className="flex flex-wrap gap-2">
                    <Link to={`/sales/invoices/${invoice.id}`}>
                        <Button className="h-9 px-3 text-xs" type="button" variant="secondary">View</Button>
                    </Link>
                    {invoice.status === 'draft' ? (
                        <Button className="h-9 px-3 text-xs" onClick={() => setPostTarget(invoice)} type="button" variant="secondary">
                            Post
                        </Button>
                    ) : null}
                </div>
            ),
        },
    ];

    const lookupError = customersQuery.error;

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader breadcrumbs={[{ label: 'Sales' }, { label: 'Sales Invoices' }]} description="Sales invoices keep customer, linked sales order, totals, and posting workflow visible inside a dense ERP table." title="Sales Invoices" />

            <ContentCard className="p-0">
                <TableToolbar description="Filter sales invoices using only the supported customer, sales order, and status parameters from the backend contract." title="Sales invoice list">
                    <SearchFilterToolbar
                        filters={
                            <div className="flex flex-col gap-3 md:flex-row">
                                <Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ status: event.target.value || undefined })} value={status}>
                                    <option value="">All statuses</option>
                                    <option value="draft">Draft</option>
                                    <option value="sent">Sent</option>
                                    <option value="partial_paid">Partial Paid</option>
                                    <option value="paid">Paid</option>
                                    <option value="overdue">Overdue</option>
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

                {salesInvoicesQuery.isPending || customersQuery.isPending ? (
                    <LoadingState className="m-6" lines={8} />
                ) : salesInvoicesQuery.isError || lookupError ? (
                    <ErrorState className="m-6" description={(salesInvoicesQuery.error ?? lookupError)?.message ?? 'Unable to load sales invoices.'} title="Unable to load sales invoices" />
                ) : (
                    <DataTable
                        columns={columns}
                        emptyState={<EmptyState className="m-6" description="No sales invoices match the current filters." title="No sales invoices found" />}
                        footer={<TablePagination meta={salesInvoicesQuery.data.meta} onPageChange={(nextPage) => updateParams({ page: nextPage })} />}
                        getRowKey={(invoice) => invoice.id}
                        rows={salesInvoicesQuery.data.items}
                    />
                )}
            </ContentCard>

            <ConfirmWorkflowModal confirmLabel="Post sales invoice" description={postTarget ? `Post ${postTarget.invoice_number}?` : ''} isLoading={postMutation.isPending} onCancel={() => setPostTarget(null)} onConfirm={() => void handlePostInvoice()} open={Boolean(postTarget)} title="Post sales invoice" />
        </div>
    );
}
