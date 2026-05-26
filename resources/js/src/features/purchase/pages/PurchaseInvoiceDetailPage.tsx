import { useState } from 'react';
import { useParams } from 'react-router-dom';
import { Button } from '../../../components/ui/Button';
import { ContentCard } from '../../../components/ui/ContentCard';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { PageHeader } from '../../../components/layout/PageHeader';
import { ConfirmWorkflowModal, DocumentHeader, DocumentLineItemsTable, TimelinePlaceholder, TotalsSummaryCard, WorkflowActionBar } from '../../shared/workflow';
import { parsePositiveInteger } from '../../shared/utils';
import { useApprovePurchaseInvoice, usePurchaseInvoice } from '../hooks';

export function PurchaseInvoiceDetailPage() {
    const { invoiceId: invoiceIdParam } = useParams();
    const invoiceId = parsePositiveInteger(invoiceIdParam ?? null, 0);
    const [approveOpen, setApproveOpen] = useState(false);
    const invoiceQuery = usePurchaseInvoice(invoiceId, invoiceId > 0);
    const approveMutation = useApprovePurchaseInvoice(invoiceId);

    if (invoiceId <= 0) {
        return <ErrorState description="The purchase invoice route is missing a valid invoice ID." title="Invalid purchase invoice route" />;
    }

    if (invoiceQuery.isPending) {
        return <LoadingState lines={10} />;
    }

    if (invoiceQuery.isError) {
        return <ErrorState description={invoiceQuery.error.message} title="Unable to load purchase invoice" />;
    }

    const invoice = invoiceQuery.data;

    async function handleApprove() {
        await approveMutation.mutateAsync();
        setApproveOpen(false);
    }

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader breadcrumbs={[{ label: 'Purchase', href: '/purchase/invoices' }, { label: 'Purchase Invoices', href: '/purchase/invoices' }, { label: invoice.invoice_number }]} description="Purchase invoice detail shows payable status, dates, totals, and approval action using the current backend resource contract." title={invoice.invoice_number} />
            <DocumentHeader dateLabel="Invoice Date" dateValue={invoice.invoice_date} documentNumber={invoice.invoice_number} documentNumberLabel="Purchase Invoice" helperText="Line arrays are not exposed by the current purchase invoice show resource, so this view focuses on header values, totals, and approval workflow." metrics={[{ label: 'Supplier', value: `#${invoice.supplier_id}` }, { label: 'Due Date', value: invoice.due_date }, { label: 'PO', value: invoice.purchase_order_id ? `#${invoice.purchase_order_id}` : '-' }]} primaryPartyLabel="Supplier" primaryPartyValue={`Supplier #${invoice.supplier_id}`} status={invoice.status} title="Payables document" />
            <div className="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
                <DocumentLineItemsTable columns={[]} description="Purchase invoice lines are not included in the current backend show resource." getRowKey={() => 'none'} rows={[]} title="Line items" />
                <TotalsSummaryCard discountTotal={invoice.discount_total} grandTotal={invoice.grand_total} subtotal={invoice.subtotal} taxTotal={invoice.tax_total} />
            </div>
            <TimelinePlaceholder />
            <ContentCard><WorkflowActionBar description="Approve only when the invoice is still in draft and the backend approve action is available.">{invoice.status === 'draft' ? <Button onClick={() => setApproveOpen(true)} type="button">Approve Invoice</Button> : null}</WorkflowActionBar></ContentCard>
            <ConfirmWorkflowModal confirmLabel="Approve purchase invoice" description={`Approve ${invoice.invoice_number}?`} isLoading={approveMutation.isPending} onCancel={() => setApproveOpen(false)} onConfirm={() => void handleApprove()} open={approveOpen} title="Approve purchase invoice" />
        </div>
    );
}
