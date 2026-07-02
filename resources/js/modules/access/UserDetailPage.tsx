import { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DetailGrid } from '@/shared/components/DetailGrid';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { FormActions } from '@/shared/components/FormActions';
import { LoadingState } from '@/shared/components/LoadingState';
import { Modal } from '@/shared/components/Modal';
import { Panel } from '@/shared/components/Panel';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import { formatDate } from '@/shared/utils/formatDate';
import { useAuth } from '@/modules/auth/AuthProvider';
import { accessApi } from './accessApi';
import { accessPermissions, hasAccessPermission } from './accessPermissions';
import { UserDocumentsPanel } from './UserDocumentsPanel';
import { UserDevicesPanel } from './UserDevicesPanel';

interface LifecycleAction {
    kind: 'status' | 'archive';
    status?: 'active' | 'inactive' | 'suspended';
    title: string;
    confirmLabel: string;
    danger: boolean;
}

export default function UserDetailPage() {
    const { id } = useParams();
    const navigate = useNavigate();
    const auth = useAuth();
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const [busy, setBusy] = useState(false);
    const [lifecycleAction, setLifecycleAction] = useState<LifecycleAction | null>(null);
    const [reason, setReason] = useState('');
    const user = useApi((signal) => accessApi.getUser(String(id), signal), [id], Boolean(id));
    const canUpdate = hasAccessPermission(auth, accessPermissions.usersUpdate);
    const canAssignRoles = hasAccessPermission(auth, accessPermissions.usersAssignRoles);
    const canAssignPermissions = hasAccessPermission(auth, accessPermissions.usersAssignPermissions);
    const canManageOrganizationAccess = hasAccessPermission(auth, accessPermissions.usersManageOrganizationAccess);
    const canActivate = hasAccessPermission(auth, accessPermissions.usersActivate);
    const canDeactivate = hasAccessPermission(auth, accessPermissions.usersDeactivate);
    const canDelete = hasAccessPermission(auth, accessPermissions.usersDelete);
    const canViewDocuments = hasAccessPermission(auth, accessPermissions.userDocumentsView);
    const canManageDocuments = hasAccessPermission(auth, accessPermissions.userDocumentsManage);
    const canViewDevices = hasAccessPermission(auth, accessPermissions.userDevicesView);
    const canManageDevices = hasAccessPermission(auth, accessPermissions.userDevicesManage);
    const canManage = canUpdate || canAssignRoles || canAssignPermissions || canManageOrganizationAccess;

    const refresh = async () => {
        if (!id) return;
        user.setData(await accessApi.getUser(id));
    };

    const executeLifecycleAction = async () => {
        if (!user.data || !lifecycleAction || reason.trim().length < 3) return;
        setBusy(true);
        setActionError(null);
        try {
            if (lifecycleAction.kind === 'archive') {
                await accessApi.archiveUser(user.data.id, user.data.row_version, reason.trim());
                navigate('/access/users');
                return;
            }
            await accessApi.changeUserStatus(
                user.data.id,
                user.data.row_version,
                lifecycleAction.status ?? 'inactive',
                reason.trim(),
            );
            setLifecycleAction(null);
            setReason('');
            await refresh();
        } catch (caught) {
            setActionError(toApiError(caught));
        } finally {
            setBusy(false);
        }
    };

    const openLifecycle = (action: LifecycleAction) => {
        setReason('');
        setLifecycleAction(action);
    };

    const record = user.data;
    return (
        <>
            <ContentHeader
                title={record?.name ?? 'User Detail'}
                description="Account readiness, access, and lifecycle summary. Sensitive changes require explicit permissions and reasons."
                actions={(
                    <>
                        <Button variant="secondary" onClick={() => navigate('/access/users')}>Back</Button>
                        {record && canManage && <LinkButton variant="secondary" to={`/access/users/${record.id}/edit`}>Manage</LinkButton>}
                        {record?.status !== 'active' && record?.status !== 'invited' && record?.credentials_ready && canActivate && (
                            <Button loading={busy} onClick={() => openLifecycle({ kind: 'status', status: 'active', title: 'Activate user', confirmLabel: 'Activate User', danger: false })}>Activate</Button>
                        )}
                        {record?.status === 'active' && canDeactivate && (
                            <Button variant="secondary" loading={busy} onClick={() => openLifecycle({ kind: 'status', status: 'suspended', title: 'Suspend user', confirmLabel: 'Suspend User', danger: true })}>Suspend</Button>
                        )}
                        {record?.status === 'active' && canDeactivate && (
                            <Button variant="danger" loading={busy} onClick={() => openLifecycle({ kind: 'status', status: 'inactive', title: 'Deactivate user', confirmLabel: 'Deactivate User', danger: true })}>Deactivate</Button>
                        )}
                        {record && canDelete && (
                            <Button variant="danger" loading={busy} onClick={() => openLifecycle({ kind: 'archive', title: 'Archive user', confirmLabel: 'Archive User', danger: true })}>Archive</Button>
                        )}
                    </>
                )}
            />
            <ErrorAlert error={actionError ?? user.error} />
            {user.loading || !record ? <LoadingState label="Loading user..." /> : (
                <div className="space-y-5">
                    <Panel title="Account summary">
                        <DetailGrid items={[
                            { label: 'Name', value: record.name },
                            { label: 'Username', value: record.username || '-' },
                            { label: 'Email', value: record.email },
                            { label: 'Phone', value: record.phone || '-' },
                            { label: 'Status', value: <StatusBadge status={record.status} /> },
                            { label: 'Credential setup', value: record.credentials_ready ? 'Completed' : 'Pending recipient action' },
                            { label: 'Invited', value: formatDate(record.invited_at) },
                            { label: 'Activated', value: formatDate(record.activated_at) },
                        ]} />
                    </Panel>
                    <Panel title="Role-based access">
                        <ReadonlyList title="Roles" values={(record.roles ?? []).map((role) => role.name)} />
                    </Panel>
                    <Panel title="Direct permission exceptions">
                        <p className="mb-3 text-sm text-slate-600">These permissions are assigned directly to the user and are separate from role-derived access.</p>
                        <ReadonlyList title="Direct permissions" values={(record.direct_permissions ?? []).map((permission) => permission.name)} monospace />
                    </Panel>
                    <Panel title="Organization access">
                        <ReadonlyList
                            title="Organization units"
                            values={(record.organization_units ?? []).map((unit) => `${unit.name}${unit.code ? ` (${unit.code})` : ''}${unit.is_default ? ' — default' : ''}`)}
                        />
                    </Panel>
                    {(canViewDocuments || canManageDocuments) && (
                        <Panel title="Private documents">
                            <UserDocumentsPanel userId={record.id} canManage={canManageDocuments} />
                        </Panel>
                    )}
                    {(canViewDevices || canManageDevices) && (
                        <Panel title="Registered devices">
                            <UserDevicesPanel userId={record.id} canManage={canManageDevices} />
                        </Panel>
                    )}
                    <Panel title="Record information">
                        <DetailGrid items={[
                            { label: 'Created', value: formatDate(record.created_at) },
                            { label: 'Updated', value: formatDate(record.updated_at) },
                            { label: 'Version', value: record.row_version },
                        ]} />
                    </Panel>
                </div>
            )}

            <Modal open={lifecycleAction !== null} title={lifecycleAction?.title ?? 'User action'} closeDisabled={busy} onClose={() => { setLifecycleAction(null); setReason(''); }}>
                <div className="space-y-5">
                    <p className="text-sm text-slate-700">Explain why this account lifecycle change is required. The reason is stored in the audit trail.</p>
                    <Textarea
                        label="Reason"
                        required
                        minLength={3}
                        maxLength={500}
                        value={reason}
                        onChange={(event) => setReason(event.target.value)}
                    />
                    <FormActions>
                        <Button type="button" variant="secondary" disabled={busy} onClick={() => { setLifecycleAction(null); setReason(''); }}>Cancel</Button>
                        <Button
                            type="button"
                            variant={lifecycleAction?.danger ? 'danger' : 'primary'}
                            loading={busy}
                            disabled={reason.trim().length < 3}
                            onClick={() => void executeLifecycleAction()}
                        >
                            {lifecycleAction?.confirmLabel ?? 'Confirm'}
                        </Button>
                    </FormActions>
                </div>
            </Modal>
        </>
    );
}

function ReadonlyList({ title, values, monospace = false }: { title: string; values: string[]; monospace?: boolean }) {
    return (
        <section>
            <h3 className="mb-2 text-sm font-semibold text-slate-900">{title}</h3>
            {values.length === 0 ? <p className="text-sm text-slate-500">None assigned.</p> : (
                <div className="flex flex-wrap gap-2">
                    {values.map((value) => (
                        <span key={value} className={`rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 ${monospace ? 'font-mono' : ''}`}>{value}</span>
                    ))}
                </div>
            )}
        </section>
    );
}
