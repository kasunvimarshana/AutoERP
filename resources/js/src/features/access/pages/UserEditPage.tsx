import { useEffect, useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useNavigate, useParams } from 'react-router-dom';
import { ContentCard } from '../../../components/ui/ContentCard';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { PageHeader } from '../../../components/layout/PageHeader';
import { ValidationError } from '../../../api/client';
import { useToast } from '../../../app/providers/ToastProvider';
import { applyValidationErrors } from '../../../lib/applyValidationErrors';
import { useTenant } from '../../auth/context/TenantContext';
import { useOrganizationUnits } from '../../organization/hooks';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { parsePositiveInteger } from '../../shared/utils';
import { buildAddressPayload, normalizeAddressDefaults } from '../schemas';
import { UserForm } from '../components/UserForm';
import { useRoles, useUpdateUser, useUser } from '../hooks';
import { userFormSchema, type UserFormInput, type UserFormValues } from '../schemas';

export function UserEditPage() {
    const navigate = useNavigate();
    const { showToast } = useToast();
    const { tenantId } = useTenant();
    const { userId: userIdParam } = useParams();
    const userId = parsePositiveInteger(userIdParam ?? null, 0);
    const [formError, setFormError] = useState<string | null>(null);
    const form = useForm<UserFormInput, unknown, UserFormValues>({
        resolver: zodResolver(userFormSchema),
        defaultValues: {
            email: '',
            first_name: '',
            last_name: '',
            phone: '',
            org_unit_id: '',
            active: true,
            roles: [],
            address_line1: '',
            address_line2: '',
            city: '',
            state: '',
            postal_code: '',
            country: '',
        },
    });

    const userQuery = useUser(userId, 'permissions', userId > 0);
    const organizationUnitsQuery = useOrganizationUnits({ tenant_id: tenantId, per_page: 100, sort: 'name' });
    const rolesQuery = useRoles({ tenant_id: tenantId, per_page: 100, page: 1 });
    const updateMutation = useUpdateUser(userId);

    useEffect(() => {
        if (!userQuery.data) {
            return;
        }

        form.reset({
            email: userQuery.data.email ?? '',
            first_name: userQuery.data.first_name ?? '',
            last_name: userQuery.data.last_name ?? '',
            phone: userQuery.data.phone ?? '',
            org_unit_id: userQuery.data.org_unit_id ?? '',
            active: userQuery.data.active,
            roles: userQuery.data.roles.map((role) => role.id),
            ...normalizeAddressDefaults(userQuery.data.address),
        });
    }, [form, userQuery.data]);

    async function onSubmit(values: UserFormValues) {
        if (userId <= 0) {
            return;
        }

        setFormError(null);

        try {
            const user = await updateMutation.mutateAsync({
                email: values.email,
                first_name: values.first_name,
                last_name: values.last_name,
                phone: values.phone ?? null,
                org_unit_id: values.org_unit_id ?? null,
                active: values.active,
                roles: values.roles,
                address: buildAddressPayload(values),
            });

            showToast({
                title: 'User updated',
                description: `${user.full_name ?? user.email ?? `User #${user.id}`} has been updated successfully.`,
                tone: 'success',
            });
            navigate(`/users-access/users/${user.id}`);
        } catch (error) {
            if (error instanceof ValidationError) {
                applyValidationErrors(error.errors, form.setError, {
                    onUnhandled: (message) => setFormError(message),
                });
                return;
            }

            setFormError(error instanceof Error ? error.message : 'Unable to update user.');
        }
    }

    if (userId <= 0) {
        return (
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
                <ErrorState description="The user route is missing a valid user ID." title="Invalid user route" />
            </div>
        );
    }

    const lookupError = userQuery.error ?? organizationUnitsQuery.error ?? rolesQuery.error;

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[{ label: 'Users & Access' }, { label: 'Users', href: '/users-access/users' }, { label: userQuery.data?.full_name ?? 'User' }, { label: 'Edit' }]}
                description="Maintain user profile fields, organization ownership, and role assignments without leaving the shared CRUD shell."
                title={userQuery.data ? `Edit ${userQuery.data.full_name ?? userQuery.data.email ?? 'User'}` : 'Edit User'}
            />

            <ContentCard>
                {userQuery.isPending || organizationUnitsQuery.isPending || rolesQuery.isPending ? (
                    <LoadingState lines={8} />
                ) : lookupError ? (
                    isForbiddenError(lookupError) ? (
                        <ProtectedErrorState description={lookupError.message} />
                    ) : (
                        <ErrorState description={lookupError.message} title="Unable to load user editor" />
                    )
                ) : (
                    <UserForm
                        form={form}
                        formError={formError}
                        isSubmitting={updateMutation.isPending}
                        mode="edit"
                        onSubmit={onSubmit}
                        organizationUnits={organizationUnitsQuery.data?.items ?? []}
                        roles={rolesQuery.data?.items ?? []}
                    />
                )}
            </ContentCard>
        </div>
    );
}
