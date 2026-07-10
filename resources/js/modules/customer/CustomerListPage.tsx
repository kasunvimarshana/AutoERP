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
import { customerStatuses, customerTypes, type CustomerCategory, type CustomerSummary } from './customerTypes';
import { CustomerCategorySelect } from './components/CustomerCategorySelect';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';

const options = (values: readonly string[]) => values.map((value) => ({ value, label: value.replaceAll('_', ' ') }));
export default function CustomerListPage() {
    const auth = useAuth(); const canUpdate = hasPermission(auth, 'customers.update'); const canCreate = hasPermission(auth, 'customers.create');
    const [search, setSearch] = useState('');
    const [type, setType] = useState('');
    const [status, setStatus] = useState('');
    const [category, setCategory] = useState<CustomerCategory | null>(null);
    const [creditAllowed, setCreditAllowed] = useState('');
    const [page, setPage] = useState(1);
    const [sort, setSort] = useState('name'); const [direction, setDirection] = useState('asc');
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const [statusCustomer, setStatusCustomer] = useState<CustomerSummary | null>(null);
    const [nextStatus, setNextStatus] = useState('on_hold');
    const [reason, setReason] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const debounced = useDebounce(search);
    const result = useApi((signal) => listCustomers({
        search: debounced || undefined,
        customer_type: type || undefined,
        status: status || undefined,
        category_id: category?.id,
        credit_allowed: creditAllowed === '' ? undefined : creditAllowed === 'true',
        page,
        per_page: 25,
        sort, direction,
    }, signal), [debounced, type, status, category?.id, creditAllowed, page, sort, direction]);

    const columns: DataColumn<CustomerSummary>[] = [
        { key: 'customer', header: 'Customer', render: (row) => <Link className="font-semibold text-sky-700 hover:underline" to={`/customers/${row.id}`}>{row.name}<span className="block text-xs font-normal text-slate-500">{row.code} / {row.customer_number}</span></Link> },
        { key: 'type', header: 'Type', render: (row) => row.customer_type.replaceAll('_', ' ') },
        { key: 'category', header: 'Categories', render: (row) => row.categories?.map((entry) => entry.name).join(', ') || '-' },
        { key: 'contact', header: 'Contact', render: (row) => row.email ?? row.phone ?? '-' },
        { key: 'credit', header: 'Credit', render: (row) => row.credit_allowed ? 'Allowed' : 'Not allowed' },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
        { key: 'actions', header: '', className: 'text-right', render: (row) => canUpdate ? <div className="flex justify-end gap-3"><Link className="font-semibold text-slate-600 hover:text-sky-700" to={`/customers/${row.id}/edit`}>Edit</Link><button type="button" className="font-semibold text-amber-700" onClick={() => void toggle(row)}>{row.status === 'active' ? 'Deactivate' : 'Activate'}</button><button type="button" className="font-semibold text-sky-700" onClick={() => { setStatusCustomer(row); setNextStatus(row.status === 'active' ? 'on_hold' : 'active'); setReason(''); }}>Change status</button></div> : null },
    ];
    async function toggle(row: CustomerSummary) {
        setActionError(null);
        try { await setCustomerActive(Number(row.id), row.status !== 'active'); result.reload(); }
        catch (error) { setActionError(toApiError(error)); }
    }
    async function saveStatus() {
        if (!statusCustomer) return;
        setSubmitting(true); setActionError(null);
        try { await changeCustomerStatus(Number(statusCustomer.id), nextStatus, reason || undefined); setStatusCustomer(null); result.reload(); }
        catch (error) { setActionError(toApiError(error)); }
        finally { setSubmitting(false); }
    }
    return <><ContentHeader title="Customers" description="Customer identity, compliance, status, and credit policy." actions={canCreate ? <LinkButton to="/customers/create">New customer</LinkButton> : undefined} />
        <div className="mb-5 grid gap-4 md:grid-cols-2 xl:grid-cols-7"><Input type="search" label="Search" placeholder="Number, code, name, email, phone" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} /><Select label="Customer type" value={type} onChange={(event) => { setType(event.target.value); setPage(1); }} options={options(customerTypes)} /><Select label="Status" value={status} onChange={(event) => { setStatus(event.target.value); setPage(1); }} options={options(customerStatuses)} /><CustomerCategorySelect value={category} onChange={(value) => { setCategory(value); setPage(1); }} /><Select label="Credit allowed" value={creditAllowed} onChange={(event) => { setCreditAllowed(event.target.value); setPage(1); }} options={[{ value: 'true', label: 'Yes' }, { value: 'false', label: 'No' }]} /><Select label="Sort" value={sort} onChange={(e)=>setSort(e.target.value)} options={[{value:'name',label:'Name'},{value:'customer_number',label:'Number'},{value:'code',label:'Code'},{value:'created_at',label:'Created'}]}/><Select label="Direction" value={direction} onChange={(e)=>setDirection(e.target.value)} options={[{value:'asc',label:'Ascending'},{value:'desc',label:'Descending'}]}/></div>
        <ErrorAlert error={actionError ?? result.error} />{result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}<Pagination meta={result.data?.meta} onPageChange={setPage} />
        <Modal open={Boolean(statusCustomer)} title="Change customer status" onClose={() => !submitting && setStatusCustomer(null)}><div className="space-y-4"><ErrorAlert error={actionError} /><Select label="New status" value={nextStatus} onChange={(event) => setNextStatus(event.target.value)} options={options(customerStatuses)} /><Input label="Reason" value={reason} onChange={(event) => setReason(event.target.value)} /><div className="flex justify-end gap-2"><Button variant="secondary" onClick={() => setStatusCustomer(null)}>Cancel</Button><Button loading={submitting} onClick={() => void saveStatus()}>Change status</Button></div></div></Modal>
    </>;
}
