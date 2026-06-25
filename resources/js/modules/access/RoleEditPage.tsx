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
import { accessApi, type AccessRole, type RolePayload } from './accessApi';
import { accessPermissions, hasAccessPermission } from './accessPermissions';
import { emptyRoleForm, RoleForm, type RoleFormState } from './RoleForm';

export default function RoleEditPage() {
    const { id } = useParams();
    const navigate = useNavigate();
    const auth = useAuth();
    const [form, setForm] = useState<RoleFormState>(() => emptyRoleForm());
    const [initial, setInitial] = useState<RoleFormState | null>(null);
    const [error, setError] = useState<ApiError | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const initializedRoleId = useRef<number | null>(null);
    const canUpdate = hasAccessPermission(auth.permissions, auth.roles, accessPermissions.rolesUpdate);
    const canAssignPermissions = hasAccessPermission(auth.permissions, auth.roles, accessPermissions.rolesAssignPermissions);
    const role = useApi((signal) => accessApi.getRole(String(id), signal), [id], Boolean(id));
    const permissions = useApi((signal) => accessApi.listPermissions({ per_page: 100 }, signal), []);

    useEffect(() => {
        if (!role.data || initializedRoleId.current === role.data.id) return;
        const mapped = mapRoleToForm(role.data);
        initializedRoleId.current = role.data.id;
        setForm(mapped);
        setInitial(mapped);
    }, [role.data]);

    const dirty = useMemo(() => initial !== null && JSON.stringify(form) !== JSON.stringify(initial), [form, initial]);
    const confirmDiscard = useUnsavedChanges(dirty && !submitting);

    const save = async () => {
        if (!id || submitting) return;
        setSubmitting(true);
        setError(null);
        try {
            const payload: RolePayload = {
                name: form.name,
                guard_name: form.guard_name,
                description: form.description || null,
                row_version: form.row_version,
            };
            if (canAssignPermissions) payload.permission_ids = form.permission_ids;
            const saved = await accessApi.updateRole(id, payload);
            navigate(`/access/roles/${saved.id}`);
        } catch (caught) {
            setError(toApiError(caught));
        } finally {
            setSubmitting(false);
        }
    };

    if (!canUpdate) {
        return (
            <>
                <ContentHeader title="Edit Role" description="Update tenant role details and permissions." />
                <CapabilityNotice>You do not have permission to edit roles.</CapabilityNotice>
            </>
        );
    }

    if (role.data?.status === 'protected') {
        return (
            <>
                <ContentHeader title="Edit Role" description="Update tenant role details and permissions." />
                <CapabilityNotice>Protected system roles cannot be edited.</CapabilityNotice>
            </>
        );
    }

    return (
        <>
            <ContentHeader title="Edit Role" description="Update tenant role details and permission assignments." />
            <ErrorAlert error={error ?? role.error ?? permissions.error} />
            {role.loading || permissions.loading || !initial ? <LoadingState label="Loading role..." /> : (
                <form className="space-y-5" onSubmit={(event) => { event.preventDefault(); void save(); }}>
                    <RoleForm
                        value={form}
                        permissions={permissions.data?.data ?? []}
                        error={error}
                        canAssignPermissions={canAssignPermissions}
                        onChange={setForm}
                    />
                    <FormActions>
                        <Button type="button" variant="secondary" onClick={() => confirmDiscard() && navigate(`/access/roles/${id}`)}>Cancel</Button>
                        <Button type="submit" loading={submitting}>Save Role</Button>
                    </FormActions>
                </form>
            )}
        </>
    );
}

function mapRoleToForm(role: AccessRole): RoleFormState {
    return {
        name: role.name ?? '',
        guard_name: role.guard_name ?? role.code ?? 'web',
        description: role.description ?? '',
        permission_ids: (role.permissions ?? []).map((permission) => permission.id),
        row_version: role.row_version,
    };
}
