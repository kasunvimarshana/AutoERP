import { useState } from 'react';
import { Link } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { ReversalDialog, type ReversalFacts } from '@/shared/components/ReversalDialog';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { useAuth } from '@/modules/auth/AuthProvider';
import { formatDate } from '@/shared/utils/formatDate';
import { formatMoney } from '@/shared/utils/formatMoney';
import { listGoodsReceipts, postGoodsReceipt, reverseGoodsReceipt, type GoodsReceipt } from '../purchaseApi';
import { hasPurchasePermission, purchasePermissions } from '../purchasePermissions';

const goodsReceiptStatusOptions = [
    { value: 'draft', label: 'Draft' },
    { value: 'posted', label: 'Posted' },
    { value: 'reversed', label: 'Reversed' },
];

export default function GoodsReceiptListPage() {
    const { confirm, confirmDialog } = useConfirmDialog();
    const auth = useAuth();
    const [page, setPage] = useState(1);
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const debouncedSearch = useDebounce(search);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const [busyId, setBusyId] = useState<number | null>(null);
    const [reversalTarget, setReversalTarget] = useState<GoodsReceipt | null>(null);
    const result = useApi((signal) => listGoodsReceipts({ page, search: debouncedSearch || undefined, status: status || undefined, per_page: 15 }, signal), [page, debouncedSearch, status]);
    const can = (permission: string) => hasPurchasePermission(auth, permission);

    const post = async (row: GoodsReceipt) => {
        if (busyId !== null) return;
        if (!await confirm({
            title: 'Post goods receipt',
            message: 'Confirm post for this goods receipt?',
            confirmLabel: 'Post',
        })) return;

        setBusyId(row.id);
        setActionError(null);
        try {
            await postGoodsReceipt(row.id, { expected_version: row.row_version });
            result.reload();
        } catch (requestError) {
            setActionError(toApiError(requestError));
        } finally {
            setBusyId(null);
        }
    };

    const reverse = async (facts: ReversalFacts) => {
        const row = reversalTarget;
        if (!row || busyId !== null) return;

        setBusyId(row.id);
        setActionError(null);
        try {
            await reverseGoodsReceipt(row.id, {
                expected_version: row.row_version,
                ...facts,
            });
            setReversalTarget(null);
            result.reload();
        } catch (requestError) {
            setActionError(toApiError(requestError));
        } finally {
            setBusyId(null);
        }
    };

    const columns: DataColumn<GoodsReceipt>[] = [
        { key: 'number', header: 'GRN', render: (row) => <Link className="font-semibold text-sky-700 hover:underline" to={`/purchase/goods-receipts/${row.id}`}>{row.grn_number ?? 'Goods receipt'}</Link> },
        { key: 'date', header: 'Date', render: (row) => formatDate(row.received_date) },
        { key: 'supplier', header: 'Supplier', render: (row) => row.supplier?.name ?? '-' },
        { key: 'po', header: 'PO', render: (row) => row.purchase_order?.purchase_order_number ?? row.purchase_order?.name ?? '-' },
        { key: 'total', header: 'Total', render: (row) => formatMoney(row.grand_total) },
        { key: 'workflow', header: 'Workflow', render: (row) => <StatusBadge status={row.workflow_status ?? row.status} /> },
        { key: 'invoice', header: 'Invoice', render: (row) => row.invoice_status?.replaceAll('_', ' ') ?? '-' },
        { key: 'return', header: 'Return', render: (row) => row.return_status?.replaceAll('_', ' ') ?? '-' },
        { key: 'actions', header: 'Actions', render: (row) => {
            const capabilities = row.capabilities ?? {};
            return <div className="flex gap-2"><LinkButton to={`/purchase/goods-receipts/${row.id}`} variant="ghost">View</LinkButton>{capabilities.can_post && can(purchasePermissions.goodsReceiptsPost) && <Button type="button" variant="secondary" loading={busyId === row.id} onClick={() => void post(row)}>Post</Button>}{capabilities.can_reverse && can(purchasePermissions.goodsReceiptsReverse) && <Button type="button" variant="secondary" loading={busyId === row.id} onClick={() => setReversalTarget(row)}>Reverse</Button>}</div>;
        } },
    ];

    return (
        <>
            <div className="space-y-5">
                <ContentHeader title="Goods receipts" actions={can(purchasePermissions.goodsReceiptsCreate) ? <LinkButton to="/purchase/goods-receipts/create">New GRN</LinkButton> : undefined} />
                <ErrorAlert error={result.error ?? actionError} />
                <div className="grid gap-3 md:grid-cols-3">
                    <Input label="Search" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
                    <Select label="Status" value={status} options={goodsReceiptStatusOptions} onChange={(event) => { setStatus(event.target.value); setPage(1); }} />
                </div>
                {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}
                <Pagination meta={result.data?.meta} onPageChange={setPage} />
            </div>
            {confirmDialog}
            <ReversalDialog
                open={reversalTarget !== null}
                title="Reverse goods receipt"
                loading={reversalTarget !== null && busyId === reversalTarget.id}
                onCancel={() => setReversalTarget(null)}
                onConfirm={reverse}
            />
        </>
    );
}
