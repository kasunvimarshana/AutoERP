import { useState } from 'react';
import { Link } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
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
import { approvePurchaseReturn, cancelPurchaseReturn, listPurchaseReturns, postPurchaseReturn, type PurchaseReturn } from '../purchaseApi';
import { hasPurchasePermission, purchasePermissions } from '../purchasePermissions';
import { useAuth } from '@/modules/auth/AuthProvider';

export default function PurchaseReturnListPage() {
    const auth = useAuth();
    const [page, setPage] = useState(1);
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const debouncedSearch = useDebounce(search);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const result = useApi((signal) => listPurchaseReturns({ page, search: debouncedSearch || undefined, status: status || undefined, per_page: 15 }, signal), [page, debouncedSearch, status]);
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
        { key: 'number', header: 'Return', render: (row) => <Link className="font-semibold text-sky-700 hover:underline" to={`/purchase/returns/${row.id}`}>{row.return_number ?? 'Purchase return'}</Link> },
        { key: 'date', header: 'Date', render: (row) => formatDate(row.return_date) },
        { key: 'supplier', header: 'Supplier', render: (row) => row.supplier?.name ?? '-' },
        { key: 'type', header: 'Type', render: (row) => row.return_type?.replaceAll('_', ' ') ?? '-' },
        { key: 'total', header: 'Total', render: (row) => formatMoney(row.grand_total) },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
        { key: 'actions', header: 'Actions', render: (row) => {
            const capabilities = row.capabilities ?? {};
            return <div className="flex gap-2"><LinkButton to={`/purchase/returns/${row.id}`} variant="ghost">View</LinkButton>{capabilities.can_approve && hasPurchasePermission(auth.permissions, purchasePermissions.returnsApprove) && <Button type="button" variant="secondary" onClick={() => run(row, 'approve')}>Approve</Button>}{capabilities.can_post && hasPurchasePermission(auth.permissions, purchasePermissions.returnsPost) && <Button type="button" variant="secondary" onClick={() => run(row, 'post')}>Post</Button>}{capabilities.can_cancel && hasPurchasePermission(auth.permissions, purchasePermissions.returnsCancel) && <Button type="button" variant="ghost" onClick={() => run(row, 'cancel')}>Cancel</Button>}</div>;
        } },
    ];
    return (
        <div className="space-y-5">
            <ContentHeader title="Purchase Returns" actions={<>{hasPurchasePermission(auth.permissions, purchasePermissions.returnsCreate) && <LinkButton to="/purchase/returns/create">Create Purchase Return</LinkButton>}{hasPurchasePermission(auth.permissions, purchasePermissions.returnsCreateManual) && <LinkButton to="/purchase/manual-supplier-returns/create" variant="secondary">Manual Return</LinkButton>}</>} />
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
