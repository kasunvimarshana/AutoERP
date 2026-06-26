import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
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
import { accessApi } from './accessApi';
import { accessPermissions, hasAccessPermission } from './accessPermissions';
import { emptyUserAccess, UserAccessFields, type UserAccessState } from './UserAccessFields';
import { emptyUserForm, UserForm, type UserFormState } from './UserForm';

interface CreateUserState {
    profile: UserFormState;
    access: UserAccessState;
}

const emptyState = (): CreateUserState => ({ profile: emptyUserForm(), access: emptyUserAccess() });

export default function CreateUserPage() {
    const navigate = useNavigate();
    const auth = useAuth();
    const [form, setForm] = useState<CreateUserState>(() => emptyState());
    const [error, setError] = useState<ApiError | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const canCreate = hasAccessPermission(auth, accessPermissions.usersCreate);
    const canAssignRoles = hasAccessPermission(auth, accessPermissions.usersAssignRoles);
    const canManageOrganizationAccess = hasAccessPermission(auth, accessPermissions.usersManageOrganizationAccess);
    const roles = useApi((signal) => accessApi.listAllRoles(signal), []);
    const organizationUnits = useApi(
        (signal) => accessApi.listAllOrganizationUnits(signal),
        [],
    );
    const dirty = useMemo(() => JSON.stringify(form) !== JSON.stringify(emptyState()), [form]);
    const confirmDiscard = useUnsavedChanges(dirty && !submitting);

    const save = async () => {
        if (submitting || !canManageOrganizationAccess || form.access.default_organization_unit_id === null) return;
        setSubmitting(true);
        setError(null);
        try {
            const created = await accessApi.createUser({
                first_name: form.profile.first_name,
                last_name: form.profile.last_name || null,
                username: form.profile.username || null,
                email: form.profile.email,
                phone: form.profile.phone || null,
                role_ids: canAssignRoles ? form.access.role_ids : [],
                organization_unit_ids: form.access.organization_unit_ids,
                default_organization_unit_id: form.access.default_organization_unit_id,
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
                <ContentHeader title="Invite User" description="Create an invitation-first tenant user account." />
                <CapabilityNotice>You do not have permission to invite users.</CapabilityNotice>
            </>
        );
    }

    if (!canManageOrganizationAccess) {
        return (
            <>
                <ContentHeader title="Invite User" description="Create an invitation-first tenant user account." />
                <CapabilityNotice>
                    User onboarding requires organization-access permission because every tenant user must have an active default organization unit.
                </CapabilityNotice>
            </>
        );
    }

    return (
        <>
            <ContentHeader title="Invite User" description="Enter the user’s identity and initial access. The recipient creates their own password from a secure invitation." />
            <ErrorAlert error={error ?? roles.error ?? organizationUnits.error} />
            {roles.loading || organizationUnits.loading ? <LoadingState label="Loading user onboarding options..." /> : (
                <form className="space-y-5" onSubmit={(event) => { event.preventDefault(); void save(); }}>
                    <Panel title="Invitation workflow">
                        <p className="text-sm text-slate-600">No temporary password is created by an administrator. The account remains invited until the recipient verifies the invitation and chooses a password.</p>
                    </Panel>
                    <UserForm
                        value={form.profile}
                        error={error}
                        onChange={(profile) => setForm((current) => ({ ...current, profile }))}
                    />
                    <UserAccessFields
                        value={form.access}
                        roles={roles.data ?? []}
                        organizationUnits={organizationUnits.data ?? []}
                        error={error}
                        canAssignRoles={canAssignRoles}
                        canManageOrganizationAccess
                        onChange={(access) => setForm((current) => ({ ...current, access }))}
                    />
                    <FormActions>
                        <Button type="button" variant="secondary" onClick={() => confirmDiscard() && navigate('/access/users')}>Cancel</Button>
                        <Button type="submit" loading={submitting}>Send Invitation</Button>
                    </FormActions>
                </form>
            )}
        </>
    );
}
