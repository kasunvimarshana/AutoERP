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
import { parsePositiveInteger } from '../../shared/utils';
import { CustomerForm } from '../components/CustomerForm';
import { useCustomer, useUpdateCustomer } from '../hooks';
import { customerFormSchema, type CustomerFormInput, type CustomerFormValues } from '../schemas';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';

export function CustomerEditPage() {
    const navigate = useNavigate();
    const { showToast } = useToast();
    const { tenantId } = useTenant();
    const { customerId: customerIdParam } = useParams();
    const customerId = parsePositiveInteger(customerIdParam ?? null, 0);
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
            payment_terms_days: '',
            ar_account_id: '',
            status: 'active',
            notes: '',
            portal_user_enabled: false,
            user_email: '',
            user_first_name: '',
            user_last_name: '',
            user_phone: '',
            user_active: true,
        },
    });

    const customerQuery = useCustomer(customerId, customerId > 0);
    const relatedUserQuery = useUser(customerQuery.data?.user_id ?? 0, undefined, Boolean(customerQuery.data?.user_id));
    const organizationUnitsQuery = useOrganizationUnits({ tenant_id: tenantId, per_page: 100, sort: 'name' });
    const updateMutation = useUpdateCustomer(customerId);

    useEffect(() => {
        if (!customerQuery.data) {
            return;
        }

        form.reset({
            customer_code: customerQuery.data.customer_code ?? '',
            name: customerQuery.data.name,
            type: customerQuery.data.type,
            org_unit_id: customerQuery.data.org_unit_id ?? '',
            tax_number: customerQuery.data.tax_number ?? '',
            registration_number: customerQuery.data.registration_number ?? '',
            currency_id: customerQuery.data.currency_id ?? '',
            credit_limit: customerQuery.data.credit_limit ? String(customerQuery.data.credit_limit) : '',
            payment_terms_days: customerQuery.data.payment_terms_days ? String(customerQuery.data.payment_terms_days) : '',
            ar_account_id: customerQuery.data.ar_account_id ?? '',
            status: customerQuery.data.status,
            notes: customerQuery.data.notes ?? '',
            portal_user_enabled: Boolean(customerQuery.data.user_id),
            user_email: relatedUserQuery.data?.email ?? '',
            user_first_name: relatedUserQuery.data?.first_name ?? '',
            user_last_name: relatedUserQuery.data?.last_name ?? '',
            user_phone: relatedUserQuery.data?.phone ?? '',
            user_active: relatedUserQuery.data?.active ?? true,
        });
    }, [customerQuery.data, form, relatedUserQuery.data]);

    async function onSubmit(values: CustomerFormValues) {
        if (customerId <= 0) {
            return;
        }

        setFormError(null);

        try {
            const customer = await updateMutation.mutateAsync({
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
                title: 'Customer updated',
                description: `${customer.name} has been updated successfully.`,
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

            setFormError(error instanceof Error ? error.message : 'Unable to update customer.');
        }
    }

    if (customerId <= 0) {
        return (
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
                <ErrorState description="The customer route is missing a valid customer ID." title="Invalid customer route" />
            </div>
        );
    }

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[{ label: 'Customers', href: '/customers' }, { label: customerQuery.data?.name ?? 'Customer' }, { label: 'Edit' }]}
                description="Maintain customer account data, linked portal ownership, and commercial defaults using the same shared CRUD form structure."
                title={customerQuery.data ? `Edit ${customerQuery.data.name}` : 'Edit Customer'}
            />

            <ContentCard>
                {customerQuery.isPending || organizationUnitsQuery.isPending || relatedUserQuery.isPending ? (
                    <LoadingState lines={8} />
                ) : customerQuery.isError || organizationUnitsQuery.isError || relatedUserQuery.isError ? (
                    (() => {
                        const error = customerQuery.error ?? organizationUnitsQuery.error ?? relatedUserQuery.error;
                        return isForbiddenError(error) ? (
                            <ProtectedErrorState description={error.message} />
                        ) : (
                            <ErrorState description={error?.message ?? 'Unable to load customer editor.'} title="Unable to load customer editor" />
                        );
                    })()
                ) : (
                    <CustomerForm
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
