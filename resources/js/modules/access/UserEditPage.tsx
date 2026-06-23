import { useEffect, useMemo, useRef, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { CapabilityNotice } from '@/shared/components/CapabilityNotice';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { FormActions } from '@/shared/components/FormActions';
import { LoadingState } from '@/shared/components/LoadingState';
import { useApi } from '@/shared/hooks/useApi';
import { useUnsavedChanges } from '@/shared/hooks/useUnsavedChanges';
import { useAuth } from '@/modules/auth/AuthProvider';
import { accessApi, type AccessUser, type UserPayload } from './accessApi';
import { accessPermissions, hasAccessPermission } from './accessPermissions';
import { emptyUserForm, UserForm, type UserFormState } from './UserForm';

export default function UserEditPage() {
    const { id } = useParams();
    const navigate = useNavigate();
    const auth = useAuth();
    const tenantId = Number(auth.tenant?.id);
    const [form, setForm] = useState<UserFormState>(() => emptyUserForm());
    const [initial, setInitial] = useState<UserFormState | null>(null);
    const [error, setError] = useState<ApiError | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const initializedUserId = useRef<number | null>(null);
    const canUpdate = hasAccessPermission(auth.permissions, auth.roles, accessPermissions.usersUpdate);
    const canAssignRoles = hasAccessPermission(auth.permissions, auth.roles, accessPermissions.usersAssignRoles);
    const canManageOrganizationAccess = hasAccessPermission(auth.permissions, auth.roles, accessPermissions.usersManageOrganizationAccess);
    const user = useApi((signal) => accessApi.getUser(String(id), signal), [id], Boolean(id));
    const roles = useApi((signal) => accessApi.listRoles({ per_page: 100 }, signal), []);
    const organizationUnits = useApi((signal) => accessApi.listOrganizationUnits({ tenant_id: tenantId }, signal), [tenantId], Number.isFinite(tenantId) && tenantId > 0);

    useEffect(() => {
        if (!user.data || initializedUserId.current === user.data.id) return;
        const mapped = mapUserToForm(user.data);
        initializedUserId.current = user.data.id;
        setForm(mapped);
        setInitial(mapped);
    }, [user.data]);

    const dirty = useMemo(() => initial !== null && JSON.stringify(form) !== JSON.stringify(initial), [form, initial]);
    const confirmDiscard = useUnsavedChanges(dirty && !submitting);

    const save = async () => {
        if (!id || submitting) return;
        setSubmitting(true);
        setError(null);
        try {
            const payload: UserPayload = {
                first_name: form.first_name,
                last_name: form.last_name || null,
                username: form.username || null,
                email: form.email,
                phone: form.phone || null,
                status: form.status,
                row_version: form.row_version,
            };
            if (canAssignRoles) payload.role_ids = form.role_ids;
            if (canManageOrganizationAccess) {
                payload.organization_unit_ids = form.organization_unit_ids;
                payload.default_organization_unit_id = form.default_organization_unit_id;
            }
            const saved = await accessApi.updateUser(id, payload);
            navigate(`/access/users/${saved.id}`);
        } catch (caught) {
            setError(toApiError(caught));
        } finally {
            setSubmitting(false);
        }
    };

    if (!canUpdate) {
        return (
            <>
                <ContentHeader title="Edit User" description="Update tenant user profile and access." />
                <CapabilityNotice>You do not have permission to edit users.</CapabilityNotice>
            </>
        );
    }

    return (
        <>
            <ContentHeader title="Edit User" description="Update profile fields, role assignments, and organization access." />
            <ErrorAlert error={error ?? user.error ?? roles.error ?? organizationUnits.error} />
            {user.loading || roles.loading || organizationUnits.loading || !initial ? <LoadingState label="Loading user..." /> : (
                <form className="space-y-5" onSubmit={(event) => { event.preventDefault(); void save(); }}>
                    <UserForm
                        value={form}
                        roles={roles.data?.data ?? []}
                        organizationUnits={organizationUnits.data?.data ?? []}
                        error={error}
                        includePassword={false}
                        canAssignRoles={canAssignRoles}
                        canManageOrganizationAccess={canManageOrganizationAccess}
                        onChange={setForm}
                    />
                    <FormActions>
                        <Button type="button" variant="secondary" onClick={() => confirmDiscard() && navigate(`/access/users/${id}`)}>Cancel</Button>
                        <Button type="submit" loading={submitting}>Save User</Button>
                    </FormActions>
                </form>
            )}
        </>
    );
}

function mapUserToForm(user: AccessUser): UserFormState {
    const organizationUnits = user.organization_units ?? [];
    return {
        first_name: user.first_name ?? '',
        last_name: user.last_name ?? '',
        username: user.username ?? '',
        email: user.email ?? '',
        phone: user.phone ?? '',
        status: user.status ?? 'active',
        password: '',
        role_ids: (user.roles ?? []).map((role) => role.id),
        organization_unit_ids: organizationUnits.map((unit) => unit.id),
        default_organization_unit_id: organizationUnits.find((unit) => unit.is_default)?.id ?? null,
        row_version: user.row_version,
    };
}
