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
import { FormField } from '../../../components/forms/FormField';
import { SectionCard } from '../../../components/forms/SectionCard';
import { Select } from '../../../components/forms/Select';
import { Input } from '../../../components/forms/Input';
import { Checkbox } from '../../../components/forms/Checkbox';
import { ValidationError } from '../../../api/client';
import { useToast } from '../../../app/providers/ToastProvider';
import { applyValidationErrors } from '../../../lib/applyValidationErrors';
import { useTenant } from '../../auth/context/TenantContext';
import { useUsers } from '../../access/hooks';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { useCreateOrganizationUnitUser, useDeleteOrganizationUnitUser, useOrganizationUnits, useOrganizationUnitUsers, useUpdateOrganizationUnitUser } from '../hooks';
import { organizationUnitAssignmentFormSchema, type OrganizationUnitAssignmentFormInput, type OrganizationUnitAssignmentFormValues } from '../schemas';
import type { OrganizationUnitRecord, OrganizationUnitUserAssignment } from '../types';

export function OrganizationUnitAssignmentsPage() {
    const { showToast } = useToast();
    const { tenantId } = useTenant();
    const [selectedUnit, setSelectedUnit] = useState<OrganizationUnitRecord | null>(null);
    const [selectedAssignment, setSelectedAssignment] = useState<OrganizationUnitUserAssignment | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<OrganizationUnitUserAssignment | null>(null);
    const [formError, setFormError] = useState<string | null>(null);
    const form = useForm<OrganizationUnitAssignmentFormInput, unknown, OrganizationUnitAssignmentFormValues>({
        resolver: zodResolver(organizationUnitAssignmentFormSchema),
        defaultValues: {
            user_id: 0,
            role: '',
            is_primary: false,
        },
    });

    const organizationUnitsQuery = useOrganizationUnits({ tenant_id: tenantId, per_page: 100, sort: 'path' });
    const usersQuery = useUsers({ tenant_id: tenantId, per_page: 100, page: 1 });
    const assignmentsQuery = useOrganizationUnitUsers(selectedUnit?.id ?? 0, tenantId, Boolean(selectedUnit?.id));
    const createMutation = useCreateOrganizationUnitUser(selectedUnit?.id ?? 0, tenantId);
    const updateMutation = useUpdateOrganizationUnitUser(selectedUnit?.id ?? 0, selectedAssignment?.id ?? 0, tenantId);
    const deleteMutation = useDeleteOrganizationUnitUser(selectedUnit?.id ?? 0, tenantId);

    useEffect(() => {
        if (!selectedAssignment) {
            form.reset({
                user_id: 0,
                role: '',
                is_primary: false,
            });
            return;
        }

        form.reset({
            user_id: selectedAssignment.user_id,
            role: selectedAssignment.role ?? '',
            is_primary: selectedAssignment.is_primary,
        });
    }, [form, selectedAssignment]);

    useEffect(() => {
        if (!selectedUnit && organizationUnitsQuery.data?.items.length) {
            setSelectedUnit(organizationUnitsQuery.data.items[0]);
        }
    }, [organizationUnitsQuery.data?.items, selectedUnit]);

    const userMap = useMemo(() => new Map(usersQuery.data?.items.map((user) => [user.id, user]) ?? []), [usersQuery.data?.items]);

    async function onSubmit(values: OrganizationUnitAssignmentFormValues) {
        if (!selectedUnit) {
            return;
        }

        setFormError(null);

        try {
            if (selectedAssignment) {
                await updateMutation.mutateAsync({
                    role: values.role ?? null,
                    is_primary: values.is_primary,
                });

                showToast({
                    title: 'Assignment updated',
                    description: 'Organization unit user assignment updated successfully.',
                    tone: 'success',
                });
            } else {
                await createMutation.mutateAsync({
                    tenant_id: tenantId,
                    org_unit_id: selectedUnit.id,
                    user_id: values.user_id,
                    role: values.role ?? null,
                    is_primary: values.is_primary,
                });

                showToast({
                    title: 'User assigned',
                    description: 'The user was assigned to the organization unit successfully.',
                    tone: 'success',
                });
            }

            setSelectedAssignment(null);
        } catch (error) {
            if (error instanceof ValidationError) {
                applyValidationErrors(error.errors, form.setError, {
                    onUnhandled: (message) => setFormError(message),
                });
                return;
            }

            setFormError(error instanceof Error ? error.message : 'Unable to save organization unit assignment.');
        }
    }

    async function handleDeleteConfirm() {
        if (!deleteTarget || !selectedUnit) {
            return;
        }

        await deleteMutation.mutateAsync(deleteTarget.id);
        setDeleteTarget(null);
        if (selectedAssignment?.id === deleteTarget.id) {
            setSelectedAssignment(null);
        }

        showToast({
            title: 'Assignment removed',
            description: 'The organization unit assignment was removed successfully.',
            tone: 'success',
        });
    }

    const lookupError = organizationUnitsQuery.error ?? usersQuery.error ?? assignmentsQuery.error;

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[{ label: 'Organization' }, { label: 'Assign Users' }]}
                description="Use the split-panel workspace to select an organization unit, review current assignments, and add or maintain linked users."
                title="Assign Users"
            />

            <div className="grid gap-4 xl:grid-cols-[0.9fr_1.1fr]">
                <ContentCard className="space-y-4">
                    <div>
                        <h2 className="text-lg font-semibold text-stone-950">Organization units</h2>
                        <p className="mt-1 text-sm text-stone-600">Select a unit to review and maintain assigned users.</p>
                    </div>

                    {organizationUnitsQuery.isPending ? (
                        <LoadingState lines={8} />
                    ) : organizationUnitsQuery.isError ? (
                        isForbiddenError(organizationUnitsQuery.error) ? (
                            <ProtectedErrorState description={organizationUnitsQuery.error.message} />
                        ) : (
                            <ErrorState description={organizationUnitsQuery.error.message} title="Unable to load organization units" />
                        )
                    ) : organizationUnitsQuery.data?.items.length ? (
                        <div className="space-y-2">
                            {organizationUnitsQuery.data.items.map((organizationUnit) => (
                                <button
                                    key={organizationUnit.id}
                                    className={selectedUnit?.id === organizationUnit.id ? 'w-full rounded-2xl bg-stone-950 px-4 py-3 text-left text-white' : 'w-full rounded-2xl bg-stone-100 px-4 py-3 text-left text-stone-800 transition hover:bg-stone-200'}
                                    onClick={() => {
                                        setSelectedUnit(organizationUnit);
                                        setSelectedAssignment(null);
                                    }}
                                    type="button"
                                >
                                    <div className="font-medium" style={{ marginLeft: `${organizationUnit.depth * 1.1}rem` }}>
                                        {organizationUnit.name}
                                    </div>
                                    <div className={selectedUnit?.id === organizationUnit.id ? 'mt-1 text-xs text-stone-300' : 'mt-1 text-xs text-stone-500'}>{organizationUnit.code ?? 'No code assigned'}</div>
                                </button>
                            ))}
                        </div>
                    ) : (
                        <EmptyState className="border-0 bg-transparent p-0 shadow-none" description="No organization units are available yet." title="No units found" />
                    )}
                </ContentCard>

                <div className="space-y-4">
                    <ContentCard className="p-0">
                        {selectedUnit ? (
                            assignmentsQuery.isPending ? (
                                <LoadingState className="m-6" lines={6} />
                            ) : lookupError ? (
                                isForbiddenError(lookupError) ? (
                                    <ProtectedErrorState className="m-6" description={lookupError.message} />
                                ) : (
                                    <ErrorState className="m-6" description={lookupError.message} title="Unable to load organization unit assignments" />
                                )
                            ) : assignmentsQuery.data?.items.length ? (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full text-left text-sm">
                                        <thead className="border-b border-stone-200/80 bg-stone-50/70 text-xs uppercase tracking-[0.16em] text-stone-500">
                                            <tr>
                                                <th className="px-6 py-4 font-medium">User</th>
                                                <th className="px-6 py-4 font-medium">Role</th>
                                                <th className="px-6 py-4 font-medium">Primary</th>
                                                <th className="px-6 py-4 font-medium">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-stone-200/70">
                                            {assignmentsQuery.data.items.map((assignment) => {
                                                const user = userMap.get(assignment.user_id);

                                                return (
                                                    <tr key={assignment.id} className={selectedAssignment?.id === assignment.id ? 'bg-stone-50/70' : undefined}>
                                                        <td className="px-6 py-4">
                                                            <div>
                                                                <p className="font-medium text-stone-950">{user?.full_name ?? user?.email ?? `User #${assignment.user_id}`}</p>
                                                                <p className="mt-1 text-xs text-stone-500">{user?.email ?? `ID ${assignment.user_id}`}</p>
                                                            </div>
                                                        </td>
                                                        <td className="px-6 py-4 text-stone-700">{assignment.role ?? '-'}</td>
                                                        <td className="px-6 py-4 text-stone-700">{assignment.is_primary ? 'Yes' : 'No'}</td>
                                                        <td className="px-6 py-4">
                                                            <div className="flex flex-wrap gap-2">
                                                                <Button className="h-9 px-3 text-xs" onClick={() => setSelectedAssignment(assignment)} type="button" variant="secondary">
                                                                    Edit
                                                                </Button>
                                                                <Button className="h-9 px-3 text-xs" onClick={() => setDeleteTarget(assignment)} type="button" variant="secondary">
                                                                    Remove
                                                                </Button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <EmptyState className="m-6" description="No users are assigned to this organization unit yet." title="No assignments found" />
                            )
                        ) : (
                            <EmptyState className="m-6" description="Choose an organization unit from the left panel to manage assignments." title="Select a unit" />
                        )}
                    </ContentCard>

                    <ContentCard>
                        <form onSubmit={form.handleSubmit(onSubmit)}>
                            <div className="space-y-6">
                                <div className="flex items-center justify-between gap-3">
                                    <div>
                                        <h2 className="text-lg font-semibold text-stone-950">{selectedAssignment ? 'Edit assignment' : 'Assign user'}</h2>
                                        <p className="mt-1 text-sm text-stone-600">{selectedUnit ? `Manage users for ${selectedUnit.name}.` : 'Select an organization unit first.'}</p>
                                    </div>
                                    {selectedAssignment ? (
                                        <Button onClick={() => setSelectedAssignment(null)} type="button" variant="secondary">
                                            New Assignment
                                        </Button>
                                    ) : null}
                                </div>

                                <SectionCard description="Assignments stay on the same screen as the selected hierarchy node, making it easy to keep ownership and membership aligned." title="Assignment editor">
                                    <div className="space-y-4">
                                        <FormField error={form.formState.errors.user_id?.message} label="User" required>
                                            <Select error={form.formState.errors.user_id?.message} disabled={!selectedUnit || Boolean(selectedAssignment)} {...form.register('user_id')}>
                                                <option value="0">Select user</option>
                                                {(usersQuery.data?.items ?? []).map((user) => (
                                                    <option key={user.id} value={user.id}>
                                                        {user.full_name ?? user.email ?? `User #${user.id}`}
                                                    </option>
                                                ))}
                                            </Select>
                                        </FormField>
                                        <FormField error={form.formState.errors.role?.message} label="Assignment Role">
                                            <Input error={form.formState.errors.role?.message} placeholder="Branch manager" {...form.register('role')} />
                                        </FormField>
                                        <FormField error={form.formState.errors.is_primary?.message} label="Primary Assignment">
                                            <Checkbox
                                                className="border-stone-200/80 bg-stone-50/70"
                                                description="Mark this user as the primary owner or main contact for the selected organization unit."
                                                label="Primary assignment"
                                                {...form.register('is_primary')}
                                            />
                                        </FormField>
                                    </div>
                                </SectionCard>

                                {formError ? <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{formError}</div> : null}

                                <ActionBar>
                                    <Button disabled={!selectedUnit} type="submit">
                                        {createMutation.isPending || updateMutation.isPending ? 'Saving...' : selectedAssignment ? 'Save Assignment' : 'Assign User'}
                                    </Button>
                                </ActionBar>
                            </div>
                        </form>
                    </ContentCard>
                </div>
            </div>

            <ConfirmModal
                confirmLabel="Remove assignment"
                description={deleteTarget ? 'Remove this user from the selected organization unit? This action cannot be undone from the current UI.' : ''}
                isLoading={deleteMutation.isPending}
                onCancel={() => setDeleteTarget(null)}
                onConfirm={() => void handleDeleteConfirm()}
                open={Boolean(deleteTarget)}
                title="Remove organization unit assignment"
            />
        </div>
    );
}
