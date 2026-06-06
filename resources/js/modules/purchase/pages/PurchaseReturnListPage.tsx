import { useState } from 'react';
import { Link } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Pagination } from '@/shared/components/Pagination';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { formatDate } from '@/shared/utils/formatDate';
import { formatMoney } from '@/shared/utils/formatMoney';
import { approvePurchaseReturn, cancelPurchaseReturn, listPurchaseReturns, postPurchaseReturn, type PurchaseReturn } from '../purchaseApi';

export default function PurchaseReturnListPage() {
    const [page, setPage] = useState(1);
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const result = useApi((signal) => listPurchaseReturns({ page, search, status, per_page: 15 }, signal), [page, search, status]);
    const run = async (row: PurchaseReturn, action: 'approve' | 'post' | 'cancel') => {
        setActionError(null);
        try {
            if (action === 'approve') await approvePurchaseReturn(row.id);
            if (action === 'post') await postPurchaseReturn(row.id);
            if (action === 'cancel') await cancelPurchaseReturn(row.id);
            result.reload();
        } catch (requestError) {
            setActionError(toApiError(requestError));
        }
    };
    const columns: DataColumn<PurchaseReturn>[] = [
        { key: 'number', header: 'Return', render: (row) => <Link className="font-semibold text-sky-700 hover:underline" to={`/purchase/returns/${row.id}`}>{row.return_number ?? `Return #${row.id}`}</Link> },
        { key: 'date', header: 'Date', render: (row) => formatDate(row.return_date) },
        { key: 'supplier', header: 'Supplier', render: (row) => row.supplier?.name ?? '-' },
        { key: 'type', header: 'Type', render: (row) => row.return_type?.replaceAll('_', ' ') ?? '-' },
        { key: 'total', header: 'Total', render: (row) => formatMoney(row.grand_total) },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
        { key: 'actions', header: 'Actions', render: (row) => <div className="flex gap-2"><Link to={`/purchase/returns/${row.id}`}><Button type="button" variant="ghost">View</Button></Link>{row.status === 'draft' && <Button type="button" variant="secondary" onClick={() => run(row, 'approve')}>Approve</Button>}{(row.status === 'draft' || row.status === 'approved') && <Button type="button" variant="secondary" onClick={() => run(row, 'post')}>Post</Button>}{row.status !== 'posted' && row.status !== 'cancelled' && <Button type="button" variant="ghost" onClick={() => run(row, 'cancel')}>Cancel</Button>}</div> },
    ];
    return (
        <div className="space-y-5">
            <ContentHeader title="Purchase returns" actions={<><Link to="/purchase/returns/create"><Button>New return</Button></Link><Link to="/purchase/manual-supplier-returns/create"><Button variant="secondary">Manual return</Button></Link></>} />
            <ErrorAlert error={result.error ?? actionError} />
            <div className="grid gap-3 md:grid-cols-3">
                <Input label="Search" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
                <Input label="Status" value={status} onChange={(event) => { setStatus(event.target.value); setPage(1); }} />
            </div>
            <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
        </div>
    );
}
