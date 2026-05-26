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
import { EmployeeForm } from '../components/EmployeeForm';
import { useCreateEmployee } from '../hooks';
import { employeeFormSchema, type EmployeeFormInput, type EmployeeFormValues } from '../schemas';

export function EmployeeCreatePage() {
    const navigate = useNavigate();
    const { showToast } = useToast();
    const { tenantId } = useTenant();
    const [formError, setFormError] = useState<string | null>(null);
    const form = useForm<EmployeeFormInput, unknown, EmployeeFormValues>({
        resolver: zodResolver(employeeFormSchema),
        defaultValues: {
            employee_code: '',
            org_unit_id: '',
            job_title: '',
            hire_date: '',
            termination_date: '',
            portal_user_enabled: true,
            user_email: '',
            user_first_name: '',
            user_last_name: '',
            user_phone: '',
            user_active: true,
        },
    });

    const organizationUnitsQuery = useOrganizationUnits({ tenant_id: tenantId, per_page: 100, sort: 'name' });
    const createMutation = useCreateEmployee();

    async function onSubmit(values: EmployeeFormValues) {
        setFormError(null);

        try {
            const employee = await createMutation.mutateAsync({
                tenant_id: tenantId,
                employee_code: values.employee_code ?? null,
                org_unit_id: values.org_unit_id ?? null,
                job_title: values.job_title ?? null,
                hire_date: values.hire_date || null,
                termination_date: values.termination_date || null,
                user: values.portal_user_enabled
                    ? {
                          email: values.user_email ?? '',
                          first_name: values.user_first_name ?? '',
                          last_name: values.user_last_name ?? '',
                          phone: values.user_phone ?? null,
                          active: values.user_active,
                      }
                    : undefined,
            });

            showToast({
                title: 'Employee created',
                description: `${employee.employee_code ?? `Employee #${employee.id}`} is ready for workforce and access workflows.`,
                tone: 'success',
            });
            navigate(`/employees/${employee.id}`);
        } catch (error) {
            if (error instanceof ValidationError) {
                applyValidationErrors(error.errors, form.setError, {
                    onUnhandled: (message) => setFormError(message),
                });
                return;
            }

            setFormError(error instanceof Error ? error.message : 'Unable to create employee.');
        }
    }

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[{ label: 'Employees' }, { label: 'Employee List', href: '/employees' }, { label: 'Add Employee' }]}
                description="Create employees with linked user profiles using the same large-card master-data form style already established in this workspace."
                title="Add Employee"
            />

            <ContentCard>
                {organizationUnitsQuery.isPending ? (
                    <LoadingState lines={8} />
                ) : organizationUnitsQuery.isError ? (
                    isForbiddenError(organizationUnitsQuery.error) ? (
                        <ProtectedErrorState description={organizationUnitsQuery.error.message} />
                    ) : (
                        <ErrorState description={organizationUnitsQuery.error.message} title="Unable to load employee setup lookups" />
                    )
                ) : (
                    <EmployeeForm
                        form={form}
                        formError={formError}
                        isSubmitting={createMutation.isPending}
                        mode="create"
                        onSubmit={onSubmit}
                        organizationUnits={organizationUnitsQuery.data?.items ?? []}
                    />
                )}
            </ContentCard>
        </div>
    );
}
