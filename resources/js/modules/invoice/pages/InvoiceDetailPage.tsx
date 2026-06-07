import { useParams } from 'react-router-dom';
import { getInvoice, getInvoiceAdjustments, getInvoiceBalance, getInvoiceSources } from '../invoiceApi';
import { useApi } from '@/shared/hooks/useApi';
import { useOnDemandTab } from '@/shared/hooks/useOnDemandTab';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Tabs } from '@/shared/components/Tabs';
import { Panel } from '@/shared/components/Panel';
import { DetailGrid } from '@/shared/components/DetailGrid';
import { RecordTable } from '@/shared/components/RecordTable';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { formatDate } from '@/shared/utils/formatDate';
import { readableRelation } from '@/shared/utils/object';

type Tab = 'summary' | 'balance' | 'sources' | 'lines' | 'adjustments';
const tabs = [['summary', 'Summary'], ['balance', 'Balance'], ['sources', 'Sources'], ['lines', 'Lines'], ['adjustments', 'Adjustments']].map(([id, label]) => ({ id: id as Tab, label }));

export default function InvoiceDetailPage() {
    const id = Number(useParams().id);
    const tabState = useOnDemandTab<Tab>('summary');
    const invoice = useApi((signal) => getInvoice(id, signal), [id]);
    const balance = useApi((signal) => getInvoiceBalance(id, signal), [id], tabState.openedTabs.has('balance'));
    const sources = useApi((signal) => getInvoiceSources(id, signal), [id], tabState.openedTabs.has('sources'));
    const adjustments = useApi((signal) => getInvoiceAdjustments(id, signal), [id], tabState.openedTabs.has('adjustments'));
    if (invoice.loading) return <LoadingState />;
    if (!invoice.data) return <ErrorAlert error={invoice.error} />;
    const value = invoice.data;
    return (
        <>
            <ContentHeader title={value.invoice_number ?? 'Invoice'} description={formatDate(value.invoice_date)} />
            <Panel className="p-0">
                <Tabs tabs={tabs} active={tabState.activeTab} onChange={tabState.openTab} />
                <div className="p-5">
                    {tabState.activeTab === 'summary' && <DetailGrid items={[
                        { label: 'Status', value: <StatusBadge status={value.status} /> },
                        { label: 'Party', value: readableRelation(value.party) },
                        { label: 'Type', value: value.invoice_type },
                        { label: 'Direction', value: value.direction },
                        { label: 'Total', value: <MoneyDisplay value={value.grand_total} /> },
                        { label: 'Balance due', value: <MoneyDisplay value={value.balance_due} /> },
                    ]} />}
                    {tabState.activeTab === 'lines' && <RecordTable rows={value.lines ?? []} fields={['line_number', 'item', 'description', 'quantity', 'unit_price', 'line_total']} />}
                    {tabState.activeTab === 'balance' && <AsyncRecord loading={balance.loading} error={balance.error} rows={balance.data ? [balance.data] : []} fields={['originalAmount', 'paidAmount', 'creditedAmount', 'remainingAmount', 'status']} />}
                    {tabState.activeTab === 'adjustments' && <AsyncRecord loading={adjustments.loading} error={adjustments.error} rows={adjustments.data ?? []} fields={['name', 'adjustment_type', 'effect', 'amount', 'allocated_amount']} />}
                    {tabState.activeTab === 'sources' && <AsyncRecord loading={sources.loading} error={sources.error} rows={sources.data?.sources ?? []} fields={['source_type', 'source_id', 'source_number', 'source_date']} />}
                </div>
            </Panel>
        </>
    );
}

function AsyncRecord({ loading, error, rows, fields }: { loading: boolean; error: import('@/shared/api/apiError').ApiError | null; rows: Record<string, unknown>[]; fields: string[] }) {
    if (loading) return <LoadingState />;
    if (error) return <ErrorAlert error={error} />;
    return <RecordTable rows={rows} fields={fields} />;
}
