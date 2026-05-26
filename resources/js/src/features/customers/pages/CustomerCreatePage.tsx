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
import { CustomerForm } from '../components/CustomerForm';
import { useCreateCustomer } from '../hooks';
import { customerFormSchema, type CustomerFormInput, type CustomerFormValues } from '../schemas';

export function CustomerCreatePage() {
    const navigate = useNavigate();
    const { showToast } = useToast();
    const { tenantId } = useTenant();
    const [formError, setFormError] = useState<string | null>(null);
    const form = useForm<CustomerFormInput, unknown, CustomerFormValues>({
        resolver: zodResolver(customerFormSchema),
        defaultValues: {
            customer_code: '',
            name: '',
            type: 'company',
            org_unit_id: '',
            tax_number: '',
            registration_number: '',
            currency_id: '',
            credit_limit: '',
            payment_terms_days: '30',
            ar_account_id: '',
            status: 'active',
            notes: '',
            portal_user_enabled: true,
            user_email: '',
            user_first_name: '',
            user_last_name: '',
            user_phone: '',
            user_active: true,
        },
    });

    const organizationUnitsQuery = useOrganizationUnits({ tenant_id: tenantId, per_page: 100, sort: 'name' });
    const createMutation = useCreateCustomer();

    async function onSubmit(values: CustomerFormValues) {
        setFormError(null);

        try {
            const customer = await createMutation.mutateAsync({
                tenant_id: tenantId,
                customer_code: values.customer_code ?? null,
                name: values.name,
                type: values.type,
                org_unit_id: values.org_unit_id ?? null,
                tax_number: values.tax_number ?? null,
                registration_number: values.registration_number ?? null,
                currency_id: values.currency_id ?? null,
                credit_limit: values.credit_limit ?? null,
                payment_terms_days: values.payment_terms_days ?? null,
                ar_account_id: values.ar_account_id ?? null,
                status: values.status,
                notes: values.notes ?? null,
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
                title: 'Customer created',
                description: `${customer.name} is ready for contacts, addresses, pricing, and receivables workflows.`,
                tone: 'success',
            });
            navigate(`/customers/${customer.id}`);
        } catch (error) {
            if (error instanceof ValidationError) {
                applyValidationErrors(error.errors, form.setError, {
                    onUnhandled: (message) => setFormError(message),
                });
                return;
            }

            setFormError(error instanceof Error ? error.message : 'Unable to create customer.');
        }
    }

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[{ label: 'Customers', href: '/customers' }, { label: 'Add Customer' }]}
                description="Use the same large-card master-data form pattern to create customer accounts with commercial defaults and linked portal ownership."
                title="Add Customer"
            />

            <ContentCard>
                {organizationUnitsQuery.isPending ? (
                    <LoadingState lines={8} />
                ) : organizationUnitsQuery.isError ? (
                    isForbiddenError(organizationUnitsQuery.error) ? (
                        <ProtectedErrorState description={organizationUnitsQuery.error.message} />
                    ) : (
                        <ErrorState description={organizationUnitsQuery.error.message} title="Unable to load customer setup lookups" />
                    )
                ) : (
                    <CustomerForm
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
