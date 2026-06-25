import { useState, type FormEvent } from 'react';
import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { ApiError, toApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { FormActions } from '@/shared/components/FormActions';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Modal } from '@/shared/components/Modal';
import { StatusBadge } from '@/shared/components/StatusBadge';
import {
    organizationUnitApi,
    type OrganizationUnitType,
    type OrganizationUnitTypePayload,
} from '../organizationUnitApi';
import { organizationUnitPermissions } from '../organizationUnitPermissions';

export function OrganizationUnitTypePanel({
    types,
    loading,
    error,
    onReload,
}: {
    types: OrganizationUnitType[];
    loading: boolean;
    error: ApiError | null;
    onReload: () => void;
}) {
    const auth = useAuth();
    const { confirm, confirmDialog } = useConfirmDialog();
    const [editing, setEditing] = useState<OrganizationUnitType | 'new' | null>(null);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const [deletingId, setDeletingId] = useState<number | null>(null);
    const canManage = hasPermission(auth, organizationUnitPermissions.typesManage);
    const columns: DataColumn<OrganizationUnitType>[] = [
        { key: 'name', header: 'Type', render: (row) => <span className="font-semibold text-slate-900">{row.name}</span> },
        { key: 'level', header: 'Hierarchy level', render: (row) => row.level },
        { key: 'units', header: 'Units using type', render: (row) => row.organization_unit_count ?? 0 },
        { key: 'status', header: 'Status', render: (row) => <StatusBadge status={row.is_active ? 'active' : 'inactive'} /> },
        {
            key: 'actions',
            header: 'Actions',
            className: 'text-right',
            render: (row) => canManage ? (
                <div className="flex justify-end gap-2">
                    <Button variant="secondary" className="min-h-8 px-3 py-1 text-xs" onClick={() => setEditing(row)}>Edit</Button>
                    <Button
                        variant="danger"
                        className="min-h-8 px-3 py-1 text-xs"
                        loading={deletingId === row.id}
                        disabled={(row.organization_unit_count ?? 0) > 0}
                        onClick={() => void deleteType(row)}
                    >
                        Delete
                    </Button>
                </div>
            ) : null,
        },
    ];

    const deleteType = async (type: OrganizationUnitType) => {
        if (!await confirm({
            title: 'Delete organization-unit type',
            message: `Delete “${type.name}”? Types assigned to organization units cannot be deleted.`,
            confirmLabel: 'Delete type',
        })) return;

        setDeletingId(type.id);
        setActionError(null);
        try {
            await organizationUnitApi.deleteType(type);
            onReload();
        } catch (caught: unknown) {
            setActionError(toApiError(caught));
        } finally {
            setDeletingId(null);
        }
    };

    return (
        <div className="space-y-4 pt-5">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 className="text-lg font-semibold text-slate-900">Hierarchy types</h2>
                    <p className="mt-1 text-sm text-slate-600">Define one active type for each hierarchy level used by the tenant.</p>
                </div>
                {canManage && <Button onClick={() => setEditing('new')}>Add type</Button>}
            </div>
            <ErrorAlert error={actionError ?? error} />
            {loading ? <LoadingState label="Loading organization-unit types…" /> : (
                <DataTable
                    rows={types}
                    columns={columns}
                    rowKey={(row) => row.id}
                    emptyMessage="No organization-unit types have been defined."
                />
            )}
            {editing && (
                <TypeEditor
                    key={editing === 'new' ? 'new' : editing.id}
                    type={editing === 'new' ? null : editing}
                    onClose={() => setEditing(null)}
                    onSaved={() => {
                        setEditing(null);
                        onReload();
                    }}
                />
            )}
            {confirmDialog}
        </div>
    );
}

function TypeEditor({
    type,
    onClose,
    onSaved,
}: {
    type: OrganizationUnitType | null;
    onClose: () => void;
    onSaved: () => void;
}) {
    const [name, setName] = useState(type?.name ?? '');
    const [level, setLevel] = useState(type ? String(type.level) : '');
    const [active, setActive] = useState(type?.is_active ?? true);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        const hierarchyLevel = Number(level);
        if (!name.trim() || !Number.isSafeInteger(hierarchyLevel) || hierarchyLevel < 0) {
            setError(new ApiError('Type name and a valid hierarchy level are required.', 422, 'INVALID_ORGANIZATION_UNIT_TYPE', 'validation'));
            return;
        }

        const payload: OrganizationUnitTypePayload = {
            name: name.trim(),
            level: hierarchyLevel,
            is_active: active,
            ...(type ? { expected_version: type.row_version } : {}),
        };
        setSubmitting(true);
        setError(null);
        try {
            if (type) await organizationUnitApi.updateType(type.id, payload);
            else await organizationUnitApi.createType(payload);
            onSaved();
        } catch (caught: unknown) {
            setError(toApiError(caught));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <Modal open title={type ? `Edit ${type.name}` : 'Add organization-unit type'} onClose={onClose} closeDisabled={submitting}>
            <form className="space-y-5" onSubmit={(event) => void submit(event)}>
                <ErrorAlert error={error} />
                <Input label="Type name" value={name} maxLength={255} onChange={(event) => setName(event.target.value)} required />
                <Input
                    label="Hierarchy level"
                    type="number"
                    min={0}
                    step={1}
                    value={level}
                    hint="Root is level 0; its direct children are level 1."
                    onChange={(event) => setLevel(event.target.value)}
                    required
                />
                <label className="flex items-start gap-3 rounded-lg border border-slate-200 p-3 text-sm text-slate-700">
                    <input type="checkbox" checked={active} onChange={(event) => setActive(event.target.checked)} />
                    <span><strong>Active type</strong><span className="mt-1 block text-xs text-slate-500">Inactive types cannot be assigned to new or moved organization units.</span></span>
                </label>
                <FormActions>
                    <Button type="button" variant="secondary" disabled={submitting} onClick={onClose}>Cancel</Button>
                    <Button type="submit" loading={submitting}>Save type</Button>
                </FormActions>
            </form>
        </Modal>
    );
}
