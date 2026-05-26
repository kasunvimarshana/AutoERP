import { useState } from 'react';
import { useParams } from 'react-router-dom';
import { Button } from '../../../components/ui/Button';
import { ContentCard } from '../../../components/ui/ContentCard';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { PageHeader } from '../../../components/layout/PageHeader';
import { ConfirmWorkflowModal, DocumentHeader, DocumentLineItemsTable, TimelinePlaceholder, TotalsSummaryCard, WorkflowActionBar } from '../../shared/workflow';
import { parsePositiveInteger } from '../../shared/utils';
import { usePostSalesInvoice, useSalesInvoice } from '../hooks';

export function SalesInvoiceDetailPage() {
    const { salesInvoiceId: salesInvoiceIdParam } = useParams();
    const salesInvoiceId = parsePositiveInteger(salesInvoiceIdParam ?? null, 0);
    const [postOpen, setPostOpen] = useState(false);
    const salesInvoiceQuery = useSalesInvoice(salesInvoiceId, salesInvoiceId > 0);
    const postMutation = usePostSalesInvoice(salesInvoiceId);

    if (salesInvoiceId <= 0) {
        return <ErrorState description="The sales invoice route is missing a valid invoice ID." title="Invalid sales invoice route" />;
    }

    if (salesInvoiceQuery.isPending) {
        return <LoadingState lines={10} />;
    }

    if (salesInvoiceQuery.isError) {
        return <ErrorState description={salesInvoiceQuery.error.message} title="Unable to load sales invoice" />;
    }

    const invoice = salesInvoiceQuery.data;

    async function handlePost() {
        await postMutation.mutateAsync();
        setPostOpen(false);
    }

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader breadcrumbs={[{ label: 'Sales', href: '/sales/orders' }, { label: 'Sales Invoices', href: '/sales/invoices' }, { label: invoice.invoice_number }]} description="Sales invoice detail keeps customer, totals, and posting workflow together in the shared document layout." title={invoice.invoice_number} />
            <DocumentHeader dateLabel="Invoice Date" dateValue={invoice.invoice_date} documentNumber={invoice.invoice_number} documentNumberLabel="Sales Invoice" helperText="The current backend sales invoice show resource does not include line arrays, so this screen keeps the header, totals, and post action aligned with the available API response." metrics={[{ label: 'Customer', value: `#${invoice.customer_id}` }, { label: 'Sales Order', value: invoice.sales_order_id ? `#${invoice.sales_order_id}` : '-' }, { label: 'Shipment', value: invoice.shipment_id ? `#${invoice.shipment_id}` : '-' }]} primaryPartyLabel="Customer" primaryPartyValue={`Customer #${invoice.customer_id}`} status={invoice.status} title="Invoice document" />
            <div className="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
                <DocumentLineItemsTable columns={[]} description="Sales invoice lines are not part of the current backend show resource." getRowKey={() => 'none'} rows={[]} title="Line items" />
                <TotalsSummaryCard discountTotal={invoice.discount_total} grandTotal={invoice.grand_total} subtotal={invoice.subtotal} taxTotal={invoice.tax_total} />
            </div>
            <TimelinePlaceholder />
            <ContentCard>
                <WorkflowActionBar description="Post the invoice only while it remains in draft status.">
                    {invoice.status === 'draft' ? <Button onClick={() => setPostOpen(true)} type="button">Post Sales Invoice</Button> : null}
                </WorkflowActionBar>
            </ContentCard>
            <ConfirmWorkflowModal confirmLabel="Post sales invoice" description={`Post ${invoice.invoice_number}?`} isLoading={postMutation.isPending} onCancel={() => setPostOpen(false)} onConfirm={() => void handlePost()} open={postOpen} title="Post sales invoice" />
        </div>
    );
}
