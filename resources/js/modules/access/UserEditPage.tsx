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
import { accessApi, type AccessMutationResult, type AccessUser } from './accessApi';
import { accessPermissions, hasAccessPermission } from './accessPermissions';
import { PermissionSelector } from './RoleForm';
import { CheckboxList } from './UserAccessFields';
import { emptyUserForm, UserForm, type UserFormState } from './UserForm';
import { Select } from '@/shared/components/Select';

export default function UserEditPage() {
    const { id } = useParams();
    const navigate = useNavigate();
    const auth = useAuth();
    const canUpdate = hasAccessPermission(auth, accessPermissions.usersUpdate);
    const canAssignRoles = hasAccessPermission(auth, accessPermissions.usersAssignRoles);
    const canAssignPermissions = hasAccessPermission(auth, accessPermissions.usersAssignPermissions);
    const canManageOrganizationAccess = hasAccessPermission(auth, accessPermissions.usersManageOrganizationAccess);
    const hasAnyManagementPermission = canUpdate || canAssignRoles || canAssignPermissions || canManageOrganizationAccess;
    const [profile, setProfile] = useState<UserFormState>(() => emptyUserForm());
    const [initialProfile, setInitialProfile] = useState<UserFormState | null>(null);
    const [rowVersion, setRowVersion] = useState(0);
    const [roleIds, setRoleIds] = useState<number[]>([]);
    const [initialRoleIds, setInitialRoleIds] = useState<number[]>([]);
    const [permissionIds, setPermissionIds] = useState<number[]>([]);
    const [initialPermissionIds, setInitialPermissionIds] = useState<number[]>([]);
    const [organizationUnitIds, setOrganizationUnitIds] = useState<number[]>([]);
    const [initialOrganizationUnitIds, setInitialOrganizationUnitIds] = useState<number[]>([]);
    const [defaultOrganizationUnitId, setDefaultOrganizationUnitId] = useState<number | null>(null);
    const [initialDefaultOrganizationUnitId, setInitialDefaultOrganizationUnitId] = useState<number | null>(null);
    const [error, setError] = useState<ApiError | null>(null);
    const [busyAction, setBusyAction] = useState<string | null>(null);
    const [permissionSearch, setPermissionSearch] = useState('');
    const user = useApi((signal) => accessApi.getUser(String(id), signal), [id], Boolean(id));
    const roles = useApi((signal) => accessApi.listAllRoles(signal), [], canAssignRoles);
    const permissions = useApi((signal) => accessApi.listAllPermissions(signal), [], canAssignPermissions);
    const organizationUnits = useApi(
        (signal) => accessApi.listAllOrganizationUnits(signal),
        [],
        canManageOrganizationAccess,
    );

    useEffect(() => {
        if (!user.data) return;
        const mapped = mapUserToForm(user.data);
        setProfile(mapped);
        setInitialProfile(mapped);
        const nextRoleIds = sortedIds((user.data.roles ?? []).map((role) => role.id));
        const nextPermissionIds = sortedIds((user.data.direct_permissions ?? []).map((permission) => permission.id));
        const nextOrganizationUnitIds = sortedIds((user.data.organization_units ?? []).map((unit) => unit.id));
        const nextDefaultOrganizationUnitId = user.data.default_organization_unit_id ?? null;
        setRowVersion(user.data.row_version);
        setRoleIds(nextRoleIds);
        setInitialRoleIds(nextRoleIds);
        setPermissionIds(nextPermissionIds);
        setInitialPermissionIds(nextPermissionIds);
        setOrganizationUnitIds(nextOrganizationUnitIds);
        setInitialOrganizationUnitIds(nextOrganizationUnitIds);
        setDefaultOrganizationUnitId(nextDefaultOrganizationUnitId);
        setInitialDefaultOrganizationUnitId(nextDefaultOrganizationUnitId);
    }, [user.data]);

    const profileDirty = useMemo(
        () => initialProfile !== null && JSON.stringify(profile) !== JSON.stringify(initialProfile),
        [initialProfile, profile],
    );
    const rolesDirty = !sameIds(roleIds, initialRoleIds);
    const permissionsDirty = !sameIds(permissionIds, initialPermissionIds);
    const organizationAccessDirty = !sameIds(organizationUnitIds, initialOrganizationUnitIds)
        || defaultOrganizationUnitId !== initialDefaultOrganizationUnitId;
    const hasUnsavedChanges = profileDirty || rolesDirty || permissionsDirty || organizationAccessDirty;
    const confirmDiscard = useUnsavedChanges(hasUnsavedChanges && busyAction === null);

    const runAction = async (name: string, action: () => Promise<AccessMutationResult>, onSaved: () => void) => {
        if (!user.data || rowVersion < 1 || busyAction) return;
        setBusyAction(name);
        setError(null);
        try {
            const result = await action();
            setRowVersion(result.row_version);
            onSaved();
        } catch (caught) {
            setError(toApiError(caught));
        } finally {
            setBusyAction(null);
        }
    };

    if (!hasAnyManagementPermission) {
        return (
            <>
                <ContentHeader title="Manage User" description="Manage tenant user profile and access." />
                <CapabilityNotice>You do not have permission to modify user accounts.</CapabilityNotice>
            </>
        );
    }

    const loading = user.loading || roles.loading || permissions.loading || organizationUnits.loading;
    const record = user.data;

    return (
        <>
            <ContentHeader title="Manage User" description="Profile, role, direct-permission, and organization-access changes are saved independently with explicit authorization." />
            <ErrorAlert error={error ?? user.error ?? roles.error ?? permissions.error ?? organizationUnits.error} />
            {loading || !record || !initialProfile ? <LoadingState label="Loading user management..." /> : (
                <div className="space-y-5">
                    {canUpdate ? (
                        <form className="space-y-4" onSubmit={(event) => {
                            event.preventDefault();
                            void runAction('profile', () => accessApi.updateUserProfile(record.id, {
                                expected_version: rowVersion,
                                first_name: profile.first_name,
                                last_name: profile.last_name || null,
                                username: profile.username || null,
                                phone: profile.phone || null,
                            }), () => setInitialProfile(profile));
                        }}>
                            <UserForm value={profile} error={error} emailReadOnly onChange={setProfile} />
                            <FormActions>
                                <Button type="button" variant="secondary" onClick={() => confirmDiscard() && navigate(`/access/users/${id}`)}>Back</Button>
                                <Button type="submit" loading={busyAction === 'profile'} disabled={!profileDirty}>Save Profile</Button>
                            </FormActions>
                        </form>
                    ) : null}

                    {canAssignRoles ? (
                        <Panel title="Role assignments">
                            <p className="mb-3 text-sm text-slate-600">Roles are the preferred way to grant reusable access. Saving this section revokes existing sessions so the new access takes effect safely.</p>
                            <CheckboxList
                                items={roles.data ?? []}
                                selectedIds={roleIds}
                                empty="No roles are available."
                                disabled={busyAction !== null}
                                onToggle={(roleId) => setRoleIds((current) => toggleId(current, roleId))}
                            />
                            <div className="mt-4 flex justify-end">
                                <Button loading={busyAction === 'roles'} disabled={!rolesDirty} onClick={() => void runAction(
                                    'roles',
                                    () => accessApi.syncUserRoles(record.id, rowVersion, roleIds),
                                    () => setInitialRoleIds(sortedIds(roleIds)),
                                )}>Save Roles</Button>
                            </div>
                        </Panel>
                    ) : null}

                    {canAssignPermissions ? (
                        <Panel title="Direct permission exceptions">
                            <p className="mb-3 text-sm text-slate-600">Use direct permissions only for an explicit exception that does not justify a reusable role. Role-based access remains the normal source of truth.</p>
                            <PermissionSelector
                                permissions={permissions.data ?? []}
                                selectedIds={permissionIds}
                                search={permissionSearch}
                                onSearchChange={setPermissionSearch}
                                onChange={setPermissionIds}
                            />
                            <div className="mt-4 flex justify-end">
                                <Button loading={busyAction === 'permissions'} disabled={!permissionsDirty} onClick={() => void runAction(
                                    'permissions',
                                    () => accessApi.syncUserPermissions(record.id, rowVersion, permissionIds),
                                    () => setInitialPermissionIds(sortedIds(permissionIds)),
                                )}>Save Direct Permissions</Button>
                            </div>
                        </Panel>
                    ) : null}

                    {canManageOrganizationAccess ? (
                        <Panel title="Organization access">
                            <p className="mb-3 text-sm text-slate-600">Select every organization unit this user may access and choose exactly one active default.</p>
                            <div className="space-y-4">
                                <CheckboxList
                                    items={organizationUnits.data ?? []}
                                    selectedIds={organizationUnitIds}
                                    empty="No active organization units are available."
                                    disabled={busyAction !== null}
                                    onToggle={(organizationUnitId) => {
                                        const nextIds = toggleId(organizationUnitIds, organizationUnitId);
                                        setOrganizationUnitIds(nextIds);
                                        if (!nextIds.includes(defaultOrganizationUnitId ?? 0)) setDefaultOrganizationUnitId(nextIds[0] ?? null);
                                    }}
                                />
                                <Select
                                    label="Default organization unit"
                                    required
                                    value={defaultOrganizationUnitId ? String(defaultOrganizationUnitId) : ''}
                                    options={(organizationUnits.data ?? [])
                                        .filter((unit) => organizationUnitIds.includes(unit.id))
                                        .map((unit) => ({ value: String(unit.id), label: unit.code ? `${unit.name} (${unit.code})` : unit.name }))}
                                    onChange={(event) => setDefaultOrganizationUnitId(event.target.value ? Number(event.target.value) : null)}
                                />
                                <div className="flex justify-end">
                                    <Button
                                        loading={busyAction === 'organization-access'}
                                        disabled={defaultOrganizationUnitId === null || !organizationAccessDirty}
                                        onClick={() => defaultOrganizationUnitId !== null && void runAction(
                                            'organization-access',
                                            () => accessApi.syncUserOrganizationAccess(
                                                record.id,
                                                rowVersion,
                                                organizationUnitIds,
                                                defaultOrganizationUnitId,
                                            ),
                                            () => {
                                                setInitialOrganizationUnitIds(sortedIds(organizationUnitIds));
                                                setInitialDefaultOrganizationUnitId(defaultOrganizationUnitId);
                                            },
                                        )}
                                    >
                                        Save Organization Access
                                    </Button>
                                </div>
                            </div>
                        </Panel>
                    ) : null}
                </div>
            )}
        </>
    );
}

function mapUserToForm(user: AccessUser): UserFormState {
    return {
        first_name: user.first_name ?? '',
        last_name: user.last_name ?? '',
        username: user.username ?? '',
        email: user.email ?? '',
        phone: user.phone ?? '',
        row_version: user.row_version,
    };
}

function toggleId(ids: number[], id: number): number[] {
    return sortedIds(ids.includes(id) ? ids.filter((candidate) => candidate !== id) : [...ids, id]);
}

function sortedIds(ids: number[]): number[] {
    return [...new Set(ids)].sort((left, right) => left - right);
}

function sameIds(left: number[], right: number[]): boolean {
    return JSON.stringify(sortedIds(left)) === JSON.stringify(sortedIds(right));
}
