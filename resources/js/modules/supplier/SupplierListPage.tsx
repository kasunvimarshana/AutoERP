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
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { changeSupplierStatus, listSuppliers, setSupplierActive } from './supplierApi';
import { supplierStatuses, type SupplierSummary } from './supplierTypes';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';

const options = (values: readonly string[]) => values.map((value) => ({ value, label: value.replaceAll('_', ' ') }));
export default function SupplierListPage() {
    const auth = useAuth(); const canUpdate = hasPermission(auth, 'suppliers.update'); const canCreate = hasPermission(auth, 'suppliers.create');
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const [statusSupplier, setStatusSupplier] = useState<SupplierSummary | null>(null);
    const [nextStatus, setNextStatus] = useState('on_hold');
    const [reason, setReason] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const debounced = useDebounce(search);
    const result = useApi((signal) => listSuppliers({
        search: debounced || undefined,
        page,
        per_page: 25,
    }, signal), [debounced, page]);

    const columns: DataColumn<SupplierSummary>[] = [
        { key: 'supplier', header: 'Supplier', render: (row) => <Link className="font-semibold text-sky-700 hover:underline" to={`/suppliers/${row.id}`}>{row.name}<span className="block text-xs font-normal text-slate-500">{row.code} / {row.supplier_number}</span></Link> },
        { key: 'type', header: 'Type', render: (row) => row.supplier_type.replaceAll('_', ' ') },
        { key: 'contact', header: 'Contact', render: (row) => row.email ?? row.phone ?? '-' },
        { key: 'total_due', header: 'Total Due', render: (row) => <div className="space-y-1">{(row.total_due ?? []).map((total, index) => <div key={`${total.currency_code ?? 'default'}-${index}`}><MoneyDisplay value={total.amount} currency={total.currency_code ?? row.default_currency?.code ?? undefined} /></div>)}</div> },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.status} /> },
        { key: 'actions', header: '', className: 'text-right', render: (row) => canUpdate ? <div className="flex justify-end gap-3"><Link className="font-semibold text-slate-600 hover:text-sky-700" to={`/suppliers/${row.id}/edit`}>Edit</Link><button type="button" className="font-semibold text-amber-700" onClick={() => void toggle(row)}>{row.status === 'active' ? 'Deactivate' : 'Activate'}</button><button type="button" className="font-semibold text-sky-700" onClick={() => { setStatusSupplier(row); setNextStatus(row.status === 'active' ? 'on_hold' : 'active'); setReason(''); }}>Change status</button></div> : null },
    ];
    async function toggle(row: SupplierSummary) {
        setActionError(null);
        try { await setSupplierActive(Number(row.id), row.status !== 'active'); result.reload(); }
        catch (error) { setActionError(toApiError(error)); }
    }
    async function saveStatus() {
        if (!statusSupplier) return;
        setSubmitting(true); setActionError(null);
        try { await changeSupplierStatus(Number(statusSupplier.id), nextStatus, reason || undefined); setStatusSupplier(null); result.reload(); }
        catch (error) { setActionError(toApiError(error)); }
        finally { setSubmitting(false); }
    }
    return <><ContentHeader title="Suppliers" description="Supplier identity, compliance, item references, and credit policy." actions={canCreate ? <LinkButton to="/suppliers/create">New supplier</LinkButton> : undefined} />
        <div className="mb-5 max-w-xl"><Input type="search" label="Search" placeholder="Number, code, name, email, phone" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} /></div>
        <ErrorAlert error={actionError ?? result.error} />{result.loading ? <LoadingState /> : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}<Pagination meta={result.data?.meta} onPageChange={setPage} />
        <Modal open={Boolean(statusSupplier)} title="Change supplier status" onClose={() => !submitting && setStatusSupplier(null)}><div className="space-y-4"><ErrorAlert error={actionError} /><Select label="New status" value={nextStatus} onChange={(event) => setNextStatus(event.target.value)} options={options(supplierStatuses)} /><Input label="Reason" value={reason} onChange={(event) => setReason(event.target.value)} /><div className="flex justify-end gap-2"><Button variant="secondary" onClick={() => setStatusSupplier(null)}>Cancel</Button><Button loading={submitting} onClick={() => void saveStatus()}>Change status</Button></div></div></Modal>
    </>;
}
