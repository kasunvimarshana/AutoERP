import { useMemo, useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Modal } from '@/shared/components/Modal';
import { Pagination } from '@/shared/components/Pagination';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { useAuth } from '@/modules/auth/AuthProvider';
import {
    createAccessRecord,
    listAccessRecords,
    type AccessEntityKind,
    type AccessRecord,
    type CreateAccessRecordPayload,
} from './accessApi';

const pageDetails: Record<AccessEntityKind, { title: string; description: string; createLabel: string }> = {
    users: { title: 'User List', description: 'Tenant users, identity details, and account status.', createLabel: 'New user' },
    roles: { title: 'Roles', description: 'Reusable tenant roles for assigning responsibility.', createLabel: 'New role' },
    permissions: { title: 'Permissions', description: 'Module permission keys used by backend authorization.', createLabel: 'New permission' },
};

export default function AccessListPage({ kind }: { kind: AccessEntityKind }) {
    const auth = useAuth();
    const tenantId = Number(auth.tenant?.id);
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const [createOpen, setCreateOpen] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const debounced = useDebounce(search);
    const result = useApi(
        (signal) => listAccessRecords(kind, {
            tenant_id: tenantId,
            search: debounced || undefined,
            page,
            per_page: 25,
        }, signal),
        [debounced, kind, page, tenantId],
    );
    const details = pageDetails[kind];
    const columns = useMemo(() => columnsFor(kind), [kind]);

    const create = async (payload: Record<string, unknown>) => {
        setSubmitting(true);
        setActionError(null);
        try {
            await createAccessRecord(kind, { ...payload, tenant_id: tenantId } as CreateAccessRecordPayload);
            setCreateOpen(false);
            result.reload();
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <>
            <ContentHeader
                title={details.title}
                description={details.description}
                actions={<Button onClick={() => { setActionError(null); setCreateOpen(true); }}>{details.createLabel}</Button>}
            />
            <div className="mb-5 max-w-md">
                <Input label="Search" type="search" value={search} placeholder={`Search ${details.title.toLowerCase()}`} onChange={(event) => { setSearch(event.target.value); setPage(1); }} />
            </div>
            <ErrorAlert error={actionError ?? result.error} />
            {result.loading
                ? <LoadingState label={`Loading ${details.title.toLowerCase()}...`} />
                : <DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} />}
            <Pagination meta={result.data?.meta} onPageChange={setPage} />
            <Modal open={createOpen} title={details.createLabel} onClose={() => !submitting && setCreateOpen(false)}>
                <AccessCreateForm kind={kind} submitting={submitting} error={actionError} onSubmit={create} onCancel={() => setCreateOpen(false)} />
            </Modal>
        </>
    );
}

function columnsFor(kind: AccessEntityKind): DataColumn<AccessRecord>[] {
    if (kind === 'users') {
        return [
            { key: 'name', header: 'User', render: (row) => <div><p className="font-semibold text-slate-900">{[row.first_name, row.last_name].filter(Boolean).join(' ') || row.username || 'User'}</p><p className="text-xs text-slate-500">{row.email ?? '-'}</p></div> },
            { key: 'username', header: 'Username', render: (row) => row.username ?? '-' },
            { key: 'phone', header: 'Phone', render: (row) => row.phone ?? '-' },
            { key: 'status', header: 'Status', render: (row) => row.status?.replaceAll('_', ' ') ?? '-' },
        ];
    }
    if (kind === 'roles') {
        return [
            { key: 'name', header: 'Role', render: (row) => <span className="font-semibold text-slate-900">{row.name ?? '-'}</span> },
            { key: 'guard', header: 'Guard', render: (row) => row.guard_name ?? '-' },
            { key: 'description', header: 'Description', render: (row) => row.description ?? '-' },
        ];
    }
    return [
        { key: 'name', header: 'Permission', render: (row) => <span className="font-mono text-xs font-semibold text-slate-900">{row.name ?? '-'}</span> },
        { key: 'module', header: 'Module', render: (row) => row.module ?? '-' },
        { key: 'guard', header: 'Guard', render: (row) => row.guard_name ?? '-' },
        { key: 'description', header: 'Description', render: (row) => row.description ?? '-' },
    ];
}

function AccessCreateForm({
    kind,
    submitting,
    error,
    onSubmit,
    onCancel,
}: {
    kind: AccessEntityKind;
    submitting: boolean;
    error: ApiError | null;
    onSubmit: (payload: Record<string, unknown>) => Promise<void>;
    onCancel: () => void;
}) {
    const [form, setForm] = useState<Record<string, string>>((): Record<string, string> => {
        if (kind === 'users') {
            return { first_name: '', last_name: '', username: '', email: '', password: '', phone: '', status: 'active' };
        }
        return { name: '', guard_name: 'web', module: '', description: '' };
    });

    return (
        <form className="space-y-4" onSubmit={(event) => { event.preventDefault(); void onSubmit(form); }}>
            <ErrorAlert error={error} />
            {kind === 'users' ? (
                <>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Input label="First name" required value={form.first_name} onChange={(event) => setForm({ ...form, first_name: event.target.value })} />
                        <Input label="Last name" value={form.last_name} onChange={(event) => setForm({ ...form, last_name: event.target.value })} />
                    </div>
                    <Input label="Email" type="email" required value={form.email} onChange={(event) => setForm({ ...form, email: event.target.value })} />
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Input label="Username" value={form.username} onChange={(event) => setForm({ ...form, username: event.target.value })} />
                        <Input label="Phone" value={form.phone} onChange={(event) => setForm({ ...form, phone: event.target.value })} />
                    </div>
                    <Input label="Temporary password" type="password" required value={form.password} onChange={(event) => setForm({ ...form, password: event.target.value })} />
                    <Select label="Status" value={form.status} options={['active', 'inactive', 'suspended'].map((value) => ({ value, label: value }))} onChange={(event) => setForm({ ...form, status: event.target.value })} />
                </>
            ) : (
                <>
                    <Input label={kind === 'roles' ? 'Role name' : 'Permission key'} required value={form.name} onChange={(event) => setForm({ ...form, name: event.target.value })} />
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Input label="Guard" value={form.guard_name} onChange={(event) => setForm({ ...form, guard_name: event.target.value })} />
                        {kind === 'permissions' && <Input label="Module" value={form.module} onChange={(event) => setForm({ ...form, module: event.target.value })} />}
                    </div>
                    <Textarea label="Description" value={form.description} onChange={(event) => setForm({ ...form, description: event.target.value })} />
                </>
            )}
            <div className="flex justify-end gap-2 pt-2">
                <Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button>
                <Button type="submit" loading={submitting}>Create</Button>
            </div>
        </form>
    );
}
