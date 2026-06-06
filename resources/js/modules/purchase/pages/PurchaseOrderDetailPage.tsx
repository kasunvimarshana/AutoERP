import { useParams } from 'react-router-dom';
import { getPurchaseOrder } from '../purchaseApi';
import { useApi } from '@/shared/hooks/useApi';
import { useOnDemandTab } from '@/shared/hooks/useOnDemandTab';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { Tabs } from '@/shared/components/Tabs';
import { Panel } from '@/shared/components/Panel';
import { DetailGrid } from '@/shared/components/DetailGrid';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { RecordTable } from '@/shared/components/RecordTable';
import { CapabilityNotice } from '@/shared/components/CapabilityNotice';
import { LoadingState } from '@/shared/components/LoadingState';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { formatDate } from '@/shared/utils/formatDate';
import { readableRelation } from '@/shared/utils/object';

type Tab = 'summary' | 'lines' | 'adjustments' | 'goods_receipt_notes' | 'invoices' | 'returns' | 'audit';
const tabs = [['summary', 'Summary'], ['lines', 'Lines'], ['adjustments', 'Adjustments'], ['goods_receipt_notes', 'GRNs'], ['invoices', 'Invoices'], ['returns', 'Returns'], ['audit', 'Audit']].map(([id, label]) => ({ id: id as Tab, label }));

export default function PurchaseOrderDetailPage() {
    const id = Number(useParams().id);
    const result = useApi((signal) => getPurchaseOrder(id, signal), [id]);
    const tabState = useOnDemandTab<Tab>('summary');
    if (result.loading) return <LoadingState />;
    if (!result.data) return <ErrorAlert error={result.error} />;
    const order = result.data;
    const fields: Partial<Record<Tab, string[]>> = {
        lines: ['item', 'description', 'ordered_quantity', 'received_quantity', 'unit_price', 'line_total', 'status'],
        adjustments: ['name', 'adjustment_type', 'effect', 'amount'],
        goods_receipt_notes: ['grn_number', 'received_date', 'status', 'total'],
    };
    return (
        <>
            <ContentHeader title={order.purchase_order_number ?? `Purchase order #${order.id}`} description={formatDate(order.purchase_order_date)} />
            <Panel className="p-0">
                <Tabs tabs={tabs} active={tabState.activeTab} onChange={tabState.openTab} />
                <div className="p-5">
                    {tabState.activeTab === 'summary' ? <DetailGrid items={[
                        { label: 'Status', value: <StatusBadge status={order.status} /> },
                        { label: 'Supplier', value: readableRelation(order.supplier) },
                        { label: 'Order date', value: formatDate(order.purchase_order_date) },
                        { label: 'Expected delivery', value: formatDate(order.expected_delivery_date) },
                        { label: 'Total', value: <MoneyDisplay value={order.grand_total ?? order.subtotal} /> },
                    ]} /> : fields[tabState.activeTab] ? <RecordTable rows={(order[tabState.activeTab] ?? []) as Record<string, unknown>[]} fields={fields[tabState.activeTab] ?? []} /> : <CapabilityNotice>This relation is not exposed by the current Purchase order detail API.</CapabilityNotice>}
                </div>
            </Panel>
        </>
    );
}
