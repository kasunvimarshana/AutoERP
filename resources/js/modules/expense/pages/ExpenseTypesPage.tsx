import { useState } from 'react';
import { useAuth } from '@/modules/auth/AuthProvider';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Modal } from '@/shared/components/Modal';
import { Pagination } from '@/shared/components/Pagination';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import {
    createExpenseType,
    listExpenseTypes,
    updateExpenseType,
    type ExpenseType,
} from '../expenseApi';
import { expensePermissions, hasExpensePermission } from '../expensePermissions';

interface FormState {
    code: string;
    name: string;
    description: string;
}

const emptyForm: FormState = { code: '', name: '', description: '' };

export default function ExpenseTypesPage() {
    const auth = useAuth();
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const [editing, setEditing] = useState<ExpenseType | 'create' | null>(null);
    const [form, setForm] = useState<FormState>(emptyForm);
    const [busy, setBusy] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const debounced = useDebounce(search);
    const result = useApi(
        (signal) => listExpenseTypes({ search: debounced || undefined, page, per_page: 25 }, signal),
        [debounced, page],
    );
    const canManage = hasExpensePermission(auth, expensePermissions.typesManage);

    function openCreate() {
        setActionError(null);
        setForm(emptyForm);
        setEditing('create');
    }

    function openEdit(row: ExpenseType) {
        setActionError(null);
        setForm({ code: row.code, name: row.name, description: row.description ?? '' });
        setEditing(row);
    }

    async function save() {
        if (!editing || form.code.trim() === '' || form.name.trim() === '') return;
        setBusy(true);
        setActionError(null);
        try {
            if (editing === 'create') {
                await createExpenseType({
                    code: form.code,
                    name: form.name,
                    description: form.description.trim() || null,
                    is_active: true,
                });
            } else {
                await updateExpenseType(editing.id, {
                    row_version: editing.row_version,
                    code: form.code,
                    name: form.name,
                    description: form.description.trim() || null,
                    is_active: editing.is_active,
                    sort_order: editing.sort_order,
                });
            }
            setEditing(null);
            result.reload();
        } catch (requestError) {
            setActionError(toApiError(requestError));
        } finally {
            setBusy(false);
        }
    }

    async function toggle(row: ExpenseType) {
        setBusy(true);
        setActionError(null);
        try {
            await updateExpenseType(row.id, {
                row_version: row.row_version,
                code: row.code,
                name: row.name,
                description: row.description,
                is_active: !row.is_active,
                sort_order: row.sort_order,
            });
            result.reload();
        } catch (requestError) {
            setActionError(toApiError(requestError));
        } finally {
            setBusy(false);
        }
    }

    const columns: DataColumn<ExpenseType>[] = [
        { key: 'code', header: 'Code', render: (row) => <span className="font-semibold text-slate-900">{row.code}</span> },
        { key: 'name', header: 'Name', render: (row) => row.name },
        { key: 'description', header: 'Description', render: (row) => row.description || '-' },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.is_active ? 'active' : 'inactive'} /> },
        {
            key: 'actions',
            header: '',
            render: (row) => canManage ? (
                <div className="flex justify-end gap-2">
                    <Button variant="secondary" onClick={() => openEdit(row)}>Edit</Button>
                    <Button variant="ghost" loading={busy} onClick={() => void toggle(row)}>
                        {row.is_active ? 'Deactivate' : 'Activate'}
                    </Button>
                </div>
            ) : null,
        },
    ];

    return (
        <>
            <ContentHeader
                title="Expense types"
                description="Create clear business labels such as Rent, Electricity, Transport, and Salaries."
                actions={canManage ? <Button onClick={openCreate}>Add expense type</Button> : undefined}
            />
            <div className="mb-4 max-w-md">
                <Input type="search" placeholder="Search code or name" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
            </div>
            <ErrorAlert error={actionError ?? result.error} />
            {result.loading
                ? <LoadingState />
                : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} emptyMessage="No expense types configured." />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
            <Modal
                open={editing !== null}
                title={editing === 'create' ? 'Add expense type' : 'Edit expense type'}
                onClose={() => !busy && setEditing(null)}
                closeDisabled={busy}
            >
                <form className="space-y-4" onSubmit={(event) => { event.preventDefault(); void save(); }}>
                    <ErrorAlert error={actionError} />
                    <div className="grid gap-4 md:grid-cols-2">
                        <Input label="Code" value={form.code} onChange={(event) => setForm((current) => ({ ...current, code: event.target.value }))} placeholder="RENT" />
                        <Input label="Name" value={form.name} onChange={(event) => setForm((current) => ({ ...current, name: event.target.value }))} placeholder="Rent" />
                    </div>
                    <Textarea label="Description" value={form.description} onChange={(event) => setForm((current) => ({ ...current, description: event.target.value }))} />
                    <div className="flex justify-end gap-3">
                        <Button variant="secondary" onClick={() => setEditing(null)} disabled={busy}>Cancel</Button>
                        <Button type="submit" loading={busy} disabled={form.code.trim() === '' || form.name.trim() === ''}>Save type</Button>
                    </div>
                </form>
            </Modal>
        </>
    );
}
