import { useState } from 'react';
import { Link } from 'react-router-dom';
import { activatePaymentMethod, deactivatePaymentMethod, deletePaymentMethod, listPaymentMethods, type PaymentMethod } from '../paymentApi';
import { hasPaymentPermission, paymentPermissions } from '../paymentPermissions';
import { useAuth } from '@/modules/auth/AuthProvider';
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
import { humanize } from '@/shared/utils/object';

export default function PaymentMethodListPage() {
    const auth = useAuth();
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const [busyId, setBusyId] = useState<number | null>(null);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const debounced = useDebounce(search);
    const result = useApi((signal) => listPaymentMethods({ search: debounced || undefined, include_overrides: true, page, per_page: 25 }, signal), [debounced, page]);
    const canCreate = hasPaymentPermission(auth.permissions, paymentPermissions.methodsCreate);
    const canUpdate = hasPaymentPermission(auth.permissions, paymentPermissions.methodsUpdate);
    const canDelete = hasPaymentPermission(auth.permissions, paymentPermissions.methodsDelete);

    async function run(row: PaymentMethod, action: 'activate' | 'deactivate' | 'delete') {
        if (busyId) return;
        setBusyId(row.id);
        setActionError(null);
        try {
            if (action === 'activate') await activatePaymentMethod(row.id);
            if (action === 'deactivate') await deactivatePaymentMethod(row.id);
            if (action === 'delete') await deletePaymentMethod(row.id);
            result.reload();
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setBusyId(null);
        }
    }

    const columns: DataColumn<PaymentMethod>[] = [
        { key: 'code', header: 'Code', render: (row) => <Link className="font-semibold text-sky-700 hover:underline" to={`/payments/methods/${row.id}/edit`}>{row.code}</Link> },
        { key: 'name', header: 'Name', render: (row) => row.name },
        { key: 'type', header: 'Type', render: (row) => humanize(row.method_type) },
        { key: 'direction', header: 'Direction', render: (row) => humanize(row.direction_allowed) },
        { key: 'active', header: 'Status', render: (row) => <StatusBadge status={row.is_active === false ? 'inactive' : 'active'} /> },
        { key: 'actions', header: '', render: (row) => <div className="flex justify-end gap-2">
            {canUpdate && <LinkButton to={`/payments/methods/${row.id}/edit`} variant="secondary">Edit</LinkButton>}
            {canUpdate && <Button variant="ghost" loading={busyId === row.id} onClick={() => void run(row, row.is_active === false ? 'activate' : 'deactivate')}>{row.is_active === false ? 'Activate' : 'Deactivate'}</Button>}
            {canDelete && <Button variant="danger" loading={busyId === row.id} onClick={() => void run(row, 'delete')}>Delete</Button>}
        </div> },
    ];

    return <>
        <ContentHeader title="Payment Methods" actions={canCreate ? <LinkButton to="/payments/methods/create">Create Payment Method</LinkButton> : undefined} />
        <div className="mb-4 max-w-md"><Input type="search" placeholder="Search code or name" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} /></div>
        <ErrorAlert error={actionError ?? result.error} />
        {result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} emptyMessage="No payment methods found." />}
        <Pagination meta={result.data?.meta} onPageChange={setPage} />
    </>;
}