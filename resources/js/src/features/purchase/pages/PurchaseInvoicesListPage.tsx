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
import { useApprovePurchaseInvoice, usePurchaseInvoices } from '../hooks';
import type { PurchaseInvoiceRecord } from '../types';

export function PurchaseInvoicesListPage() {
    const { tenantId } = useTenant();
    const [searchParams, setSearchParams] = useSearchParams();
    const [approveTarget, setApproveTarget] = useState<PurchaseInvoiceRecord | null>(null);
    const status = searchParams.get('status') ?? '';
    const supplierId = searchParams.get('supplierId') ?? '';
    const invoiceNumber = searchParams.get('invoiceNumber') ?? '';
    const page = parsePositiveInteger(searchParams.get('page'), 1);
    const invoicesQuery = usePurchaseInvoices({ tenant_id: tenantId, page, per_page: 10, status: status || undefined, supplier_id: supplierId ? Number(supplierId) : undefined, invoice_number: invoiceNumber || undefined, sort: '-updated_at' });
    const suppliersQuery = useSuppliers({ tenant_id: tenantId, page: 1, per_page: 100, sort: 'name' });
    const approveMutation = useApprovePurchaseInvoice(approveTarget?.id ?? 0);

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

    async function handleApproveConfirm() {
        await approveMutation.mutateAsync();
        setApproveTarget(null);
    }

    const columns: DataTableColumn<PurchaseInvoiceRecord>[] = [
        { key: 'invoice_number', header: 'Invoice', render: (invoice) => <div><Link className="font-medium text-stone-950 transition hover:text-stone-700" to={`/purchase/invoices/${invoice.id}`}>{invoice.invoice_number}</Link><p className="mt-1 text-xs text-stone-500">{invoice.supplier_invoice_number ?? 'No supplier invoice number'}</p></div> },
        { key: 'supplier_id', header: 'Supplier', render: (invoice) => <span className="text-sm text-stone-700">#{invoice.supplier_id}</span> },
        { key: 'invoice_date', header: 'Invoice Date', render: (invoice) => <span className="text-sm text-stone-700">{formatDate(invoice.invoice_date)}</span> },
        { key: 'status', header: 'Status', render: (invoice) => <StatusBadge>{invoice.status.replaceAll('_', ' ')}</StatusBadge> },
        { key: 'grand_total', header: 'Grand Total', render: (invoice) => <span className="text-sm text-stone-700">{formatCurrency(invoice.grand_total)}</span> },
        { key: 'actions', header: 'Actions', render: (invoice) => <div className="flex flex-wrap gap-2"><Link to={`/purchase/invoices/${invoice.id}`}><Button className="h-9 px-3 text-xs" type="button" variant="secondary">View</Button></Link>{invoice.status === 'draft' ? <Button className="h-9 px-3 text-xs" onClick={() => setApproveTarget(invoice)} type="button" variant="secondary">Approve</Button> : null}</div> },
    ];

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader actions={<div className="flex gap-2"><Link to="/purchase/invoices/new"><Button>Create Invoice</Button></Link><Link to="/purchase/payments/new"><Button variant="secondary">Record Payment</Button></Link></div>} breadcrumbs={[{ label: 'Purchase' }, { label: 'Purchase Invoices' }]} description="Purchase invoices are available as payable workflow documents with draft-to-approved action handling." title="Purchase Invoices" />
            <ContentCard className="p-0">
                <TableToolbar description="Use the supported supplier, status, and invoice-number filters from the backend list request." title="Purchase invoice list">
                    <SearchFilterToolbar filters={<div className="flex flex-col gap-3 md:flex-row"><Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ status: event.target.value || undefined })} value={status}><option value="">All statuses</option><option value="draft">Draft</option><option value="approved">Approved</option><option value="partial_paid">Partial Paid</option><option value="paid">Paid</option><option value="disputed">Disputed</option><option value="cancelled">Cancelled</option></Select><Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ supplierId: event.target.value || undefined })} value={supplierId}><option value="">All suppliers</option>{suppliersQuery.data?.items.map((supplier) => <option key={supplier.id} value={supplier.id}>{supplier.name}</option>)}</Select></div>} search={<Input className="w-full md:max-w-sm" label={undefined} onChange={(event) => updateParams({ invoiceNumber: event.target.value || undefined })} placeholder="Filter invoice number" value={invoiceNumber} />} />
                </TableToolbar>
                {invoicesQuery.isPending || suppliersQuery.isPending ? <LoadingState className="m-6" lines={8} /> : invoicesQuery.isError || suppliersQuery.isError ? <ErrorState className="m-6" description={(invoicesQuery.error ?? suppliersQuery.error)?.message ?? 'Unable to load purchase invoices.'} title="Unable to load purchase invoices" /> : <DataTable columns={columns} emptyState={<EmptyState className="m-6" description="No purchase invoices match the current filters." title="No purchase invoices found" />} getRowKey={(invoice) => invoice.id} rows={invoicesQuery.data.items} />}
            </ContentCard>
            <ConfirmWorkflowModal confirmLabel="Approve purchase invoice" description={approveTarget ? `Approve ${approveTarget.invoice_number}?` : ''} isLoading={approveMutation.isPending} onCancel={() => setApproveTarget(null)} onConfirm={() => void handleApproveConfirm()} open={Boolean(approveTarget)} title="Approve purchase invoice" />
        </div>
    );
}
