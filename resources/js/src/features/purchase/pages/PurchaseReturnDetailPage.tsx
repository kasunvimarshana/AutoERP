import { useState } from 'react';
import { useParams } from 'react-router-dom';
import { Button } from '../../../components/ui/Button';
import { ContentCard } from '../../../components/ui/ContentCard';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { PageHeader } from '../../../components/layout/PageHeader';
import { ConfirmWorkflowModal, DocumentHeader, DocumentLineItemsTable, TimelinePlaceholder, TotalsSummaryCard, WorkflowActionBar } from '../../shared/workflow';
import { parsePositiveInteger } from '../../shared/utils';
import { usePostPurchaseReturn, usePurchaseReturn } from '../hooks';

export function PurchaseReturnDetailPage() {
    const { purchaseReturnId: purchaseReturnIdParam } = useParams();
    const purchaseReturnId = parsePositiveInteger(purchaseReturnIdParam ?? null, 0);
    const [postOpen, setPostOpen] = useState(false);
    const purchaseReturnQuery = usePurchaseReturn(purchaseReturnId, purchaseReturnId > 0);
    const postMutation = usePostPurchaseReturn(purchaseReturnId);

    if (purchaseReturnId <= 0) {
        return <ErrorState description="The purchase return route is missing a valid return ID." title="Invalid purchase return route" />;
    }

    if (purchaseReturnQuery.isPending) {
        return <LoadingState lines={10} />;
    }

    if (purchaseReturnQuery.isError) {
        return <ErrorState description={purchaseReturnQuery.error.message} title="Unable to load purchase return" />;
    }

    const purchaseReturn = purchaseReturnQuery.data;

    async function handlePost() {
        await postMutation.mutateAsync();
        setPostOpen(false);
    }

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader breadcrumbs={[{ label: 'Purchase', href: '/purchase/returns' }, { label: 'Purchase Returns', href: '/purchase/returns' }, { label: purchaseReturn.return_number }]} description="Purchase return detail keeps return reason, vendor context, totals, and posting action aligned with the backend workflow." title={purchaseReturn.return_number} />
            <DocumentHeader dateLabel="Return Date" dateValue={purchaseReturn.return_date} documentNumber={purchaseReturn.return_number} documentNumberLabel="Purchase Return" helperText="The current backend show resource does not include purchase return lines, so line rendering remains a placeholder pending direct backend support." metrics={[{ label: 'Supplier', value: `#${purchaseReturn.supplier_id}` }, { label: 'Original GRN', value: purchaseReturn.original_grn_id ? `#${purchaseReturn.original_grn_id}` : '-' }, { label: 'Original Invoice', value: purchaseReturn.original_invoice_id ? `#${purchaseReturn.original_invoice_id}` : '-' }]} primaryPartyLabel="Supplier" primaryPartyValue={`Supplier #${purchaseReturn.supplier_id}`} status={purchaseReturn.status} title="Return document" />
            <div className="grid gap-4 xl:grid-cols-[1.1fr_0.9fr]">
                <DocumentLineItemsTable columns={[]} description="Purchase return lines are not included in the current backend show resource." getRowKey={() => 'none'} rows={[]} title="Line items" />
                <TotalsSummaryCard grandTotal={purchaseReturn.grand_total} subtotal={purchaseReturn.subtotal} taxTotal={purchaseReturn.tax_total} />
            </div>
            <TimelinePlaceholder />
            <ContentCard><WorkflowActionBar description="Posting remains available only while the document is still in a pre-shipped workflow state.">{(purchaseReturn.status === 'draft' || purchaseReturn.status === 'approved') ? <Button onClick={() => setPostOpen(true)} type="button">Post Purchase Return</Button> : null}</WorkflowActionBar></ContentCard>
            <ConfirmWorkflowModal confirmLabel="Post purchase return" description={`Post ${purchaseReturn.return_number}?`} isLoading={postMutation.isPending} onCancel={() => setPostOpen(false)} onConfirm={() => void handlePost()} open={postOpen} title="Post purchase return" />
        </div>
    );
}
