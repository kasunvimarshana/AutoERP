import { useEffect, useMemo, useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Link } from 'react-router-dom';
import { Button } from '../../../components/ui/Button';
import { ContentCard } from '../../../components/ui/ContentCard';
import { ConfirmModal } from '../../../components/feedback/ConfirmModal';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { PageHeader } from '../../../components/layout/PageHeader';
import { DataTable, type DataTableColumn } from '../../../components/tables';
import { SectionCard } from '../../../components/forms/SectionCard';
import { ValidationError } from '../../../api/client';
import { useToast } from '../../../app/providers/ToastProvider';
import { applyValidationErrors } from '../../../lib/applyValidationErrors';
import { UomConversionForm } from '../components/UomConversionForm';
import { useCreateUomConversion, useDeleteUomConversion, useUnitsOfMeasure, useUomConversions, useUpdateUomConversion } from '../hooks';
import { uomConversionFormSchema, type UomConversionFormInput, type UomConversionFormValues } from '../schemas';
import type { UomConversion } from '../types';
import { useTenant } from '../../auth/context/TenantContext';

export function UomConversionPage() {
    const { showToast } = useToast();
    const { tenantId } = useTenant();
    const [editingConversion, setEditingConversion] = useState<UomConversion | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<UomConversion | null>(null);
    const [formError, setFormError] = useState<string | null>(null);
    const unitsQuery = useUnitsOfMeasure({ tenant_id: tenantId, per_page: 100, sort: 'name' });
    const conversionsQuery = useUomConversions({ per_page: 100, sort: '-updated_at' });
    const createMutation = useCreateUomConversion();
    const updateMutation = useUpdateUomConversion(editingConversion?.id ?? 0);
    const deleteMutation = useDeleteUomConversion();

    const form = useForm<UomConversionFormInput, unknown, UomConversionFormValues>({
        resolver: zodResolver(uomConversionFormSchema),
        defaultValues: {
            from_uom_id: '',
            to_uom_id: '',
            factor: '',
        },
    });

    useEffect(() => {
        if (!editingConversion) {
            form.reset({ from_uom_id: '', to_uom_id: '', factor: '' });
            return;
        }

        form.reset({
            from_uom_id: editingConversion.from_uom_id,
            to_uom_id: editingConversion.to_uom_id,
            factor: String(editingConversion.factor),
        });
    }, [editingConversion, form]);

    const unitMap = useMemo(() => {
        return new Map((unitsQuery.data?.items ?? []).map((unit) => [unit.id, `${unit.name} (${unit.symbol})`]));
    }, [unitsQuery.data?.items]);

    async function handleSubmit(values: UomConversionFormValues) {
        setFormError(null);

        try {
            const payload = {
                from_uom_id: values.from_uom_id,
                to_uom_id: values.to_uom_id,
                factor: values.factor,
            };

            if (editingConversion) {
                await updateMutation.mutateAsync(payload);
                showToast({ title: 'Conversion updated', description: 'The UOM conversion rule was updated successfully.', tone: 'success' });
            } else {
                await createMutation.mutateAsync(payload);
                showToast({ title: 'Conversion added', description: 'A new UOM conversion rule is now available.', tone: 'success' });
            }

            setEditingConversion(null);
        } catch (error) {
            if (error instanceof ValidationError) {
                applyValidationErrors(error.errors, form.setError, {
                    onUnhandled: (message) => setFormError(message),
                });
                return;
            }

            setFormError(error instanceof Error ? error.message : 'Unable to save UOM conversion.');
        }
    }

    async function handleDeleteConfirm() {
        if (!deleteTarget) {
            return;
        }

        await deleteMutation.mutateAsync(deleteTarget.id);
        showToast({ title: 'Conversion deleted', description: 'The UOM conversion rule was removed.', tone: 'success' });
        setDeleteTarget(null);
    }

    const columns: DataTableColumn<UomConversion>[] = [
        {
            key: 'from',
            header: 'From UOM',
            render: (conversion) => <span className="text-sm text-stone-700">{unitMap.get(conversion.from_uom_id) ?? `#${conversion.from_uom_id}`}</span>,
        },
        {
            key: 'to',
            header: 'To UOM',
            render: (conversion) => <span className="text-sm text-stone-700">{unitMap.get(conversion.to_uom_id) ?? `#${conversion.to_uom_id}`}</span>,
        },
        {
            key: 'factor',
            header: 'Factor',
            render: (conversion) => <span className="font-medium text-stone-950">{conversion.factor}</span>,
        },
        {
            key: 'actions',
            header: 'Actions',
            className: 'w-[12rem]',
            render: (conversion) => (
                <div className="flex flex-wrap gap-2">
                    <Button className="h-9 px-3 text-xs" onClick={() => setEditingConversion(conversion)} type="button" variant="secondary">
                        Edit
                    </Button>
                    <Button className="h-9 px-3 text-xs" onClick={() => setDeleteTarget(conversion)} type="button" variant="secondary">
                        Delete
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                actions={
                    <Link to="/products/units">
                        <Button variant="secondary">Back to Units</Button>
                    </Link>
                }
                breadcrumbs={[{ label: 'Products', href: '/products' }, { label: 'Units of Measure', href: '/products/units' }, { label: 'Conversions' }]}
                description="Manage conversion rules between units of measure so purchase, stock, and sales quantities stay aligned."
                title="UOM Conversions"
            />

            <div className="grid gap-4 xl:grid-cols-[1.05fr_0.95fr]">
                <ContentCard className="p-0">
                    {unitsQuery.isPending || conversionsQuery.isPending ? (
                        <LoadingState className="m-6" lines={8} />
                    ) : unitsQuery.isError || conversionsQuery.isError ? (
                        <ErrorState
                            action={
                                <Button
                                    onClick={() => {
                                        void unitsQuery.refetch();
                                        void conversionsQuery.refetch();
                                    }}
                                    variant="secondary"
                                >
                                    Retry
                                </Button>
                            }
                            className="m-6"
                            description={(unitsQuery.error ?? conversionsQuery.error)?.message ?? 'Unable to load conversions.'}
                            title="Unable to load UOM conversions"
                        />
                    ) : (
                        <DataTable
                            columns={columns}
                            emptyState={<EmptyState className="m-6" description="No UOM conversions have been configured yet." title="No conversions found" />}
                            getRowKey={(conversion) => conversion.id}
                            rows={conversionsQuery.data.items}
                        />
                    )}
                </ContentCard>

                <SectionCard
                    description="Use the shared form pattern to add or edit conversion rules between units."
                    title={editingConversion ? 'Edit conversion rule' : 'Add conversion rule'}
                >
                    {formError ? <div className="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{formError}</div> : null}
                    {unitsQuery.isPending ? (
                        <LoadingState lines={5} />
                    ) : unitsQuery.isError ? (
                        <ErrorState description={unitsQuery.error.message} title="Unable to load units" />
                    ) : (
                        <UomConversionForm
                            form={form}
                            isSubmitting={createMutation.isPending || updateMutation.isPending}
                            mode={editingConversion ? 'edit' : 'create'}
                            onCancel={() => {
                                setEditingConversion(null);
                                setFormError(null);
                            }}
                            onSubmit={handleSubmit}
                            units={unitsQuery.data?.items ?? []}
                        />
                    )}
                </SectionCard>
            </div>

            <ConfirmModal
                confirmLabel="Delete conversion"
                description={
                    deleteTarget
                        ? `Delete the conversion from ${unitMap.get(deleteTarget.from_uom_id) ?? deleteTarget.from_uom_id} to ${
                              unitMap.get(deleteTarget.to_uom_id) ?? deleteTarget.to_uom_id
                          }?`
                        : ''
                }
                isLoading={deleteMutation.isPending}
                onCancel={() => setDeleteTarget(null)}
                onConfirm={() => void handleDeleteConfirm()}
                open={Boolean(deleteTarget)}
                title="Delete conversion"
            />
        </div>
    );
}
