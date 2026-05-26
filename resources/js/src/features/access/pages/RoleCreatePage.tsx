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
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { accessApi } from '../api';
import { RoleForm } from '../components/RoleForm';
import { useCreateRole, usePermissions } from '../hooks';
import { roleFormSchema, type RoleFormInput, type RoleFormValues } from '../schemas';

export function RoleCreatePage() {
    const navigate = useNavigate();
    const { showToast } = useToast();
    const { tenantId } = useTenant();
    const [formError, setFormError] = useState<string | null>(null);
    const form = useForm<RoleFormInput, unknown, RoleFormValues>({
        resolver: zodResolver(roleFormSchema),
        defaultValues: {
            name: '',
            permission_ids: [],
        },
    });

    const permissionsQuery = usePermissions({ tenant_id: tenantId, per_page: 100, page: 1 });
    const createMutation = useCreateRole();

    async function onSubmit(values: RoleFormValues) {
        setFormError(null);

        try {
            const role = await createMutation.mutateAsync({ tenant_id: tenantId, name: values.name });

            if (values.permission_ids.length > 0) {
                await accessApi.syncRolePermissions(role.id, { permission_ids: values.permission_ids });
            }

            showToast({
                title: 'Role created',
                description: `${role.name} is now available for user assignments.`,
                tone: 'success',
            });
            navigate('/users-access/roles');
        } catch (error) {
            if (error instanceof ValidationError) {
                applyValidationErrors(error.errors, form.setError, {
                    onUnhandled: (message) => setFormError(message),
                });
                return;
            }

            setFormError(error instanceof Error ? error.message : 'Unable to create role.');
        }
    }

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[{ label: 'Users & Access' }, { label: 'Roles', href: '/users-access/roles' }, { label: 'Add Role' }]}
                description="Create a new role and define its permission bundle in the same shared admin layout used across Phase 3."
                title="Add Role"
            />

            <ContentCard>
                {permissionsQuery.isPending ? (
                    <LoadingState lines={8} />
                ) : permissionsQuery.isError ? (
                    isForbiddenError(permissionsQuery.error) ? (
                        <ProtectedErrorState description={permissionsQuery.error.message} />
                    ) : (
                        <ErrorState description={permissionsQuery.error.message} title="Unable to load permissions" />
                    )
                ) : (
                    <RoleForm
                        allowNameEdit
                        form={form}
                        formError={formError}
                        isSubmitting={createMutation.isPending}
                        mode="create"
                        onSubmit={onSubmit}
                        permissions={permissionsQuery.data?.items ?? []}
                    />
                )}
            </ContentCard>
        </div>
    );
}
