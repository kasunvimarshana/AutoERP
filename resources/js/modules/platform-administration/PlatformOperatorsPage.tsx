import { useState, type FormEvent } from 'react';
import { PLATFORM_PERMISSION } from '@/app/access/platformPermissions';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';
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
import { StatusBadge } from '@/shared/components/StatusBadge';
import { SuccessAlert } from '@/shared/components/SuccessAlert';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { platformAdministrationApi } from './platformAdministrationApi';
import { formatPlatformDateTime, humanizePlatformValue, permissionGroup } from './platformAdministrationPresentation';
import type { CreatePlatformOperatorPayload, PlatformOperator } from './platformAdministrationTypes';

type OperatorAction =
    | { type: 'permissions'; operator: PlatformOperator }
    | { type: 'status'; operator: PlatformOperator; target: 'active' | 'inactive' }
    | { type: 'resend-invitation'; operator: PlatformOperator }
    | { type: 'revoke-invitation'; operator: PlatformOperator }
    | null;

const emptyCreateForm: CreatePlatformOperatorPayload = {
    first_name: '',
    last_name: '',
    email: '',
    permissions: [],
};

export default function PlatformOperatorsPage() {
    const auth = useAuth();
    const canManage = hasPermission(auth, PLATFORM_PERMISSION.operatorsManage);
    const [search, setSearch] = useState('');
    const [status, setStatus] = useState('');
    const [page, setPage] = useState(1);
    const debouncedSearch = useDebounce(search);
    const request = useApi(
        (signal) => platformAdministrationApi.listOperators({
            search: debouncedSearch || undefined,
            status: status || undefined,
            page,
            per_page: 20,
        }, signal),
        [debouncedSearch, status, page],
        true,
        false,
    );
    const [createOpen, setCreateOpen] = useState(false);
    const [createForm, setCreateForm] = useState<CreatePlatformOperatorPayload>(emptyCreateForm);
    const [action, setAction] = useState<OperatorAction>(null);
    const [selectedPermissions, setSelectedPermissions] = useState<string[]>([]);
    const [reason, setReason] = useState('');
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [success, setSuccess] = useState<string | null>(null);

    const availablePermissions = request.data?.available_permissions ?? [];
    const columns: DataColumn<PlatformOperator>[] = [
        {
            key: 'operator',
            header: 'Operator',
            render: (operator) => (
                <div>
                    <p className="font-semibold text-slate-900">{operator.display_name}</p>
                    <p className="text-xs text-slate-500">{operator.email}</p>
                    <InvitationSummary operator={operator} />
                </div>
            ),
        },
        { key: 'status', header: 'Status', render: (operator) => <StatusBadge status={operator.status} /> },
        {
            key: 'permissions',
            header: 'Access',
            render: (operator) => (
                <span>{operator.permissions.length} permission{operator.permissions.length === 1 ? '' : 's'}</span>
            ),
        },
        { key: 'updated', header: 'Updated', render: (operator) => formatPlatformDateTime(operator.updated_at) },
        {
            key: 'actions',
            header: '',
            className: 'text-right',
            mobile: false,
            render: (operator) => canManage ? renderOperatorActions(operator) : null,
        },
    ];

    function openPermissions(operator: PlatformOperator) {
        setSelectedPermissions([...operator.permissions]);
        setAction({ type: 'permissions', operator });
        setError(null);
    }

    function openAction(nextAction: Exclude<OperatorAction, null>) {
        setAction(nextAction);
        setReason('');
        setError(null);
    }

    function renderOperatorActions(operator: PlatformOperator) {
        return (
            <div className="flex flex-wrap justify-end gap-2">
                <Button
                    variant="secondary"
                    className="min-h-8 px-3 py-1 text-xs"
                    onClick={() => openPermissions(operator)}
                >
                    Permissions
                </Button>
                {operator.status === 'invited' ? (
                    <>
                        <Button
                            variant="primary"
                            className="min-h-8 px-3 py-1 text-xs"
                            onClick={() => openAction({ type: 'resend-invitation', operator })}
                        >
                            Resend invitation
                        </Button>
                        <Button
                            variant="danger"
                            className="min-h-8 px-3 py-1 text-xs"
                            onClick={() => openAction({ type: 'revoke-invitation', operator })}
                        >
                            Revoke invitation
                        </Button>
                    </>
                ) : (
                    <Button
                        variant={operator.status === 'active' ? 'danger' : 'primary'}
                        className="min-h-8 px-3 py-1 text-xs"
                        onClick={() => openAction({
                            type: 'status',
                            operator,
                            target: operator.status === 'active' ? 'inactive' : 'active',
                        })}
                    >
                        {operator.status === 'active' ? 'Deactivate' : 'Activate'}
                    </Button>
                )}
            </div>
        );
    }

    function togglePermission(permission: string) {
        setSelectedPermissions((current) => current.includes(permission)
            ? current.filter((candidate) => candidate !== permission)
            : [...current, permission].sort());
    }

    async function createOperator(event: FormEvent) {
        event.preventDefault();
        setSaving(true);
        setError(null);
        setSuccess(null);
        try {
            const created = await platformAdministrationApi.createOperator({
                ...createForm,
                first_name: createForm.first_name.trim(),
                last_name: createForm.last_name?.trim() || null,
                email: createForm.email.trim().toLowerCase(),
            });
            setCreateOpen(false);
            setCreateForm(emptyCreateForm);
            request.reload();
            setSuccess(`A secure invitation was queued for ${created.email}. The recipient chooses their own password before the account becomes active.`);
        } catch (requestError: unknown) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    }

    async function saveAction() {
        if (!action) return;
        setSaving(true);
        setError(null);
        setSuccess(null);
        try {
            if (action.type === 'permissions') {
                const updated = await platformAdministrationApi.updateOperatorPermissions(action.operator, selectedPermissions);
                setSuccess(`${updated.display_name}'s platform permissions were synchronized. Existing sessions were revoked.`);
            } else if (action.type === 'status') {
                const updated = await platformAdministrationApi.changeOperatorStatus(action.operator, action.target, reason.trim());
                setSuccess(`${updated.display_name} is now ${updated.status}.`);
            } else if (action.type === 'resend-invitation') {
                const updated = await platformAdministrationApi.resendOperatorInvitation(action.operator);
                setSuccess(`The active invitation was queued again for ${updated.email}. Its expiry was refreshed, and existing copies of the active link remain valid.`);
            } else {
                const updated = await platformAdministrationApi.revokeOperatorInvitation(action.operator, reason.trim());
                setSuccess(`${updated.display_name}'s pending invitation was revoked.`);
            }
            setAction(null);
            setReason('');
            request.reload();
        } catch (requestError: unknown) {
            setError(toApiError(requestError));
            request.reload();
        } finally {
            setSaving(false);
        }
    }

    return (
        <>
            <ContentHeader
                title="Platform operators"
                description="Govern control-plane identities, least-privilege grants, and recipient-owned account activation without mixing tenant users into platform access."
                actions={canManage ? <Button onClick={() => { setCreateOpen(true); setError(null); setSuccess(null); }}>Invite operator</Button> : null}
            />

            <div className="space-y-5">
                <SuccessAlert message={success} onDismiss={() => setSuccess(null)} />
                <ErrorAlert error={request.error} title="Unable to load platform operators" />
                <ErrorAlert error={error} title="Platform operator action failed" />

                <div className="grid gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-2">
                    <Input
                        label="Search operators"
                        value={search}
                        placeholder="Name or platform email"
                        onChange={(event) => { setSearch(event.target.value); setPage(1); }}
                    />
                    <Select
                        label="Status"
                        value={status}
                        placeholder="All statuses"
                        options={[
                            { value: 'invited', label: 'Invited' },
                            { value: 'active', label: 'Active' },
                            { value: 'inactive', label: 'Inactive' },
                        ]}
                        onChange={(event) => { setStatus(event.target.value); setPage(1); }}
                    />
                </div>

                {request.loading && !request.data ? <LoadingState label="Loading platform operators..." /> : (
                    <DataTable
                        rows={request.data?.data ?? []}
                        columns={columns}
                        rowKey={(operator) => operator.id}
                        emptyMessage="No platform operators match the current filters."
                        mobileSummary={(operator) => operator.display_name}
                        mobileDetails={(operator) => (
                            <div className="space-y-1">
                                <p>{operator.email}</p>
                                <InvitationSummary operator={operator} />
                                <p>{operator.permissions.length} permission(s)</p>
                                <p>Updated {formatPlatformDateTime(operator.updated_at)}</p>
                            </div>
                        )}
                        rowBadge={(operator) => <StatusBadge status={operator.status} />}
                        mobileActions={canManage ? (operator) => renderOperatorActions(operator) : undefined}
                    />
                )}

                {request.data ? <Pagination meta={request.data.meta} onPageChange={setPage} /> : null}
            </div>

            <Modal open={createOpen} title="Invite platform operator" onClose={() => setCreateOpen(false)} closeDisabled={saving}>
                <form className="space-y-5" onSubmit={createOperator}>
                    <p className="rounded-lg bg-blue-50 p-3 text-sm text-blue-900">
                        A secure email invitation is sent to the recipient. Platform managers never choose or know the operator&apos;s password. The account remains inactive until the invitation is accepted.
                    </p>
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Input label="First name" required value={createForm.first_name} onChange={(event) => setCreateForm((current) => ({ ...current, first_name: event.target.value }))} />
                        <Input label="Last name" value={createForm.last_name ?? ''} onChange={(event) => setCreateForm((current) => ({ ...current, last_name: event.target.value }))} />
                    </div>
                    <Input
                        label="Platform email"
                        type="email"
                        required
                        value={createForm.email}
                        onChange={(event) => setCreateForm((current) => ({ ...current, email: event.target.value }))}
                        hint="The invitation and all control-plane security notifications are sent to this address."
                    />
                    <PermissionSelector permissions={availablePermissions} selected={createForm.permissions} onToggle={(permission) => setCreateForm((current) => ({ ...current, permissions: current.permissions.includes(permission) ? current.permissions.filter((item) => item !== permission) : [...current.permissions, permission].sort() }))} />
                    <div className="flex justify-end gap-2">
                        <Button type="button" variant="secondary" disabled={saving} onClick={() => setCreateOpen(false)}>Cancel</Button>
                        <Button type="submit" loading={saving} disabled={createForm.permissions.length === 0}>Send invitation</Button>
                    </div>
                </form>
            </Modal>

            <Modal
                open={action?.type === 'permissions'}
                title={action?.type === 'permissions' ? `Permissions for ${action.operator.display_name}` : 'Permissions'}
                onClose={() => setAction(null)}
                closeDisabled={saving}
            >
                <div className="space-y-5">
                    <p className="text-sm text-slate-600">Saving performs an exact permission synchronization and revokes existing sessions so changed authorization cannot remain cached.</p>
                    <PermissionSelector permissions={availablePermissions} selected={selectedPermissions} onToggle={togglePermission} />
                    <div className="flex justify-end gap-2">
                        <Button variant="secondary" disabled={saving} onClick={() => setAction(null)}>Cancel</Button>
                        <Button loading={saving} onClick={saveAction}>Save permissions</Button>
                    </div>
                </div>
            </Modal>

            <Modal
                open={action?.type === 'status'}
                title={action?.type === 'status' ? `${action.target === 'active' ? 'Activate' : 'Deactivate'} ${action.operator.display_name}` : 'Change operator status'}
                onClose={() => setAction(null)}
                closeDisabled={saving}
            >
                <div className="space-y-5">
                    <p className="text-sm text-slate-700">
                        {action?.type === 'status' && action.target === 'inactive'
                            ? 'Deactivation blocks platform sign-in and revokes active sessions. The backend prevents self-lockout and removal of the last active platform manager.'
                            : 'Activation restores sign-in eligibility. Existing sessions are not created automatically.'}
                    </p>
                    <Textarea label="Reason" required minLength={10} maxLength={500} value={reason} onChange={(event) => setReason(event.target.value)} hint="At least 10 characters. The reason is retained in the platform audit trail." />
                    <div className="flex justify-end gap-2">
                        <Button variant="secondary" disabled={saving} onClick={() => setAction(null)}>Cancel</Button>
                        <Button
                            variant={action?.type === 'status' && action.target === 'inactive' ? 'danger' : 'primary'}
                            loading={saving}
                            disabled={reason.trim().length < 10}
                            onClick={saveAction}
                        >
                            Confirm status change
                        </Button>
                    </div>
                </div>
            </Modal>

            <Modal
                open={action?.type === 'resend-invitation'}
                title={action?.type === 'resend-invitation' ? `Resend invitation to ${action.operator.display_name}` : 'Resend invitation'}
                onClose={() => setAction(null)}
                closeDisabled={saving}
            >
                <div className="space-y-5">
                    <p className="text-sm text-slate-700">
                        The active secure invitation will be queued again for {action?.type === 'resend-invitation' ? action.operator.email : 'the operator'}. Its expiry is refreshed, and existing copies remain valid until the invitation is accepted, expires, or is explicitly revoked.
                    </p>
                    <div className="flex justify-end gap-2">
                        <Button variant="secondary" disabled={saving} onClick={() => setAction(null)}>Cancel</Button>
                        <Button loading={saving} onClick={saveAction}>Resend invitation</Button>
                    </div>
                </div>
            </Modal>

            <Modal
                open={action?.type === 'revoke-invitation'}
                title={action?.type === 'revoke-invitation' ? `Revoke invitation for ${action.operator.display_name}` : 'Revoke invitation'}
                onClose={() => setAction(null)}
                closeDisabled={saving}
            >
                <div className="space-y-5">
                    <p className="text-sm text-slate-700">Revoking the invitation permanently invalidates the pending link and leaves the operator account inactive.</p>
                    <Textarea label="Reason" required minLength={10} maxLength={500} value={reason} onChange={(event) => setReason(event.target.value)} hint="At least 10 characters. The reason is retained in the platform audit trail." />
                    <div className="flex justify-end gap-2">
                        <Button variant="secondary" disabled={saving} onClick={() => setAction(null)}>Cancel</Button>
                        <Button variant="danger" loading={saving} disabled={reason.trim().length < 10} onClick={saveAction}>Revoke invitation</Button>
                    </div>
                </div>
            </Modal>
        </>
    );
}

