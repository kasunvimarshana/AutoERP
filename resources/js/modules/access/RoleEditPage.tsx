import { useEffect, useMemo, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { CapabilityNotice } from '@/shared/components/CapabilityNotice';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { FormActions } from '@/shared/components/FormActions';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { useApi } from '@/shared/hooks/useApi';
import { useUnsavedChanges } from '@/shared/hooks/useUnsavedChanges';
import { useAuth } from '@/modules/auth/AuthProvider';
import { accessApi, type AccessRole } from './accessApi';
import { accessPermissions, hasAccessPermission } from './accessPermissions';
import { emptyRoleForm, PermissionSelector, RoleForm, type RoleFormState } from './RoleForm';

export default function RoleEditPage() {
    const { id } = useParams();
    const navigate = useNavigate();
    const auth = useAuth();
    const [form, setForm] = useState<RoleFormState>(() => emptyRoleForm());
    const [initial, setInitial] = useState<RoleFormState | null>(null);
    const [permissionIds, setPermissionIds] = useState<number[]>([]);
    const [permissionSearch, setPermissionSearch] = useState('');
    const [error, setError] = useState<ApiError | null>(null);
    const [busyAction, setBusyAction] = useState<string | null>(null);
    const canUpdate = hasAccessPermission(auth, accessPermissions.rolesUpdate);
    const canAssignPermissions = hasAccessPermission(auth, accessPermissions.rolesAssignPermissions);
    const role = useApi((signal) => accessApi.getRole(String(id), signal), [id], Boolean(id));
    const permissions = useApi((signal) => accessApi.listAllPermissions(signal), [], canAssignPermissions);

    useEffect(() => {
        if (!role.data) return;
        const mapped = mapRoleToForm(role.data);
        setForm(mapped);
        setInitial(mapped);
        setPermissionIds((role.data.permissions ?? []).map((permission) => permission.id));
    }, [role.data]);

    const dirty = useMemo(() => initial !== null && JSON.stringify(form) !== JSON.stringify(initial), [form, initial]);
    const confirmDiscard = useUnsavedChanges(dirty && busyAction === null);

    const refresh = async () => {
        if (!id) return;
        role.setData(await accessApi.getRole(id));
    };

    const saveProfile = async () => {
        if (!role.data || busyAction) return;
        setBusyAction('profile');
        setError(null);
        try {
            await accessApi.updateRole(role.data.id, {
                expected_version: role.data.row_version,
                name: form.name,
                description: form.description || null,
            });
            await refresh();
        } catch (caught) {
            setError(toApiError(caught));
        } finally {
            setBusyAction(null);
        }
    };

    const savePermissions = async () => {
        if (!role.data || busyAction) return;
        setBusyAction('permissions');
        setError(null);
        try {
            await accessApi.syncRolePermissions(role.data.id, role.data.row_version, permissionIds);
            await refresh();
        } catch (caught) {
            setError(toApiError(caught));
        } finally {
            setBusyAction(null);
        }
    };

    if (!canUpdate && !canAssignPermissions) {
        return (
            <>
                <ContentHeader title="Manage Role" description="Update role details and permissions." />
                <CapabilityNotice>You do not have permission to modify roles.</CapabilityNotice>
            </>
        );
    }

    if (role.data?.is_system) {
        return (
            <>
                <ContentHeader title="Manage Role" description="Protected system role." />
                <CapabilityNotice>System roles are immutable. Their authoritative definition is maintained by the User module permission catalogue.</CapabilityNotice>
            </>
        );
    }

    return (
        <>
            <ContentHeader title="Manage Role" description="Role details and permission assignments are independent privileged actions." />
            <ErrorAlert error={error ?? role.error ?? permissions.error} />
            {role.loading || permissions.loading || !role.data || !initial ? <LoadingState label="Loading role management..." /> : (
                <div className="space-y-5">
                    {canUpdate ? (
                        <form className="space-y-4" onSubmit={(event) => { event.preventDefault(); void saveProfile(); }}>
                            <RoleForm value={form} error={error} onChange={setForm} />
                            <FormActions>
                                <Button type="button" variant="secondary" onClick={() => confirmDiscard() && navigate(`/access/roles/${id}`)}>Back</Button>
                                <Button type="submit" loading={busyAction === 'profile'} disabled={!dirty}>Save Role Details</Button>
                            </FormActions>
                        </form>
                    ) : null}
                    {canAssignPermissions ? (
                        <Panel title="Permission assignments">
                            <p className="mb-3 text-sm text-slate-600">Assign only system-defined permissions. Changes invalidate affected access caches and apply to every user holding this role.</p>
                            <PermissionSelector
                                permissions={permissions.data ?? []}
                                selectedIds={permissionIds}
                                search={permissionSearch}
                                onSearchChange={setPermissionSearch}
                                onChange={setPermissionIds}
                            />
                            <div className="mt-4 flex justify-end">
                                <Button loading={busyAction === 'permissions'} onClick={() => void savePermissions()}>Save Permissions</Button>
                            </div>
                        </Panel>
                    ) : null}
                </div>
            )}
        </>
    );
}

function mapRoleToForm(role: AccessRole): RoleFormState {
    return {
        name: role.name ?? '',
        description: role.description ?? '',
        row_version: role.row_version,
    };
}
