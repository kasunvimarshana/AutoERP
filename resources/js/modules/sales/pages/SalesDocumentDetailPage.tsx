import { useNavigate, useParams } from 'react-router-dom';
import { useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { useApi } from '@/shared/hooks/useApi';
import { readableRelation } from '@/shared/utils/object';
import { SalesDocumentTabs } from '../components/SalesDocumentTabs';
import { SalesOrderActions } from '../components/SalesDocumentActions';
import { SalesProgressPanel } from '../components/SalesProgressPanel';
import { SalesRelatedDocuments } from '../components/SalesRelatedDocuments';
import { SalesSummaryPanel } from '../components/SalesSummaryPanel';
import { approveSalesOrder, cancelSalesOrder, closeSalesOrder, getSalesOrder, getSalesQuotation, submitSalesOrder } from '../salesApi';
import type { SalesOrder, SalesQuotation } from '../salesTypes';

export default function SalesDocumentDetailPage({ kind }: { kind: 'quotation' | 'order' }) {
    const id = Number(useParams().id);
    const navigate = useNavigate();
    const [busy, setBusy] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const result = useApi<SalesQuotation | SalesOrder>(
        (signal) => kind === 'quotation' ? getSalesQuotation(id, signal) : getSalesOrder(id, signal),
        [id, kind],
    );
    if (result.loading) return <LoadingState />;
    if (result.error || !result.data) return <ErrorAlert error={result.error} />;

    const document = result.data;
    const order = kind === 'order' ? document as SalesOrder : null;
    const quotation = kind === 'quotation' ? document as SalesQuotation : null;
    const segment = kind === 'quotation' ? 'quotations' : 'orders';
    const number = quotation?.quotation_number ?? order?.sales_order_number ?? 'Sales document';
    const runOrderAction = async (action: 'submit' | 'approve' | 'cancel' | 'close') => {
        if (!order || busy) return;
        setBusy(true);
        setActionError(null);
        try {
            if (action === 'submit') await submitSalesOrder(order.id);
            if (action === 'approve') await approveSalesOrder(order.id);
            if (action === 'cancel') await cancelSalesOrder(order.id);
            if (action === 'close') await closeSalesOrder(order.id);
            result.reload();
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setBusy(false);
        }
    };

    const summary = (
        <div className="grid gap-4 lg:grid-cols-2">
            <Panel title="Customer and dates">
                <dl className="space-y-3 text-sm">
                    <Row label="Customer" value={readableRelation(document.customer)} />
                    <Row label="Document date" value={quotation?.quotation_date ?? order?.sales_order_date ?? '-'} />
                    <Row label={kind === 'quotation' ? 'Valid until' : 'Expected delivery'} value={quotation?.valid_until ?? order?.expected_delivery_date ?? '-'} />
                    {order && <Row label="Warehouse" value={readableRelation(order.warehouse)} />}
                </dl>
            </Panel>
            <SalesSummaryPanel totals={document} />
        </div>
    );

    return (
        <>
            <ContentHeader
                title={number}
                description={`${kind === 'quotation' ? 'Sales quotation' : 'Sales order'} status: ${document.status?.replaceAll('_', ' ') ?? 'unknown'}.`}
                actions={(
                    <div className="flex gap-2">
                        {quotation && document.status === 'draft' && <LinkButton to={`/sales/${segment}/${id}/edit`} variant="secondary">Edit</LinkButton>}
                        {order && (
                            <SalesOrderActions
                                order={order}
                                busy={busy}
                                onSubmit={() => void runOrderAction('submit')}
                                onApprove={() => void runOrderAction('approve')}
                                onCancel={() => void runOrderAction('cancel')}
                                onClose={() => void runOrderAction('close')}
                            />
                        )}
                        <Button variant="ghost" onClick={() => navigate(`/sales/${segment}`)}>Back</Button>
                    </div>
                )}
            />
            <ErrorAlert error={actionError} />
            {order && <SalesProgressPanel progress={order.progress} />}
            <SalesDocumentTabs document={document} summary={summary} />
            {order && <SalesRelatedDocuments documents={order.related_documents} />}
        </>
    );
}

function Row({ label, value }: { label: string; value: React.ReactNode }) {
    return <div className="flex justify-between gap-4"><dt className="text-slate-500">{label}</dt><dd className="font-semibold text-slate-900">{value}</dd></div>;
}
