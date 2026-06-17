import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
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
import { accessApi } from './accessApi';
import { accessPermissions, hasAccessPermission } from './accessPermissions';
import { emptyUserForm, UserForm, type UserFormState } from './UserForm';

export default function CreateUserPage() {
    const navigate = useNavigate();
    const auth = useAuth();
    const tenantId = Number(auth.tenant?.id);
    const [form, setForm] = useState<UserFormState>(() => emptyUserForm());
    const [error, setError] = useState<ApiError | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const canCreate = hasAccessPermission(auth.permissions, auth.roles, accessPermissions.usersCreate);
    const canAssignRoles = hasAccessPermission(auth.permissions, auth.roles, accessPermissions.usersAssignRoles);
    const canManageOrganizationAccess = hasAccessPermission(auth.permissions, auth.roles, accessPermissions.usersManageOrganizationAccess);
    const dirty = useMemo(() => JSON.stringify(form) !== JSON.stringify(emptyUserForm()), [form]);
    const confirmDiscard = useUnsavedChanges(dirty && !submitting);
    const roles = useApi((signal) => accessApi.listRoles({ per_page: 100 }, signal), []);
    const organizationUnits = useApi((signal) => accessApi.listOrganizationUnits({ tenant_id: tenantId }, signal), [tenantId], Number.isFinite(tenantId) && tenantId > 0);

    const save = async () => {
        if (submitting) return;
        setSubmitting(true);
        setError(null);
        try {
            const created = await accessApi.createUser({
                ...form,
                role_ids: canAssignRoles ? form.role_ids : [],
                organization_unit_ids: canManageOrganizationAccess ? form.organization_unit_ids : [],
                default_organization_unit_id: canManageOrganizationAccess ? form.default_organization_unit_id : null,
            });
            navigate(`/access/users/${created.id}`);
        } catch (caught) {
            setError(toApiError(caught));
        } finally {
            setSubmitting(false);
        }
    };

    if (!canCreate) {
        return (
            <>
                <ContentHeader title="Create User" description="Add a tenant user account." />
                <CapabilityNotice>You do not have permission to create users.</CapabilityNotice>
            </>
        );
    }

    return (
        <>
            <ContentHeader title="Create User" description="Add a tenant user account and initial access." />
            <ErrorAlert error={error ?? roles.error ?? organizationUnits.error} />
            {roles.loading || organizationUnits.loading ? <LoadingState label="Loading user setup..." /> : (
                <form className="space-y-5" onSubmit={(event) => { event.preventDefault(); void save(); }}>
                    <UserForm
                        value={form}
                        roles={roles.data?.data ?? []}
                        organizationUnits={organizationUnits.data?.data ?? []}
                        error={error}
                        includePassword
                        canAssignRoles={canAssignRoles}
                        canManageOrganizationAccess={canManageOrganizationAccess}
                        onChange={setForm}
                    />
                    <FormActions>
                        <Button type="button" variant="secondary" onClick={() => confirmDiscard() && navigate('/access/users')}>Cancel</Button>
                        <Button type="submit" loading={submitting}>Create User</Button>
                    </FormActions>
                </form>
            )}
        </>
    );
}
