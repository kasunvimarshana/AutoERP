import { useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { ContentCard } from '../../../components/ui/ContentCard';
import { PageHeader } from '../../../components/layout/PageHeader';
import { Button } from '../../../components/ui/Button';
import { ConfirmModal } from '../../../components/feedback/ConfirmModal';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { Input } from '../../../components/forms/Input';
import { DataTable, SearchFilterToolbar, TablePagination, TableToolbar, type DataTableColumn } from '../../../components/tables';
import { useToast } from '../../../app/providers/ToastProvider';
import { useTenant } from '../../auth/context/TenantContext';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { formatDate, parsePositiveInteger } from '../../shared/utils';
import { useDeleteEmployee, useEmployees } from '../hooks';
import type { EmployeeRecord } from '../types';

export function EmployeeListPage() {
    const { showToast } = useToast();
    const { tenantId } = useTenant();
    const [searchParams, setSearchParams] = useSearchParams();
    const [deleteTarget, setDeleteTarget] = useState<EmployeeRecord | null>(null);

    const page = parsePositiveInteger(searchParams.get('page'), 1);
    const search = searchParams.get('search') ?? '';
    const employeesQuery = useEmployees({
        tenant_id: tenantId,
        page,
        per_page: 10,
        employee_code: search || undefined,
        sort: '-updated_at',
    });
    const deleteMutation = useDeleteEmployee();

    function updateParams(updates: Record<string, string | number | undefined>) {
        setSearchParams((current) => {
            const next = new URLSearchParams(current);

            for (const [key, value] of Object.entries(updates)) {
                if (value === undefined || value === '') {
                    next.delete(key);
                } else {
                    next.set(key, String(value));
                }
            }

            if ('search' in updates) {
                next.set('page', '1');
            }

            return next;
        });
    }

    async function handleDeleteConfirm() {
        if (!deleteTarget) {
            return;
        }

        const target = deleteTarget;
        await deleteMutation.mutateAsync(target.id);
        setDeleteTarget(null);
        showToast({
            title: 'Employee deleted',
            description: `${target.employee_code ?? `Employee #${target.id}`} has been removed from the employee directory.`,
            tone: 'success',
        });
    }

    const columns: DataTableColumn<EmployeeRecord>[] = [
        {
            key: 'employee_code',
            header: 'Employee',
            render: (employee) => (
                <div>
                    <Link className="font-medium text-stone-950 transition hover:text-stone-700" to={`/employees/${employee.id}`}>
                        {employee.employee_code ?? `Employee #${employee.id}`}
                    </Link>
                    <p className="mt-1 text-xs text-stone-500">{employee.job_title ?? 'No job title assigned'}</p>
                </div>
            ),
        },
        { key: 'user_id', header: 'User ID', render: (employee) => <span className="text-sm text-stone-700">#{employee.user_id}</span> },
        { key: 'org_unit_id', header: 'Org Unit ID', render: (employee) => <span className="text-sm text-stone-700">{employee.org_unit_id ? `#${employee.org_unit_id}` : '-'}</span> },
        { key: 'hire_date', header: 'Hire Date', render: (employee) => formatDate(employee.hire_date) },
        {
            key: 'actions',
            header: 'Actions',
            className: 'w-[13rem]',
            render: (employee) => (
                <div className="flex flex-wrap gap-2">
                    <Link to={`/employees/${employee.id}`}>
                        <Button className="h-9 px-3 text-xs" type="button" variant="secondary">
                            View
                        </Button>
                    </Link>
                    <Link to={`/employees/${employee.id}/edit`}>
                        <Button className="h-9 px-3 text-xs" type="button" variant="secondary">
                            Edit
                        </Button>
                    </Link>
                    <Button className="h-9 px-3 text-xs" onClick={() => setDeleteTarget(employee)} type="button" variant="secondary">
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
                    <Link to="/employees/new">
                        <Button>Add Employee</Button>
                    </Link>
                }
                breadcrumbs={[{ label: 'Employees' }, { label: 'Employee List' }]}
                description="Employee master data follows the same list, create, edit, and detail pattern already used across the other Phase 3 modules."
                title="Employee List"
            />

            <ContentCard className="p-0">
                <TableToolbar
                    actions={<div className="hidden text-xs uppercase tracking-[0.16em] text-stone-400 lg:block">Tenant {tenantId}</div>}
                    description="Search employee codes, review workforce assignments, and move directly into detail or maintenance flows."
                    title="Employee directory"
                >
                    <SearchFilterToolbar
                        search={
                            <Input
                                className="w-full md:max-w-sm"
                                label={undefined}
                                onChange={(event) => updateParams({ search: event.target.value || undefined })}
                                placeholder="Search employee code"
                                value={search}
                            />
                        }
                        trailing={<div className="text-sm text-stone-500">{employeesQuery.data?.meta?.total ?? 0} records</div>}
                    />
                </TableToolbar>

                {employeesQuery.isPending ? (
                    <LoadingState className="m-6" lines={8} />
                ) : employeesQuery.isError ? (
                    isForbiddenError(employeesQuery.error) ? (
                        <ProtectedErrorState className="m-6" description={employeesQuery.error.message} />
                    ) : (
                        <ErrorState className="m-6" description={employeesQuery.error.message} title="Unable to load employees" />
                    )
                ) : (
                    <DataTable
                        columns={columns}
                        emptyState={
                            <EmptyState
                                action={
                                    <Link to="/employees/new">
                                        <Button>Create your first employee</Button>
                                    </Link>
                                }
                                className="m-6"
                                description="No employees match the current filter yet. Add an employee or widen the search."
                                title="No employees found"
                            />
                        }
                        footer={<TablePagination meta={employeesQuery.data.meta} onPageChange={(nextPage) => updateParams({ page: nextPage })} />}
                        getRowKey={(employee) => employee.id}
                        rows={employeesQuery.data.items}
                    />
                )}
            </ContentCard>

            <ConfirmModal
                confirmLabel="Delete employee"
                description={deleteTarget ? `Delete ${deleteTarget.employee_code ?? `employee #${deleteTarget.id}`}? This action cannot be undone from the current UI.` : ''}
                isLoading={deleteMutation.isPending}
                onCancel={() => setDeleteTarget(null)}
                onConfirm={() => void handleDeleteConfirm()}
                open={Boolean(deleteTarget)}
                title="Delete employee"
            />
        </div>
    );
}
