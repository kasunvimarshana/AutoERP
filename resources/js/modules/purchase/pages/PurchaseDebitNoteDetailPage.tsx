import { Link, useParams } from 'react-router-dom';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DetailGrid } from '@/shared/components/DetailGrid';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { formatDate } from '@/shared/utils/formatDate';
import { formatMoney } from '@/shared/utils/formatMoney';
import { getPurchaseDebitNote } from '../purchaseApi';

export default function PurchaseDebitNoteDetailPage() {
    const id = Number(useParams().id);
    const result = useApi((signal) => getPurchaseDebitNote(id, signal), [id]);
    if (result.loading) return <LoadingState />;
    if (!result.data) return <ErrorAlert error={result.error} />;
    const note = result.data;
    return (
        <div className="space-y-5">
            <ContentHeader title={note.debit_note_number ?? 'Debit note'} description={formatDate(note.debit_note_date)} />
            <Panel>
                <DetailGrid items={[
                    { label: 'Status', value: <StatusBadge status={note.status} /> },
                    { label: 'Supplier', value: note.supplier?.name ?? '-' },
                    { label: 'Amount', value: formatMoney(note.amount) },
                    { label: 'Allocated', value: formatMoney(note.allocated_amount) },
                    { label: 'Remaining', value: formatMoney(note.remaining_amount) },
                    { label: 'Source', value: note.source?.number ?? note.source?.type?.replaceAll('_', ' ') ?? '-' },
                    { label: 'Purchase return', value: note.purchase_return_id ? <Link className="text-sky-700 hover:underline" to={`/purchase/returns/${note.purchase_return_id}`}>{note.purchase_return?.return_number ?? note.source?.number ?? 'Purchase return'}</Link> : '-' },
                    { label: 'Reason', value: note.reason ?? '-' },
                ]} />
            </Panel>
        </div>
    );
}
