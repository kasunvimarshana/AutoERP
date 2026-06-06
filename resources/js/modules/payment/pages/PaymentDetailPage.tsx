import { useParams } from 'react-router-dom';
import { getPayment, getPaymentAllocations, getPaymentUnappliedBalance } from '../paymentApi';
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

type Tab = 'summary' | 'allocations' | 'unapplied';
const tabs = [{ id: 'summary' as Tab, label: 'Summary' }, { id: 'allocations' as Tab, label: 'Allocations' }, { id: 'unapplied' as Tab, label: 'Unapplied Balance' }];

export default function PaymentDetailPage() {
    const id = Number(useParams().id);
    const tabState = useOnDemandTab<Tab>('summary');
    const payment = useApi((signal) => getPayment(id, signal), [id]);
    const allocations = useApi((signal) => getPaymentAllocations(id, signal), [id], tabState.openedTabs.has('allocations'));
    const unapplied = useApi((signal) => getPaymentUnappliedBalance(id, signal), [id], tabState.openedTabs.has('unapplied'));
    if (payment.loading) return <LoadingState />;
    if (!payment.data) return <ErrorAlert error={payment.error} />;
    const value = payment.data;
    return (
        <>
            <ContentHeader title={value.payment_number ?? `Payment #${value.id}`} description={formatDate(value.payment_date)} />
            <Panel className="p-0">
                <Tabs tabs={tabs} active={tabState.activeTab} onChange={tabState.openTab} />
                <div className="p-5">
                    {tabState.activeTab === 'summary' && <DetailGrid items={[
                        { label: 'Status', value: <StatusBadge status={value.status} /> },
                        { label: 'Party', value: readableRelation(value.party) },
                        { label: 'Type', value: value.payment_type },
                        { label: 'Direction', value: value.direction },
                        { label: 'Total', value: <MoneyDisplay value={value.total_amount} /> },
                        { label: 'Allocated', value: <MoneyDisplay value={value.allocated_amount} /> },
                        { label: 'Unapplied', value: <MoneyDisplay value={value.unapplied_amount} /> },
                    ]} />}
                    {tabState.activeTab === 'allocations' && (allocations.loading ? <LoadingState /> : allocations.error ? <ErrorAlert error={allocations.error} /> : <RecordTable rows={allocations.data ?? []} fields={['invoice', 'allocation_date', 'allocated_amount', 'status']} />)}
                    {tabState.activeTab === 'unapplied' && (unapplied.loading ? <LoadingState /> : unapplied.error ? <ErrorAlert error={unapplied.error} /> : <RecordTable rows={unapplied.data ? [unapplied.data] : []} fields={['original_amount', 'applied_amount', 'refunded_amount', 'available_amount', 'status']} />)}
                </div>
            </Panel>
        </>
    );
}
