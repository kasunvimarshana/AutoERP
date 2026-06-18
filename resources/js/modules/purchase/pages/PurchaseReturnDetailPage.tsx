import { useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { DetailGrid } from '@/shared/components/DetailGrid';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { Tabs } from '@/shared/components/Tabs';
import { useApi } from '@/shared/hooks/useApi';
import { useAuth } from '@/modules/auth/AuthProvider';
import { formatDate } from '@/shared/utils/formatDate';
import { formatMoney } from '@/shared/utils/formatMoney';
import { approvePurchaseReturn, cancelPurchaseReturn, getPurchaseReturn, postPurchaseReturn, type PurchaseReturnLine } from '../purchaseApi';
import { PurchaseDocumentShell, PurchasePageHeader } from '../components/PurchaseDocumentShell';
import { hasPurchasePermission, purchasePermissions } from '../purchasePermissions';

type Tab = 'summary' | 'lines' | 'adjustments' | 'linked';

export default function PurchaseReturnDetailPage() {
    const id = Number(useParams().id);
    const auth = useAuth();
    const result = useApi((signal) => getPurchaseReturn(id, signal), [id]);
    const [tab, setTab] = useState<Tab>('summary');
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const [busy, setBusy] = useState(false);
    if (result.loading) return <LoadingState />;
    if (!result.data) return <ErrorAlert error={result.error} />;
    const row = result.data;
    const capabilities = row.capabilities ?? {};
    const can = (permission: string) => hasPurchasePermission(auth.permissions, permission);
    const run = async (action: 'approve' | 'post' | 'cancel') => {
        if (busy) return;
        if (!window.confirm(`Confirm ${action} for this purchase return?`)) return;
        setBusy(true);
        setActionError(null);
        try {
            if (action === 'approve') result.setData(await approvePurchaseReturn(row.id));
            if (action === 'post') {
                await postPurchaseReturn(row.id);
                result.reload();
            }
            if (action === 'cancel') result.setData(await cancelPurchaseReturn(row.id));
        } catch (requestError) {
            setActionError(toApiError(requestError));
        } finally {
            setBusy(false);
        }
    };
    const columns: DataColumn<PurchaseReturnLine>[] = [
        { key: 'item', header: 'Item', render: (line) => line.item?.name ?? '-' },
        { key: 'uom', header: 'UOM', render: (line) => line.uom?.code ?? '-' },
        { key: 'returned', header: 'Returned', render: (line) => line.returned_quantity },
        { key: 'source', header: 'Source qty', render: (line) => line.source_quantity ?? '-' },
        { key: 'remaining', header: 'Remaining', render: (line) => line.remaining_quantity ?? '-' },
        { key: 'price', header: 'Unit price', render: (line) => formatMoney(line.unit_price) },
        { key: 'total', header: 'Total', render: (line) => formatMoney(line.line_total) },
    ];
    return (
        <PurchaseDocumentShell
            header={<PurchasePageHeader
                title={row.return_number ?? 'Purchase return'}
                description={formatDate(row.return_date)}
                actions={<>{capabilities.can_approve && can(purchasePermissions.returnsApprove) && <Button loading={busy} onClick={() => void run('approve')}>Approve</Button>}{capabilities.can_post && can(purchasePermissions.returnsPost) && <Button loading={busy} variant="secondary" onClick={() => void run('post')}>Post</Button>}{capabilities.can_cancel && can(purchasePermissions.returnsCancel) && <Button loading={busy} variant="ghost" onClick={() => void run('cancel')}>Cancel</Button>}</>}
            />}
        >
            <ErrorAlert error={result.error ?? actionError} />
            <Panel>
                <Tabs tabs={[{ id: 'summary', label: 'Summary' }, { id: 'lines', label: 'Lines' }, { id: 'adjustments', label: 'Adjustment allocations' }, { id: 'linked', label: 'Linked documents' }]} active={tab} onChange={setTab} />
                <div className="p-5">
                    {tab === 'summary' && <DetailGrid items={[
                        { label: 'Status', value: <StatusBadge status={row.status} /> },
                        { label: 'Type', value: row.return_type?.replaceAll('_', ' ') ?? '-' },
                        { label: 'Supplier', value: row.supplier?.name ?? '-' },
                        { label: 'Warehouse', value: row.warehouse?.name ?? '-' },
                        { label: 'Affects payable', value: row.affects_supplier_balance ? 'Yes' : 'No' },
                        { label: 'Grand total', value: formatMoney(row.grand_total) },
                        { label: 'Reason', value: row.reason ?? '-' },
                    ]} />}
                    {tab === 'lines' && <DataTable rows={row.lines ?? []} columns={columns} rowKey={(line) => line.id ?? `${line.source_line_type}-${line.source_line_id}`} />}
                    {tab === 'adjustments' && <DataTable rows={row.adjustment_allocations ?? []} columns={[
                        { key: 'type', header: 'Type', render: (allocation) => String(allocation.adjustment_type ?? '-') },
                        { key: 'effect', header: 'Effect', render: (allocation) => String(allocation.effect ?? '-') },
                        { key: 'returned', header: 'Returned', render: (allocation) => formatMoney(String(allocation.returned_amount ?? '0.000000')) },
                        { key: 'remaining', header: 'Remaining', render: (allocation) => formatMoney(String(allocation.remaining_amount ?? '0.000000')) },
                    ]} rowKey={(allocation) => String(allocation.id ?? allocation.adjustment_type)} />}
                    {tab === 'linked' && <DetailGrid items={[
                        { label: 'Source', value: row.source?.number ?? (row.source?.type ? row.source.type.replaceAll('_', ' ') : '-') },
                        { label: 'Debit note', value: row.debit_note_id ? <Link className="text-sky-700 hover:underline" to={`/purchase/debit-notes/${row.debit_note_id}`}>{row.debit_note?.debit_note_number ?? 'Debit note'}</Link> : '-' },
                    ]} />}
                </div>
            </Panel>
        </PurchaseDocumentShell>
    );
}
