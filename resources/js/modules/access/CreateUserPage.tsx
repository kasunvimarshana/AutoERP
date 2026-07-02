import { useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { CapabilityNotice } from '@/shared/components/CapabilityNotice';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { FormActions } from '@/shared/components/FormActions';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { useApi } from '@/shared/hooks/useApi';
import { useUnsavedChanges } from '@/shared/hooks/useUnsavedChanges';
import { useAuth } from '@/modules/auth/AuthProvider';
import { accessApi, type PasswordPolicyRequirements } from './accessApi';
import { accessPermissions, hasAccessPermission } from './accessPermissions';
import { emptyUserAccess, UserAccessFields, type UserAccessState } from './UserAccessFields';
import { emptyUserForm, UserForm, type UserFormState } from './UserForm';

interface CreateUserState {
    profile: UserFormState;
    access: UserAccessState;
    password: string;
    password_confirmation: string;
}

const emptyState = (): CreateUserState => ({
    profile: emptyUserForm(),
    access: emptyUserAccess(),
    password: '',
    password_confirmation: '',
});

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
    const passwordPolicy = useApi((signal) => accessApi.getUserPasswordPolicy(signal), []);
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
                password: form.password,
                password_confirmation: form.password_confirmation,
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
                <ContentHeader title="Create User" description="Create an active tenant user account." />
                <CapabilityNotice>You do not have permission to create users.</CapabilityNotice>
            </>
        );
    }

    if (!canManageOrganizationAccess) {
        return (
            <>
                <ContentHeader title="Create User" description="Create an active tenant user account." />
                <CapabilityNotice>
                    User onboarding requires organization-access permission because every tenant user must have an active default organization unit.
                </CapabilityNotice>
            </>
        );
    }

    return (
        <>
            <ContentHeader title="Create User" description="Enter the user identity, initial access, and password." />
            <ErrorAlert error={error ?? roles.error ?? organizationUnits.error ?? passwordPolicy.error} />
            {roles.loading || organizationUnits.loading || passwordPolicy.loading ? <LoadingState label="Loading user setup options..." /> : (
                <form className="space-y-5" onSubmit={(event) => { event.preventDefault(); void save(); }}>
                    <UserForm
                        value={form.profile}
                        error={error}
                        onChange={(profile) => setForm((current) => ({ ...current, profile }))}
                    />
                    <Panel title="Initial password">
                        <div className="grid gap-4 md:grid-cols-2">
                            <Input
                                label="Password"
                                type="password"
                                autoComplete="new-password"
                                required
                                value={form.password}
                                minLength={passwordPolicy.data?.minimum_length}
                                hint={passwordPolicyHint(passwordPolicy.data)}
                                error={fieldError(error, 'password')}
                                onChange={(event) => setForm((current) => ({ ...current, password: event.target.value }))}
                            />
                            <Input
                                label="Confirm password"
                                type="password"
                                autoComplete="new-password"
                                required
                                value={form.password_confirmation}
                                error={fieldError(error, 'password_confirmation')}
                                onChange={(event) => setForm((current) => ({ ...current, password_confirmation: event.target.value }))}
                            />
                        </div>
                    </Panel>
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
                        <Button type="submit" loading={submitting}>Create User</Button>
                    </FormActions>
                </form>
            )}
        </>
    );
}

function passwordPolicyHint(policy: PasswordPolicyRequirements | null | undefined): string {
    if (!policy) return 'Use the configured authentication password policy.';
    const requirements = [`${policy.minimum_length}+ characters`];
    if (policy.mixed_case) requirements.push('mixed case');
    if (policy.numbers) requirements.push('a number');
    if (policy.symbols) requirements.push('a symbol');
    return `Use ${requirements.join(', ')}. Share it through a secure channel.`;
}
