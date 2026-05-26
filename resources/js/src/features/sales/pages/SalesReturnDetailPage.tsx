import { useState } from 'react';
import { useParams } from 'react-router-dom';
import { Button } from '../../../components/ui/Button';
import { ContentCard } from '../../../components/ui/ContentCard';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { PageHeader } from '../../../components/layout/PageHeader';
import { ConfirmWorkflowModal, DocumentHeader, DocumentLineItemsTable, TimelinePlaceholder, TotalsSummaryCard, WorkflowActionBar } from '../../shared/workflow';
import { parsePositiveInteger } from '../../shared/utils';
import { useApproveSalesReturn, useReceiveSalesReturn, useSalesReturn } from '../hooks';

export function SalesReturnDetailPage() {
    const { salesReturnId: salesReturnIdParam } = useParams();
    const salesReturnId = parsePositiveInteger(salesReturnIdParam ?? null, 0);
    const [action, setAction] = useState<'approve' | 'receive' | null>(null);
    const salesReturnQuery = useSalesReturn(salesReturnId, salesReturnId > 0);
    const approveMutation = useApproveSalesReturn(salesReturnId);
    const receiveMutation = useReceiveSalesReturn(salesReturnId);

    if (salesReturnId <= 0) {
        return <ErrorState description="The sales return route is missing a valid return ID." title="Invalid sales return route" />;
    }

    if (salesReturnQuery.isPending) {
        return <LoadingState lines={10} />;
    }

    if (salesReturnQuery.isError) {
        return <ErrorState description={salesReturnQuery.error.message} title="Unable to load sales return" />;
    }

    const salesReturn = salesReturnQuery.data;

    async function handleActionConfirm() {
        if (action === 'approve') {
            await approveMutation.mutateAsync();
        } else if (action === 'receive') {
            await receiveMutation.mutateAsync();
        }

        setAction(null);
    }

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader breadcrumbs={[{ label: 'Sales', href: '/sales/orders' }, { label: 'Sales Returns', href: '/sales/returns' }, { label: salesReturn.return_number }]} description="Sales return detail keeps customer, original document linkage, totals, and approve/receive actions in one document workspace." title={salesReturn.return_number} />
            <DocumentHeader dateLabel="Return Date" dateValue={salesReturn.return_date} documentNumber={salesReturn.return_number} documentNumberLabel="Sales Return" helperText="The current sales return show resource does not include line arrays, so the shared detail page keeps the header, totals, and workflow actions aligned with the available backend contract." metrics={[{ label: 'Customer', value: `#${salesReturn.customer_id}` }, { label: 'Original Sales Order', value: salesReturn.original_sales_order_id ? `#${salesReturn.original_sales_order_id}` : '-' }, { label: 'Original Invoice', value: salesReturn.original_invoice_id ? `#${salesReturn.original_invoice_id}` : '-' }]} primaryPartyLabel="Customer" primaryPartyValue={`Customer #${salesReturn.customer_id}`} status={salesReturn.status} title="Return document" />
            <div className="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
                <DocumentLineItemsTable columns={[]} description="Sales return lines are not part of the current backend show resource." getRowKey={() => 'none'} rows={[]} title="Line items" />
                <TotalsSummaryCard grandTotal={salesReturn.grand_total} subtotal={salesReturn.subtotal} taxTotal={salesReturn.tax_total} />
            </div>
            <TimelinePlaceholder />
            <ContentCard>
                <WorkflowActionBar description="Approve the return while it is in draft, then receive it once the document moves to approved status.">
                    {salesReturn.status === 'draft' ? <Button onClick={() => setAction('approve')} type="button">Approve Sales Return</Button> : null}
                    {salesReturn.status === 'approved' ? <Button onClick={() => setAction('receive')} type="button" variant="secondary">Receive Sales Return</Button> : null}
                </WorkflowActionBar>
            </ContentCard>
            <ConfirmWorkflowModal confirmLabel={action === 'approve' ? 'Approve sales return' : 'Receive sales return'} description={action ? `${action === 'approve' ? 'Approve' : 'Receive'} ${salesReturn.return_number}?` : ''} isLoading={approveMutation.isPending || receiveMutation.isPending} onCancel={() => setAction(null)} onConfirm={() => void handleActionConfirm()} open={Boolean(action)} title={action === 'approve' ? 'Approve sales return' : 'Receive sales return'} />
        </div>
    );
}
