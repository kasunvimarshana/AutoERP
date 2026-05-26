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
import { SupplierForm } from '../components/SupplierForm';
import { useSupplier, useUpdateSupplier } from '../hooks';
import { supplierFormSchema, type SupplierFormInput, type SupplierFormValues } from '../schemas';

export function SupplierEditPage() {
    const navigate = useNavigate();
    const { showToast } = useToast();
    const { tenantId } = useTenant();
    const { supplierId: supplierIdParam } = useParams();
    const supplierId = parsePositiveInteger(supplierIdParam ?? null, 0);
    const [formError, setFormError] = useState<string | null>(null);
    const form = useForm<SupplierFormInput, unknown, SupplierFormValues>({
        resolver: zodResolver(supplierFormSchema),
        defaultValues: {
            supplier_code: '',
            name: '',
            type: 'company',
            org_unit_id: '',
            tax_number: '',
            registration_number: '',
            currency_id: '',
            payment_terms_days: '',
            ap_account_id: '',
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

    const supplierQuery = useSupplier(supplierId, supplierId > 0);
    const relatedUserQuery = useUser(supplierQuery.data?.user_id ?? 0, undefined, Boolean(supplierQuery.data?.user_id));
    const organizationUnitsQuery = useOrganizationUnits({ tenant_id: tenantId, per_page: 100, sort: 'name' });
    const updateMutation = useUpdateSupplier(supplierId);

    useEffect(() => {
        if (!supplierQuery.data) {
            return;
        }

        form.reset({
            supplier_code: supplierQuery.data.supplier_code ?? '',
            name: supplierQuery.data.name,
            type: supplierQuery.data.type,
            org_unit_id: supplierQuery.data.org_unit_id ?? '',
            tax_number: supplierQuery.data.tax_number ?? '',
            registration_number: supplierQuery.data.registration_number ?? '',
            currency_id: supplierQuery.data.currency_id ?? '',
            payment_terms_days: supplierQuery.data.payment_terms_days ? String(supplierQuery.data.payment_terms_days) : '',
            ap_account_id: supplierQuery.data.ap_account_id ?? '',
            status: supplierQuery.data.status,
            notes: supplierQuery.data.notes ?? '',
            portal_user_enabled: Boolean(supplierQuery.data.user_id),
            user_email: relatedUserQuery.data?.email ?? '',
            user_first_name: relatedUserQuery.data?.first_name ?? '',
            user_last_name: relatedUserQuery.data?.last_name ?? '',
            user_phone: relatedUserQuery.data?.phone ?? '',
            user_active: relatedUserQuery.data?.active ?? true,
        });
    }, [form, relatedUserQuery.data, supplierQuery.data]);

    async function onSubmit(values: SupplierFormValues) {
        if (supplierId <= 0) {
            return;
        }

        setFormError(null);

        try {
            const supplier = await updateMutation.mutateAsync({
                tenant_id: tenantId,
                supplier_code: values.supplier_code ?? null,
                name: values.name,
                type: values.type,
                org_unit_id: values.org_unit_id ?? null,
                tax_number: values.tax_number ?? null,
                registration_number: values.registration_number ?? null,
                currency_id: values.currency_id ?? null,
                payment_terms_days: values.payment_terms_days ?? null,
                ap_account_id: values.ap_account_id ?? null,
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
                title: 'Supplier updated',
                description: `${supplier.name} has been updated successfully.`,
                tone: 'success',
            });
            navigate(`/suppliers/${supplier.id}`);
        } catch (error) {
            if (error instanceof ValidationError) {
                applyValidationErrors(error.errors, form.setError, {
                    onUnhandled: (message) => setFormError(message),
                });
                return;
            }

            setFormError(error instanceof Error ? error.message : 'Unable to update supplier.');
        }
    }

    if (supplierId <= 0) {
        return (
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
                <ErrorState description="The supplier route is missing a valid supplier ID." title="Invalid supplier route" />
            </div>
        );
    }

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[{ label: 'Suppliers', href: '/suppliers' }, { label: supplierQuery.data?.name ?? 'Supplier' }, { label: 'Edit' }]}
                description="Maintain supplier account data, linked portal ownership, and procurement defaults using the shared CRUD form pattern."
                title={supplierQuery.data ? `Edit ${supplierQuery.data.name}` : 'Edit Supplier'}
            />

            <ContentCard>
                {supplierQuery.isPending || organizationUnitsQuery.isPending || relatedUserQuery.isPending ? (
                    <LoadingState lines={8} />
                ) : supplierQuery.isError || organizationUnitsQuery.isError || relatedUserQuery.isError ? (
                    (() => {
                        const error = supplierQuery.error ?? organizationUnitsQuery.error ?? relatedUserQuery.error;
                        return isForbiddenError(error) ? (
                            <ProtectedErrorState description={error.message} />
                        ) : (
                            <ErrorState description={error?.message ?? 'Unable to load supplier editor.'} title="Unable to load supplier editor" />
                        );
                    })()
                ) : (
                    <SupplierForm
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
