import { useNavigate, useParams } from 'react-router-dom';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { Panel } from '@/shared/components/Panel';
import { useApi } from '@/shared/hooks/useApi';
import { readableRelation } from '@/shared/utils/object';
import { subtractDecimal } from '@/shared/utils/decimal';
import { SalesDocumentTabs } from '../components/SalesDocumentTabs';
import { getSalesOrder, getSalesQuotation } from '../salesApi';
import type { SalesOrder, SalesQuotation } from '../salesTypes';

export default function SalesDocumentDetailPage({ kind }: { kind: 'quotation' | 'order' }) {
    const id = Number(useParams().id);
    const navigate = useNavigate();
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
            <Panel title="Totals">
                <dl className="space-y-3 text-sm">
                    <Row label="Subtotal" value={<MoneyDisplay value={document.subtotal} />} />
                    <Row label="Line discounts" value={<MoneyDisplay value={document.line_discount_total} />} />
                    <Row label="Line tax" value={<MoneyDisplay value={document.line_tax_total} />} />
                    <Row label="Header net" value={<MoneyDisplay value={subtractDecimal(document.header_increase_total ?? '0.000000', document.header_decrease_total ?? '0.000000')} />} />
                    <Row label="Grand total" value={<MoneyDisplay value={document.grand_total} />} />
                </dl>
            </Panel>
        </div>
    );

    return (
        <>
            <ContentHeader
                title={number}
                description={`Backend workflow and totals. Current status: ${document.status?.replaceAll('_', ' ') ?? 'unknown'}.`}
                actions={(
                    <div className="flex gap-2">
                        {document.status === 'draft' && <LinkButton to={`/sales/${segment}/${id}/edit`} variant="secondary">Edit</LinkButton>}
                        {order && <LinkButton to={`/sales/deliveries?order_id=${id}`}>Create delivery</LinkButton>}
                        <Button variant="ghost" onClick={() => navigate(`/sales/${segment}`)}>Back</Button>
                    </div>
                )}
            />
            <SalesDocumentTabs document={document} summary={summary} />
        </>
    );
}

function Row({ label, value }: { label: string; value: React.ReactNode }) {
    return <div className="flex justify-between gap-4"><dt className="text-slate-500">{label}</dt><dd className="font-semibold text-slate-900">{value}</dd></div>;
}