function InvitationSummary({ operator }: { operator: PlatformOperator }) {
    if (operator.status !== 'invited' || !operator.invitation) return null;

    const invitation = operator.invitation;
    return (
        <div className="mt-1 text-xs text-slate-500">
            <span>Invitation {humanizePlatformValue(invitation.delivery_status)}</span>
            {invitation.expires_at ? <span> · Expires {formatPlatformDateTime(invitation.expires_at)}</span> : null}
            {invitation.delivery_status === 'failed' && invitation.error_message
                ? <p className="mt-1 text-red-700">{invitation.error_message}</p>
                : null}
        </div>
    );
}

function PermissionSelector({ permissions, selected, onToggle }: { permissions: string[]; selected: string[]; onToggle: (permission: string) => void }) {
    const groups = permissions.reduce<Record<string, string[]>>((result, permission) => {
        const group = permissionGroup(permission);
        (result[group] ??= []).push(permission);
        return result;
    }, {});

    return (
        <fieldset>
            <legend className="text-sm font-semibold text-slate-900">Platform permissions</legend>
            <p className="mt-1 text-xs text-slate-500">Select only the control-plane capabilities required for this operator&apos;s role.</p>
            <div className="mt-3 max-h-80 space-y-4 overflow-y-auto rounded-lg border border-slate-200 p-4">
                {Object.entries(groups).map(([group, values]) => (
                    <div key={group}>
                        <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{group}</p>
                        <div className="grid gap-2 sm:grid-cols-2">
                            {values.map((permission) => (
                                <label key={permission} className="flex cursor-pointer items-start gap-2 rounded-lg border border-slate-200 p-3 text-sm hover:bg-slate-50">
                                    <input
                                        type="checkbox"
                                        className="mt-1"
                                        checked={selected.includes(permission)}
                                        onChange={() => onToggle(permission)}
                                    />
                                    <span>
                                        <span className="block font-medium text-slate-800">{humanizePlatformValue(permission)}</span>
                                        <span className="block text-xs text-slate-500">{permission}</span>
                                    </span>
                                </label>
                            ))}
                        </div>
                    </div>
                ))}
                {permissions.length === 0 ? <p className="text-sm text-amber-700">The platform permission catalogue is unavailable. Synchronize it before creating or editing operators.</p> : null}
            </div>
        </fieldset>
    );
}
