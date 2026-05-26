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
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { parsePositiveInteger } from '../../shared/utils';
import { RoleForm } from '../components/RoleForm';
import { usePermissions, useRole, useSyncRolePermissions } from '../hooks';
import { roleFormSchema, type RoleFormInput, type RoleFormValues } from '../schemas';

export function RoleEditPage() {
    const navigate = useNavigate();
    const { showToast } = useToast();
    const { tenantId } = useTenant();
    const { roleId: roleIdParam } = useParams();
    const roleId = parsePositiveInteger(roleIdParam ?? null, 0);
    const [formError, setFormError] = useState<string | null>(null);
    const form = useForm<RoleFormInput, unknown, RoleFormValues>({
        resolver: zodResolver(roleFormSchema),
        defaultValues: {
            name: '',
            permission_ids: [],
        },
    });

    const roleQuery = useRole(roleId, roleId > 0);
    const permissionsQuery = usePermissions({ tenant_id: tenantId, per_page: 100, page: 1 });
    const syncMutation = useSyncRolePermissions(roleId);

    useEffect(() => {
        if (!roleQuery.data) {
            return;
        }

        form.reset({
            name: roleQuery.data.name,
            permission_ids: roleQuery.data.permissions.map((permission) => permission.id),
        });
    }, [form, roleQuery.data]);

    async function onSubmit(values: RoleFormValues) {
        if (roleId <= 0) {
            return;
        }

        setFormError(null);

        try {
            const role = await syncMutation.mutateAsync({ permission_ids: values.permission_ids });

            showToast({
                title: 'Role updated',
                description: `${role.name} permissions were synchronized successfully.`,
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

            setFormError(error instanceof Error ? error.message : 'Unable to update role permissions.');
        }
    }

    if (roleId <= 0) {
        return (
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
                <ErrorState description="The role route is missing a valid role ID." title="Invalid role route" />
            </div>
        );
    }

    const lookupError = roleQuery.error ?? permissionsQuery.error;

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[{ label: 'Users & Access' }, { label: 'Roles', href: '/users-access/roles' }, { label: roleQuery.data?.name ?? 'Role' }, { label: 'Edit' }]}
                description="The current backend contract exposes permission synchronization for roles, so this editor focuses on access bundle maintenance."
                title={roleQuery.data ? `Edit ${roleQuery.data.name}` : 'Edit Role'}
            />

            <ContentCard>
                {roleQuery.isPending || permissionsQuery.isPending ? (
                    <LoadingState lines={8} />
                ) : lookupError ? (
                    isForbiddenError(lookupError) ? (
                        <ProtectedErrorState description={lookupError.message} />
                    ) : (
                        <ErrorState description={lookupError.message} title="Unable to load role editor" />
                    )
                ) : (
                    <RoleForm
                        allowNameEdit={false}
                        form={form}
                        formError={formError}
                        isSubmitting={syncMutation.isPending}
                        mode="edit"
                        onSubmit={onSubmit}
                        permissions={permissionsQuery.data?.items ?? []}
                    />
                )}
            </ContentCard>
        </div>
    );
}
