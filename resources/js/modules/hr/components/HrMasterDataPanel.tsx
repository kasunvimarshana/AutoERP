import { useState } from 'react';
import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
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
import { notifySuccess } from '@/shared/notifications/appToast';
import { HrDepartmentSelect } from './HrDepartmentSelect';
import { createHrMaster, deleteHrMaster, listHrMaster, updateHrMaster, type HrMasterKind, type HrMasterPayload } from '../hrMasterApi';
import { hrPermissions } from '../hrPermissions';
import type { HrDepartment, HrMaster } from '../hrTypes';

const PAGE_SIZE = 25;
const DEFAULT_SORT_ORDER = 0;

const copy: Record<HrMasterKind, { title: string; singular: string; description: string }> = {
    departments: { title: 'Departments', singular: 'Department', description: 'Organize employees into clear reporting and operational groups.' },
    designations: { title: 'Designations', singular: 'Designation', description: 'Maintain employee job titles used in assignments and reporting.' },
    'employment-types': { title: 'Employment Types', singular: 'Employment Type', description: 'Define employment arrangements such as permanent, contract, or temporary.' },
    skills: { title: 'Skills', singular: 'Skill', description: 'Maintain workforce skills used for service-job matching and capability reporting.' },
    certifications: { title: 'Certifications', singular: 'Certification', description: 'Maintain recognized employee certification types.' },
    licenses: { title: 'Licenses', singular: 'License', description: 'Maintain employee license types and compliance references.' },
};

const emptyPayload = (): HrMasterPayload => ({
    code: '',
    name: '',
    description: '',
    is_active: true,
    sort_order: DEFAULT_SORT_ORDER,
    parent_id: null,
});

export function HrMasterDataPanel({ kind }: { kind: HrMasterKind }) {
    const auth = useAuth();
    const canManage = hasPermission(auth, hrPermissions.masterDataManage);
    const config = copy[kind];
    const [search, setSearch] = useState('');
    const [page, setPage] = useState(1);
    const [editing, setEditing] = useState<HrMaster | null>(null);
    const [draft, setDraft] = useState<HrMasterPayload>(emptyPayload);
    const [parent, setParent] = useState<HrDepartment | null>(null);
    const [open, setOpen] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const debouncedSearch = useDebounce(search);
    const { confirm, confirmDialog } = useConfirmDialog();
    const result = useApi((signal) => listHrMaster(kind, {
        search: debouncedSearch || undefined,
        page,
        per_page: PAGE_SIZE,
    }, signal), [debouncedSearch, kind, page]);

    const openCreate = () => {
        setEditing(null);
        setDraft(emptyPayload());
        setParent(null);
        setActionError(null);
        setOpen(true);
    };

    const openEdit = (row: HrMaster) => {
        const department = row as HrDepartment;
        setEditing(row);
        setDraft({
            code: row.code ?? '',
            name: row.name,
            description: row.description ?? '',
            is_active: row.is_active,
            sort_order: row.sort_order ?? DEFAULT_SORT_ORDER,
            parent_id: department.parent?.id ?? department.parent_id ?? null,
        });
        setParent(department.parent ?? null);
        setActionError(null);
        setOpen(true);
    };

    const save = async () => {
        if (!canManage || submitting) return;
        setSubmitting(true);
        setActionError(null);
        try {
            const payload = {
                ...draft,
                code: draft.code.trim(),
                name: draft.name.trim(),
                description: draft.description?.trim() || null,
                parent_id: kind === 'departments' ? parent?.id ?? null : undefined,
            };
            if (editing) {
                await updateHrMaster(kind, editing.id, payload);
            } else {
                await createHrMaster(kind, payload);
            }
            setOpen(false);
            result.reload();
            notifySuccess(`${config.singular} ${editing ? 'updated' : 'created'} successfully.`);
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setSubmitting(false);
        }
    };

    const remove = async (row: HrMaster) => {
        if (!canManage || !await confirm({
            title: `Delete ${config.singular.toLowerCase()}?`,
            message: `Delete ${row.name}? This is only allowed when it is not referenced by HR records.`,
            confirmLabel: 'Delete',
            danger: true,
        })) return;

        setActionError(null);
        try {
            await deleteHrMaster(kind, row.id);
            result.reload();
            notifySuccess(`${config.singular} deleted successfully.`);
        } catch (error) {
            setActionError(toApiError(error));
        }
    };

    const columns: DataColumn<HrMaster>[] = [
        { key: 'code', header: 'Code', render: (row) => row.code ?? '-' },
        { key: 'name', header: 'Name', render: (row) => row.name },
        ...(kind === 'departments' ? [{ key: 'parent', header: 'Parent', render: (row: HrMaster) => (row as HrDepartment).parent?.name ?? '-' }] : []),
        { key: 'description', header: 'Description', render: (row) => row.description ?? '-' },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.is_active ? 'active' : 'inactive'} /> },
        ...(canManage ? [{
            key: 'actions',
            header: '',
            className: 'text-right',
            render: (row: HrMaster) => <div className="flex justify-end gap-2"><Button variant="ghost" onClick={() => openEdit(row)}>Edit</Button><Button variant="danger" onClick={() => void remove(row)}>Delete</Button></div>,
        }] : []),
    ];

    return <>
        <ContentHeader title={config.title} description={config.description} actions={canManage ? <Button onClick={openCreate}>Add {config.singular}</Button> : undefined} />
        <div className="mb-4 max-w-md"><Input label="Search" value={search} onChange={(event) => { setSearch(event.target.value); setPage(1); }} placeholder={`Search ${config.title.toLowerCase()}`} /></div>
        <ErrorAlert error={actionError ?? result.error} />
        {result.loading ? <LoadingState label={`Loading ${config.title.toLowerCase()}...`} /> : <><DataTable rows={result.data?.data ?? []} columns={columns} rowKey={(row) => row.id} /><Pagination meta={result.data?.meta} onPageChange={setPage} /></>}
        {canManage && <Modal open={open} title={`${editing ? 'Edit' : 'Add'} ${config.singular}`} onClose={() => !submitting && setOpen(false)}>
            <div className="space-y-4">
                <ErrorAlert error={actionError} />
                <div className="grid gap-3 md:grid-cols-2">
                    <Input label="Code" value={draft.code} onChange={(event) => setDraft({ ...draft, code: event.target.value })} />
                    <Input label="Name" value={draft.name} onChange={(event) => setDraft({ ...draft, name: event.target.value })} />
                    {kind === 'departments' && <HrDepartmentSelect label="Parent department" value={parent} onChange={setParent} />}
                    <Input label="Sort order" type="number" min={0} value={String(draft.sort_order ?? DEFAULT_SORT_ORDER)} onChange={(event) => setDraft({ ...draft, sort_order: Number(event.target.value) })} />
                </div>
                <Textarea label="Description" value={draft.description ?? ''} onChange={(event) => setDraft({ ...draft, description: event.target.value })} />
                <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={draft.is_active} onChange={(event) => setDraft({ ...draft, is_active: event.target.checked })} />Active</label>
                <div className="flex justify-end gap-2"><Button variant="secondary" onClick={() => setOpen(false)} disabled={submitting}>Cancel</Button><Button loading={submitting} onClick={() => void save()}>Save {config.singular}</Button></div>
            </div>
        </Modal>}
        {confirmDialog}
    </>;
}
