import { useEffect, useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Button } from '../../../components/ui/Button';
import { ContentCard } from '../../../components/ui/ContentCard';
import { ConfirmModal } from '../../../components/feedback/ConfirmModal';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { PageHeader } from '../../../components/layout/PageHeader';
import { ActionBar } from '../../../components/forms/ActionBar';
import { ValidationError } from '../../../api/client';
import { useToast } from '../../../app/providers/ToastProvider';
import { applyValidationErrors } from '../../../lib/applyValidationErrors';
import { useTenant } from '../../auth/context/TenantContext';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { useCreateOrganizationUnitType, useDeleteOrganizationUnitType, useOrganizationUnitTypes, useUpdateOrganizationUnitType } from '../hooks';
import { OrganizationUnitTypeForm } from '../components/OrganizationUnitTypeForm';
import { organizationUnitTypeFormSchema, type OrganizationUnitTypeFormInput, type OrganizationUnitTypeFormValues } from '../schemas';
import type { OrganizationUnitTypeRecord } from '../types';

export function OrganizationUnitTypesPage() {
    const { showToast } = useToast();
    const { tenantId } = useTenant();
    const [selectedType, setSelectedType] = useState<OrganizationUnitTypeRecord | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<OrganizationUnitTypeRecord | null>(null);
    const [formError, setFormError] = useState<string | null>(null);
    const form = useForm<OrganizationUnitTypeFormInput, unknown, OrganizationUnitTypeFormValues>({
        resolver: zodResolver(organizationUnitTypeFormSchema),
        defaultValues: {
            name: '',
            level: 0,
            is_active: true,
        },
    });

    const typesQuery = useOrganizationUnitTypes({ tenant_id: tenantId, per_page: 100, page: 1, sort: 'level' });
    const createMutation = useCreateOrganizationUnitType();
    const updateMutation = useUpdateOrganizationUnitType(selectedType?.id ?? 0);
    const deleteMutation = useDeleteOrganizationUnitType();

    useEffect(() => {
        if (!selectedType) {
            form.reset({ name: '', level: 0, is_active: true });
            return;
        }

        form.reset({
            name: selectedType.name,
            level: selectedType.level,
            is_active: selectedType.is_active,
        });
    }, [form, selectedType]);

    async function onSubmit(values: OrganizationUnitTypeFormValues) {
        setFormError(null);

        try {
            if (selectedType) {
                await updateMutation.mutateAsync({
                    tenant_id: tenantId,
                    name: values.name,
                    level: values.level,
                    is_active: values.is_active,
                });

                showToast({
                    title: 'Unit type updated',
                    description: `${values.name} has been updated successfully.`,
                    tone: 'success',
                });
            } else {
                await createMutation.mutateAsync({
                    tenant_id: tenantId,
                    name: values.name,
                    level: values.level,
                    is_active: values.is_active,
                });

                showToast({
                    title: 'Unit type created',
                    description: `${values.name} is now available for the hierarchy.`,
                    tone: 'success',
                });
            }

            setSelectedType(null);
        } catch (error) {
            if (error instanceof ValidationError) {
                applyValidationErrors(error.errors, form.setError, {
                    onUnhandled: (message) => setFormError(message),
                });
                return;
            }

            setFormError(error instanceof Error ? error.message : 'Unable to save organization unit type.');
        }
    }

    async function handleDeleteConfirm() {
        if (!deleteTarget) {
            return;
        }

        const target = deleteTarget;
        await deleteMutation.mutateAsync(target.id);
        setDeleteTarget(null);
        if (selectedType?.id === target.id) {
            setSelectedType(null);
        }

        showToast({
            title: 'Unit type deleted',
            description: `${target.name} has been removed from the taxonomy.`,
            tone: 'success',
        });
    }

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[{ label: 'Organization' }, { label: 'Organization Unit Types' }]}
                description="Organization unit taxonomy sits alongside the hierarchy so structural maintenance stays close to the working model."
                title="Organization Unit Types"
            />

            <div className="grid gap-4 xl:grid-cols-[1fr_0.9fr]">
                <ContentCard className="p-0">
                    {typesQuery.isPending ? (
                        <LoadingState className="m-6" lines={6} />
                    ) : typesQuery.isError ? (
                        isForbiddenError(typesQuery.error) ? (
                            <ProtectedErrorState className="m-6" description={typesQuery.error.message} />
                        ) : (
                            <ErrorState className="m-6" description={typesQuery.error.message} title="Unable to load organization unit types" />
                        )
                    ) : typesQuery.data?.items.length ? (
                        <div className="overflow-x-auto">
                            <table className="min-w-full text-left text-sm">
                                <thead className="border-b border-stone-200/80 bg-stone-50/70 text-xs uppercase tracking-[0.16em] text-stone-500">
                                    <tr>
                                        <th className="px-6 py-4 font-medium">Type</th>
                                        <th className="px-6 py-4 font-medium">Level</th>
                                        <th className="px-6 py-4 font-medium">Status</th>
                                        <th className="px-6 py-4 font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-stone-200/70">
                                    {typesQuery.data.items.map((organizationUnitType) => (
                                        <tr key={organizationUnitType.id} className={selectedType?.id === organizationUnitType.id ? 'bg-stone-50/70' : undefined}>
                                            <td className="px-6 py-4 font-medium text-stone-950">{organizationUnitType.name}</td>
                                            <td className="px-6 py-4 text-stone-700">{organizationUnitType.level}</td>
                                            <td className="px-6 py-4">
                                                <span className={organizationUnitType.is_active ? 'inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700' : 'inline-flex rounded-full bg-stone-100 px-2.5 py-1 text-xs font-semibold text-stone-700'}>
                                                    {organizationUnitType.is_active ? 'Active' : 'Inactive'}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex flex-wrap gap-2">
                                                    <Button className="h-9 px-3 text-xs" onClick={() => setSelectedType(organizationUnitType)} type="button" variant="secondary">
                                                        Edit
                                                    </Button>
                                                    <Button className="h-9 px-3 text-xs" onClick={() => setDeleteTarget(organizationUnitType)} type="button" variant="secondary">
                                                        Delete
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <EmptyState className="m-6" description="No organization unit types are configured yet." title="No unit types found" />
                    )}
                </ContentCard>

                <ContentCard>
                    <div className="space-y-6">
                        <div className="flex items-center justify-between gap-3">
                            <div>
                                <h2 className="text-lg font-semibold text-stone-950">{selectedType ? `Edit ${selectedType.name}` : 'Add Organization Unit Type'}</h2>
                                <p className="mt-1 text-sm text-stone-600">Use the side editor to manage the hierarchy taxonomy without leaving the list.</p>
                            </div>
                            {selectedType ? (
                                <Button onClick={() => setSelectedType(null)} type="button" variant="secondary">
                                    New Type
                                </Button>
                            ) : null}
                        </div>

                        {!typesQuery.isPending && !typesQuery.isError ? (
                            <form onSubmit={form.handleSubmit(onSubmit)}>
                                <div className="space-y-6">
                                    <OrganizationUnitTypeForm form={form} />
                                    {formError ? <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{formError}</div> : null}
                                    <ActionBar>
                                        <Button type="submit">{createMutation.isPending || updateMutation.isPending ? 'Saving...' : selectedType ? 'Save Changes' : 'Create Unit Type'}</Button>
                                    </ActionBar>
                                </div>
                            </form>
                        ) : null}
                    </div>
                </ContentCard>
            </div>

            <ConfirmModal
                confirmLabel="Delete unit type"
                description={deleteTarget ? `Delete ${deleteTarget.name}? This action cannot be undone from the current UI.` : ''}
                isLoading={deleteMutation.isPending}
                onCancel={() => setDeleteTarget(null)}
                onConfirm={() => void handleDeleteConfirm()}
                open={Boolean(deleteTarget)}
                title="Delete organization unit type"
            />
        </div>
    );
}
