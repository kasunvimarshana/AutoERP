import { useState } from 'react';
import { Link } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { formatDate } from '@/shared/utils/formatDate';
import { formatMoney } from '@/shared/utils/formatMoney';
import { listGoodsReceipts, postGoodsReceipt, reverseGoodsReceipt, type GoodsReceipt } from '../purchaseApi';

export default function GoodsReceiptListPage() {
    const [page, setPage] = useState(1);
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const debouncedSearch = useDebounce(search);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const result = useApi((signal) => listGoodsReceipts({ page, search: debouncedSearch || undefined, status: status || undefined, per_page: 15 }, signal), [page, debouncedSearch, status]);

    const run = async (row: GoodsReceipt, action: 'post' | 'reverse') => {
        setActionError(null);
        try {
            if (action === 'post') await postGoodsReceipt(row.id);
            if (action === 'reverse') await reverseGoodsReceipt(row.id);
            result.reload();
        } catch (requestError) {
            setActionError(toApiError(requestError));
        }
    };

    const columns: DataColumn<GoodsReceipt>[] = [
        { key: 'number', header: 'GRN', render: (row) => <Link className="font-semibold text-sky-700 hover:underline" to={`/purchase/goods-receipts/${row.id}`}>{row.grn_number ?? 'Goods receipt'}</Link> },
        { key: 'date', header: 'Date', render: (row) => formatDate(row.received_date) },
        { key: 'supplier', header: 'Supplier', render: (row) => row.supplier?.name ?? '-' },
        { key: 'po', header: 'PO', render: (row) => row.purchase_order?.purchase_order_number ?? row.purchase_order?.name ?? '-' },
        { key: 'total', header: 'Total', render: (row) => formatMoney(row.grand_total) },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
        { key: 'actions', header: 'Actions', render: (row) => <div className="flex gap-2"><Link to={`/purchase/goods-receipts/${row.id}`}><Button type="button" variant="ghost">View</Button></Link>{row.status === 'draft' && <Button type="button" variant="secondary" onClick={() => run(row, 'post')}>Post</Button>}{row.status === 'posted' && <Button type="button" variant="secondary" onClick={() => run(row, 'reverse')}>Reverse</Button>}</div> },
    ];

    return (
        <div className="space-y-5">
            <ContentHeader title="Goods receipts" actions={<Link to="/purchase/goods-receipts/create"><Button>New GRN</Button></Link>} />
            <ErrorAlert error={result.error ?? actionError} />
            <div className="grid gap-3 md:grid-cols-3">
                <Input label="Search" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
                <Input label="Status" value={status} onChange={(event) => { setStatus(event.target.value); setPage(1); }} />
            </div>
            {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
        </div>
    );
}
