import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useNavigate } from 'react-router-dom';
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
import { buildAddressPayload } from '../schemas';
import { UserForm } from '../components/UserForm';
import { useCreateUser, useRoles } from '../hooks';
import { userFormSchema, type UserFormInput, type UserFormValues } from '../schemas';

export function UserCreatePage() {
    const navigate = useNavigate();
    const { showToast } = useToast();
    const { tenantId } = useTenant();
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

    const organizationUnitsQuery = useOrganizationUnits({ tenant_id: tenantId, per_page: 100, sort: 'name' });
    const rolesQuery = useRoles({ tenant_id: tenantId, per_page: 100, page: 1 });
    const createMutation = useCreateUser();

    async function onSubmit(values: UserFormValues) {
        setFormError(null);

        try {
            const user = await createMutation.mutateAsync({
                tenant_id: tenantId,
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
                title: 'User created',
                description: `${user.full_name ?? user.email ?? `User #${user.id}`} is ready for access and profile management.`,
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

            setFormError(error instanceof Error ? error.message : 'Unable to create user.');
        }
    }

    const lookupError = organizationUnitsQuery.error ?? rolesQuery.error;

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[{ label: 'Users & Access' }, { label: 'Users', href: '/users-access/users' }, { label: 'Add User' }]}
                description="Create tenant users with shared profile fields, role assignments, and organization ownership in the same master-data layout used across the application."
                title="Add User"
            />

            <ContentCard>
                {organizationUnitsQuery.isPending || rolesQuery.isPending ? (
                    <LoadingState lines={8} />
                ) : lookupError ? (
                    isForbiddenError(lookupError) ? (
                        <ProtectedErrorState description={lookupError.message} />
                    ) : (
                        <ErrorState description={lookupError.message} title="Unable to load user setup lookups" />
                    )
                ) : (
                    <UserForm
                        form={form}
                        formError={formError}
                        isSubmitting={createMutation.isPending}
                        mode="create"
                        onSubmit={onSubmit}
                        organizationUnits={organizationUnitsQuery.data?.items ?? []}
                        roles={rolesQuery.data?.items ?? []}
                    />
                )}
            </ContentCard>
        </div>
    );
}
