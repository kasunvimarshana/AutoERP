import { useState } from 'react';
import { Link } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Modal } from '@/shared/components/Modal';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { changeCustomerStatus, listCustomers, setCustomerActive } from './customerApi';
import { customerStatuses, type CustomerSummary } from './customerTypes';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';
import { notifySuccess } from '@/shared/notifications/appToast';

const options = (values: readonly string[]) => values.map((value) => ({ value, label: value.replaceAll('_', ' ') }));
export default function CustomerListPage() {
    const auth = useAuth(); const canUpdate = hasPermission(auth, 'customers.update'); const canCreate = hasPermission(auth, 'customers.create');
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const [statusCustomer, setStatusCustomer] = useState<CustomerSummary | null>(null);
    const [nextStatus, setNextStatus] = useState('on_hold');
    const [reason, setReason] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const debounced = useDebounce(search);
    const result = useApi((signal) => listCustomers({
        search: debounced || undefined,
        page,
        per_page: 25,
    }, signal), [debounced, page]);
    const customers = result.data;

    const columns: DataColumn<CustomerSummary>[] = [
        { key: 'customer', header: 'Customer', render: (row) => <Link className="font-semibold text-sky-700 hover:underline" to={`/customers/${row.id}`}>{row.name}<span className="block text-xs font-normal text-slate-500">{row.code} / {row.customer_number}</span></Link> },
        { key: 'contact', header: 'Contact', render: (row) => row.email ?? row.phone ?? '-' },
        { key: 'vehicles', header: 'Current Vehicles', render: (row) => row.current_vehicles?.some((vehicle) => vehicle.registration_number) ? <div className="flex flex-wrap gap-1">{row.current_vehicles.filter((vehicle) => vehicle.registration_number).map((vehicle) => <Link key={vehicle.id} className="rounded bg-slate-100 px-2 py-1 text-xs font-semibold text-sky-700 hover:bg-sky-50" to={`/vehicles/${vehicle.id}`}>{vehicle.registration_number}</Link>)}</div> : '-' },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
        { key: 'actions', header: '', className: 'text-right', render: (row) => canUpdate ? <div className="flex justify-end gap-3"><Link className="font-semibold text-slate-600 hover:text-sky-700" to={`/customers/${row.id}/edit`}>Edit</Link><button type="button" className="font-semibold text-amber-700" onClick={() => void toggle(row)}>{row.status === 'active' ? 'Deactivate' : 'Activate'}</button><button type="button" className="font-semibold text-sky-700" onClick={() => { setStatusCustomer(row); setNextStatus(row.status === 'active' ? 'on_hold' : 'active'); setReason(''); }}>Change status</button></div> : null },
    ];
    async function toggle(row: CustomerSummary) {
        setActionError(null);
        try {
            const updated = await setCustomerActive(Number(row.id), row.status !== 'active');
            result.setData((current) => updateCustomerCollection(current, updated));
            notifySuccess(updated.status === 'active' ? 'Customer activated successfully.' : 'Customer deactivated successfully.');
        }
        catch (error) { setActionError(toApiError(error)); }
    }
    async function saveStatus() {
        if (!statusCustomer) return;
        setSubmitting(true); setActionError(null);
        try {
            const updated = await changeCustomerStatus(Number(statusCustomer.id), nextStatus, reason || undefined);
            setStatusCustomer(null);
            result.setData((current) => updateCustomerCollection(current, updated));
            notifySuccess('Customer status updated successfully.');
        }
        catch (error) { setActionError(toApiError(error)); }
        finally { setSubmitting(false); }
    }
    return <><ContentHeader title="Customers" description="Customer identity, compliance, status, and credit policy." actions={canCreate ? <LinkButton to="/customers/create">New customer</LinkButton> : undefined} />
        <div className="mb-5 max-w-xl"><Input type="search" label="Search" placeholder="Customer number, name, contact, or vehicle number" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} /></div>
        <ErrorAlert error={actionError ?? result.error} />{result.loading && !customers ? <LoadingState /> : <DataTable rows={customers?.data ?? []} columns={columns} rowKey={(row) => row.id} />}<Pagination meta={customers?.meta} onPageChange={setPage} />
        <Modal open={Boolean(statusCustomer)} title="Change customer status" onClose={() => !submitting && setStatusCustomer(null)}><div className="space-y-4"><ErrorAlert error={actionError} /><Select label="New status" value={nextStatus} onChange={(event) => setNextStatus(event.target.value)} options={options(customerStatuses)} /><Input label="Reason" value={reason} onChange={(event) => setReason(event.target.value)} /><div className="flex justify-end gap-2"><Button variant="secondary" onClick={() => setStatusCustomer(null)}>Cancel</Button><Button loading={submitting} onClick={() => void saveStatus()}>Change status</Button></div></div></Modal>
    </>;
}

function updateCustomerCollection(
    collection: Awaited<ReturnType<typeof listCustomers>> | null,
    updated: CustomerSummary,
) {
    if (collection === null) return collection;

    const rows = collection.data ?? [];
    const currentIndex = rows.findIndex((row) => row.id === updated.id);
    if (currentIndex === -1) return collection;

    return {
        ...collection,
        data: rows.map((row) => row.id === updated.id ? { ...row, ...updated } : row),
    };
}
