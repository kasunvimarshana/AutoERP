import { useEffect, useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { useNavigate, useParams } from 'react-router-dom';
import { Button } from '../../../components/ui/Button';
import { ContentCard } from '../../../components/ui/ContentCard';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { PageHeader } from '../../../components/layout/PageHeader';
import { ValidationError } from '../../../api/client';
import { applyValidationErrors } from '../../../lib/applyValidationErrors';
import { useTenant } from '../../auth/context/TenantContext';
import { UnitOfMeasureForm } from '../components/UnitOfMeasureForm';
import { useUnitOfMeasure, useUpdateUnitOfMeasure } from '../hooks';
import { unitOfMeasureFormSchema, type UnitOfMeasureFormInput, type UnitOfMeasureFormValues } from '../schemas';
import { parsePositiveInteger } from '../utils';

export function UnitOfMeasureEditPage() {
    const navigate = useNavigate();
    const { tenantId } = useTenant();
    const { unitId: unitIdParam } = useParams();
    const unitId = parsePositiveInteger(unitIdParam ?? null, 0);
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

    const unitQuery = useUnitOfMeasure(unitId, unitId > 0);
    const updateMutation = useUpdateUnitOfMeasure(unitId);

    useEffect(() => {
        if (!unitQuery.data) {
            return;
        }

        form.reset({
            name: unitQuery.data.name,
            symbol: unitQuery.data.symbol,
            type: unitQuery.data.type,
            is_base: unitQuery.data.is_base,
        });
    }, [form, unitQuery.data]);

    async function onSubmit(values: UnitOfMeasureFormValues) {
        setFormError(null);

        try {
            await updateMutation.mutateAsync({
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

            setFormError(error instanceof Error ? error.message : 'Unable to update unit of measure.');
        }
    }

    if (unitId <= 0) {
        return (
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
                <ErrorState description="The unit of measure route is missing a valid ID." title="Invalid unit route" />
            </div>
        );
    }

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[
                    { label: 'Products', href: '/products' },
                    { label: 'Units of Measure', href: '/products/units' },
                    { label: unitQuery.data?.name ?? 'UOM' },
                ]}
                description="Edit unit naming, symbol, and base-unit behavior using the shared product admin form pattern."
                title={unitQuery.data ? `Edit ${unitQuery.data.name}` : 'Edit Unit of Measure'}
            />

            <ContentCard>
                {unitQuery.isPending ? (
                    <LoadingState lines={6} />
                ) : unitQuery.isError ? (
                    <ErrorState
                        action={
                            <Button onClick={() => void unitQuery.refetch()} variant="secondary">
                                Retry
                            </Button>
                        }
                        description={unitQuery.error.message}
                        title="Unable to load unit editor"
                    />
                ) : (
                    <UnitOfMeasureForm form={form} formError={formError} isSubmitting={updateMutation.isPending} mode="edit" onSubmit={onSubmit} />
                )}
            </ContentCard>
        </div>
    );
}
