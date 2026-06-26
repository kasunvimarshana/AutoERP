import { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DetailGrid } from '@/shared/components/DetailGrid';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { useAuth } from '@/modules/auth/AuthProvider';
import { accessApi, type AccessRole } from './accessApi';
import { accessPermissions, hasAccessPermission } from './accessPermissions';
import { PermissionSelector } from './RoleForm';

export default function RoleDetailPage() {
    const { id } = useParams();
    const navigate = useNavigate();
    const auth = useAuth();
    const { confirm, confirmDialog } = useConfirmDialog();
    const [search, setSearch] = useState('');
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const [deleting, setDeleting] = useState(false);
    const role = useApi((signal) => accessApi.getRole(String(id), signal), [id], Boolean(id));
    const canUpdate = hasAccessPermission(auth, accessPermissions.rolesUpdate);
    const canDelete = hasAccessPermission(auth, accessPermissions.rolesDelete);

    const deleteRole = async (record: AccessRole) => {
        if (!await confirm({ title: 'Delete role', message: `Delete the role “${record.name}”? This cannot be undone.`, confirmLabel: 'Delete role' })) return;
        setDeleting(true);
        setActionError(null);
        try {
            await accessApi.deleteRole(record.id, record.row_version);
            navigate('/access/roles');
        } catch (caught) {
            setActionError(toApiError(caught));
        } finally {
            setDeleting(false);
        }
    };

    const record = role.data;
    return (
        <>
            <ContentHeader
                title={record?.name ?? 'Role Detail'}
                description="Read-only role summary and permission assignments."
                actions={(
                    <>
                        <Button variant="secondary" onClick={() => navigate('/access/roles')}>Back</Button>
                        {record && canUpdate && !record.is_system && <LinkButton variant="secondary" to={`/access/roles/${record.id}/edit`}>Edit</LinkButton>}
                        {record && canDelete && !record.is_system && <Button variant="danger" loading={deleting} onClick={() => void deleteRole(record)}>Delete</Button>}
                    </>
                )}
            />
            <ErrorAlert error={actionError ?? role.error} />
            {role.loading || !record ? <LoadingState label="Loading role..." /> : (
                <div className="space-y-5">
                    <Panel title="Summary">
                        <DetailGrid items={[
                            { label: 'Name', value: record.name },
                            { label: 'Authorization guard', value: record.guard_name ?? '-' },
                            { label: 'Description', value: record.description ?? '-' },
                            { label: 'Assigned Users', value: record.assigned_users_count ?? 0 },
                            { label: 'Permissions', value: record.permissions_count ?? 0 },
                            { label: 'Type', value: <StatusBadge status={record.is_system ? 'system' : 'custom'} /> },
                        ]} />
                    </Panel>
                    <Panel title="Permissions">
                        <PermissionSelector
                            permissions={record.permissions ?? []}
                            selectedIds={(record.permissions ?? []).map((permission) => permission.id)}
                            search={search}
                            readOnly
                            onSearchChange={setSearch}
                        />
                    </Panel>
                    <Panel title="Assigned Users">
                        <DetailGrid items={[
                            { label: 'Assigned Users', value: record.assigned_users_count ?? 0 },
                        ]} />
                    </Panel>
                </div>
            )}
            {confirmDialog}
        </>
    );
}
