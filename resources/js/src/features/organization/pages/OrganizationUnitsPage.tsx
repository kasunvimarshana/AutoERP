import { useEffect, useMemo, useState } from 'react';
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
import { useUsers } from '../../access/hooks';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { useCreateOrganizationUnit, useDeleteOrganizationUnit, useOrganizationUnits, useOrganizationUnitTypes, useUpdateOrganizationUnit } from '../hooks';
import { OrganizationUnitForm } from '../components/OrganizationUnitForm';
import { organizationUnitFormSchema, type OrganizationUnitFormInput, type OrganizationUnitFormValues } from '../schemas';
import type { OrganizationUnitRecord } from '../types';

export function OrganizationUnitsPage() {
    const { showToast } = useToast();
    const { tenantId } = useTenant();
    const [selectedUnit, setSelectedUnit] = useState<OrganizationUnitRecord | null>(null);
    const [formError, setFormError] = useState<string | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<OrganizationUnitRecord | null>(null);
    const form = useForm<OrganizationUnitFormInput, unknown, OrganizationUnitFormValues>({
        resolver: zodResolver(organizationUnitFormSchema),
        defaultValues: {
            name: '',
            code: '',
            type_id: '',
            parent_id: '',
            manager_user_id: '',
            description: '',
            is_active: true,
        },
    });

    const organizationUnitsQuery = useOrganizationUnits({ tenant_id: tenantId, per_page: 100, sort: 'path' });
    const organizationUnitTypesQuery = useOrganizationUnitTypes({ tenant_id: tenantId, per_page: 100, page: 1, sort: 'level' });
    const usersQuery = useUsers({ tenant_id: tenantId, per_page: 100, page: 1 });
    const createMutation = useCreateOrganizationUnit();
    const updateMutation = useUpdateOrganizationUnit(selectedUnit?.id ?? 0);
    const deleteMutation = useDeleteOrganizationUnit();

    useEffect(() => {
        if (!selectedUnit) {
            form.reset({
                name: '',
                code: '',
                type_id: '',
                parent_id: '',
                manager_user_id: '',
                description: '',
                is_active: true,
            });
            return;
        }

        form.reset({
            name: selectedUnit.name,
            code: selectedUnit.code ?? '',
            type_id: selectedUnit.type_id ?? '',
            parent_id: selectedUnit.parent_id ?? '',
            manager_user_id: selectedUnit.manager_user_id ?? '',
            description: selectedUnit.description ?? '',
            is_active: selectedUnit.is_active,
        });
    }, [form, selectedUnit]);

    const typeMap = useMemo(
        () => new Map(organizationUnitTypesQuery.data?.items.map((organizationUnitType) => [organizationUnitType.id, organizationUnitType.name]) ?? []),
        [organizationUnitTypesQuery.data?.items],
    );

    async function onSubmit(values: OrganizationUnitFormValues) {
        setFormError(null);

        try {
            if (selectedUnit) {
                await updateMutation.mutateAsync({
                    type_id: values.type_id ?? null,
                    parent_id: values.parent_id ?? null,
                    manager_user_id: values.manager_user_id ?? null,
                    name: values.name,
                    code: values.code ?? null,
                    description: values.description ?? null,
                    is_active: values.is_active,
                });

                showToast({
                    title: 'Organization unit updated',
                    description: `${values.name} has been updated successfully.`,
                    tone: 'success',
                });
            } else {
                await createMutation.mutateAsync({
                    tenant_id: tenantId,
                    type_id: values.type_id ?? null,
                    parent_id: values.parent_id ?? null,
                    manager_user_id: values.manager_user_id ?? null,
                    name: values.name,
                    code: values.code ?? null,
                    description: values.description ?? null,
                    is_active: values.is_active,
                });

                showToast({
                    title: 'Organization unit created',
                    description: `${values.name} has been added to the organization hierarchy.`,
                    tone: 'success',
                });
            }

            setSelectedUnit(null);
        } catch (error) {
            if (error instanceof ValidationError) {
                applyValidationErrors(error.errors, form.setError, {
                    onUnhandled: (message) => setFormError(message),
                });
                return;
            }

            setFormError(error instanceof Error ? error.message : 'Unable to save organization unit.');
        }
    }

    async function handleDeleteConfirm() {
        if (!deleteTarget) {
            return;
        }

        const target = deleteTarget;
        await deleteMutation.mutateAsync(target.id);
        setDeleteTarget(null);
        if (selectedUnit?.id === target.id) {
            setSelectedUnit(null);
        }

        showToast({
            title: 'Organization unit deleted',
            description: `${target.name} has been removed from the hierarchy.`,
            tone: 'success',
        });
    }

    const lookupError = organizationUnitsQuery.error ?? organizationUnitTypesQuery.error ?? usersQuery.error;

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[{ label: 'Organization' }, { label: 'Organization Units' }]}
                description="The organization hierarchy is presented in a tree-aware list with a side editor so parent-child structure and maintenance actions stay visible together."
                title="Organization Units"
            />

            <div className="grid gap-4 xl:grid-cols-[1.15fr_0.85fr]">
                <ContentCard className="p-0">
                    {organizationUnitsQuery.isPending || organizationUnitTypesQuery.isPending || usersQuery.isPending ? (
                        <LoadingState className="m-6" lines={8} />
                    ) : lookupError ? (
                        isForbiddenError(lookupError) ? (
                            <ProtectedErrorState className="m-6" description={lookupError.message} />
                        ) : (
                            <ErrorState className="m-6" description={lookupError.message} title="Unable to load organization hierarchy" />
                        )
                    ) : organizationUnitsQuery.data?.items.length ? (
                        <div className="overflow-x-auto">
                            <table className="min-w-full text-left text-sm">
                                <thead className="border-b border-stone-200/80 bg-stone-50/70 text-xs uppercase tracking-[0.16em] text-stone-500">
                                    <tr>
                                        <th className="px-6 py-4 font-medium">Unit</th>
                                        <th className="px-6 py-4 font-medium">Type</th>
                                        <th className="px-6 py-4 font-medium">Depth</th>
                                        <th className="px-6 py-4 font-medium">Status</th>
                                        <th className="px-6 py-4 font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-stone-200/70">
                                    {organizationUnitsQuery.data.items.map((organizationUnit) => (
                                        <tr key={organizationUnit.id} className={selectedUnit?.id === organizationUnit.id ? 'bg-stone-50/70' : undefined}>
                                            <td className="px-6 py-4">
                                                <button className="flex items-center text-left" onClick={() => setSelectedUnit(organizationUnit)} type="button">
                                                    <span className="font-medium text-stone-950" style={{ marginLeft: `${organizationUnit.depth * 1.25}rem` }}>
                                                        {organizationUnit.name}
                                                    </span>
                                                </button>
                                            </td>
                                            <td className="px-6 py-4 text-stone-700">{organizationUnit.type_id ? typeMap.get(organizationUnit.type_id) ?? `#${organizationUnit.type_id}` : '-'}</td>
                                            <td className="px-6 py-4 text-stone-700">{organizationUnit.depth}</td>
                                            <td className="px-6 py-4">
                                                <span className={organizationUnit.is_active ? 'inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700' : 'inline-flex rounded-full bg-stone-100 px-2.5 py-1 text-xs font-semibold text-stone-700'}>
                                                    {organizationUnit.is_active ? 'Active' : 'Inactive'}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex flex-wrap gap-2">
                                                    <Button className="h-9 px-3 text-xs" onClick={() => setSelectedUnit(organizationUnit)} type="button" variant="secondary">
                                                        Edit
                                                    </Button>
                                                    <Button className="h-9 px-3 text-xs" onClick={() => setDeleteTarget(organizationUnit)} type="button" variant="secondary">
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
                        <EmptyState className="m-6" description="No organization units are available for this tenant yet." title="No organization units found" />
                    )}
                </ContentCard>

                <ContentCard>
                    <div className="space-y-6">
                        <div className="flex items-center justify-between gap-3">
                            <div>
                                <h2 className="text-lg font-semibold text-stone-950">{selectedUnit ? `Edit ${selectedUnit.name}` : 'Add Organization Unit'}</h2>
                                <p className="mt-1 text-sm text-stone-600">Use the side editor to maintain hierarchy, ownership, and status without leaving the tree view.</p>
                            </div>
                            {selectedUnit ? (
                                <Button onClick={() => setSelectedUnit(null)} type="button" variant="secondary">
                                    New Unit
                                </Button>
                            ) : null}
                        </div>

                        {!lookupError && !organizationUnitsQuery.isPending && !organizationUnitTypesQuery.isPending && !usersQuery.isPending ? (
                            <form onSubmit={form.handleSubmit(onSubmit)}>
                                <div className="space-y-6">
                                    <OrganizationUnitForm
                                        form={form}
                                        managerUsers={usersQuery.data?.items ?? []}
                                        organizationUnits={organizationUnitsQuery.data?.items ?? []}
                                        organizationUnitTypes={organizationUnitTypesQuery.data?.items ?? []}
                                    />

                                    {formError ? <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{formError}</div> : null}

                                    <ActionBar>
                                        <Button type="submit">{createMutation.isPending || updateMutation.isPending ? 'Saving...' : selectedUnit ? 'Save Changes' : 'Create Unit'}</Button>
                                    </ActionBar>
                                </div>
                            </form>
                        ) : null}
                    </div>
                </ContentCard>
            </div>

            <ConfirmModal
                confirmLabel="Delete unit"
                description={deleteTarget ? `Delete ${deleteTarget.name}? This action cannot be undone from the current UI.` : ''}
                isLoading={deleteMutation.isPending}
                onCancel={() => setDeleteTarget(null)}
                onConfirm={() => void handleDeleteConfirm()}
                open={Boolean(deleteTarget)}
                title="Delete organization unit"
            />
        </div>
    );
}
