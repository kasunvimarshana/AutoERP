import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useNavigate } from 'react-router-dom';
import { PageHeader } from '../../../components/layout/PageHeader';
import { ContentCard } from '../../../components/ui/ContentCard';
import { ValidationError } from '../../../api/client';
import { applyValidationErrors } from '../../../lib/applyValidationErrors';
import { useTenant } from '../../auth/context/TenantContext';
import { UnitOfMeasureForm } from '../components/UnitOfMeasureForm';
import { useCreateUnitOfMeasure } from '../hooks';
import { unitOfMeasureFormSchema, type UnitOfMeasureFormInput, type UnitOfMeasureFormValues } from '../schemas';

export function UnitOfMeasureCreatePage() {
    const navigate = useNavigate();
    const { tenantId } = useTenant();
    const [formError, setFormError] = useState<string | null>(null);
    const form = useForm<UnitOfMeasureFormInput, unknown, UnitOfMeasureFormValues>({
        resolver: zodResolver(unitOfMeasureFormSchema),
        defaultValues: {
            name: '',
            symbol: '',
            type: 'unit',
            is_base: false,
        },
    });
    const createMutation = useCreateUnitOfMeasure();

    async function onSubmit(values: UnitOfMeasureFormValues) {
        setFormError(null);

        try {
            await createMutation.mutateAsync({
                tenant_id: tenantId,
                name: values.name,
                symbol: values.symbol,
                type: values.type,
                is_base: values.is_base,
            });

            navigate('/products/units');
        } catch (error) {
            if (error instanceof ValidationError) {
                applyValidationErrors(error.errors, form.setError, {
                    onUnhandled: (message) => setFormError(message),
                });
                return;
            }

            setFormError(error instanceof Error ? error.message : 'Unable to create unit of measure.');
        }
    }

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[
                    { label: 'Products', href: '/products' },
                    { label: 'Units of Measure', href: '/products/units' },
                    { label: 'Add UOM' },
                ]}
                description="Add a unit of measure using the same shared form language already used by the product foundation."
                title="Add Unit of Measure"
            />

            <ContentCard>
                <UnitOfMeasureForm form={form} formError={formError} isSubmitting={createMutation.isPending} mode="create" onSubmit={onSubmit} />
            </ContentCard>
        </div>
    );
}
