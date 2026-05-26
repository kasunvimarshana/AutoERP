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
import { useUser } from '../../access/hooks';
import { useOrganizationUnits } from '../../organization/hooks';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { parsePositiveInteger } from '../../shared/utils';
import { EmployeeForm } from '../components/EmployeeForm';
import { useEmployee, useUpdateEmployee } from '../hooks';
import { employeeFormSchema, type EmployeeFormInput, type EmployeeFormValues } from '../schemas';

export function EmployeeEditPage() {
    const navigate = useNavigate();
    const { showToast } = useToast();
    const { tenantId } = useTenant();
    const { employeeId: employeeIdParam } = useParams();
    const employeeId = parsePositiveInteger(employeeIdParam ?? null, 0);
    const [formError, setFormError] = useState<string | null>(null);
    const form = useForm<EmployeeFormInput, unknown, EmployeeFormValues>({
        resolver: zodResolver(employeeFormSchema),
        defaultValues: {
            employee_code: '',
            org_unit_id: '',
            job_title: '',
            hire_date: '',
            termination_date: '',
            portal_user_enabled: false,
            user_email: '',
            user_first_name: '',
            user_last_name: '',
            user_phone: '',
            user_active: true,
        },
    });

    const employeeQuery = useEmployee(employeeId, employeeId > 0);
    const userQuery = useUser(employeeQuery.data?.user_id ?? 0, undefined, Boolean(employeeQuery.data?.user_id));
    const organizationUnitsQuery = useOrganizationUnits({ tenant_id: tenantId, per_page: 100, sort: 'name' });
    const updateMutation = useUpdateEmployee(employeeId);

    useEffect(() => {
        if (!employeeQuery.data) {
            return;
        }

        form.reset({
            employee_code: employeeQuery.data.employee_code ?? '',
            org_unit_id: employeeQuery.data.org_unit_id ?? '',
            job_title: employeeQuery.data.job_title ?? '',
            hire_date: employeeQuery.data.hire_date ?? '',
            termination_date: employeeQuery.data.termination_date ?? '',
            portal_user_enabled: Boolean(employeeQuery.data.user_id),
            user_email: userQuery.data?.email ?? '',
            user_first_name: userQuery.data?.first_name ?? '',
            user_last_name: userQuery.data?.last_name ?? '',
            user_phone: userQuery.data?.phone ?? '',
            user_active: userQuery.data?.active ?? true,
        });
    }, [employeeQuery.data, form, userQuery.data]);

    async function onSubmit(values: EmployeeFormValues) {
        if (employeeId <= 0) {
            return;
        }

        setFormError(null);

        try {
            const employee = await updateMutation.mutateAsync({
                tenant_id: tenantId,
                employee_code: values.employee_code ?? null,
                org_unit_id: values.org_unit_id ?? null,
                job_title: values.job_title ?? null,
                hire_date: values.hire_date || null,
                termination_date: values.termination_date || null,
                user:
                    values.portal_user_enabled || values.user_email || values.user_first_name || values.user_last_name || values.user_phone
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
                title: 'Employee updated',
                description: `${employee.employee_code ?? `Employee #${employee.id}`} has been updated successfully.`,
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

            setFormError(error instanceof Error ? error.message : 'Unable to update employee.');
        }
    }

    if (employeeId <= 0) {
        return (
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
                <ErrorState description="The employee route is missing a valid employee ID." title="Invalid employee route" />
            </div>
        );
    }

    const lookupError = employeeQuery.error ?? organizationUnitsQuery.error ?? userQuery.error;

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[{ label: 'Employees' }, { label: 'Employee List', href: '/employees' }, { label: employeeQuery.data?.employee_code ?? 'Employee' }, { label: 'Edit' }]}
                description="Maintain employee records, linked user identity, and employment dates using the same CRUD shell as the rest of the master-data workspace."
                title={employeeQuery.data ? `Edit ${employeeQuery.data.employee_code ?? `Employee #${employeeQuery.data.id}`}` : 'Edit Employee'}
            />

            <ContentCard>
                {employeeQuery.isPending || organizationUnitsQuery.isPending || userQuery.isPending ? (
                    <LoadingState lines={8} />
                ) : lookupError ? (
                    isForbiddenError(lookupError) ? (
                        <ProtectedErrorState description={lookupError.message} />
                    ) : (
                        <ErrorState description={lookupError.message} title="Unable to load employee editor" />
                    )
                ) : (
                    <EmployeeForm
                        form={form}
                        formError={formError}
                        isSubmitting={updateMutation.isPending}
                        mode="edit"
                        onSubmit={onSubmit}
                        organizationUnits={organizationUnitsQuery.data?.items ?? []}
                    />
                )}
            </ContentCard>
        </div>
    );
}
