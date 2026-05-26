import { Link, useParams, useSearchParams } from 'react-router-dom';
import { ContentCard } from '../../../components/ui/ContentCard';
import { PageHeader } from '../../../components/layout/PageHeader';
import { Button } from '../../../components/ui/Button';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { SectionCard } from '../../../components/forms/SectionCard';
import { StatusBadge } from '../../../components/tables';
import { useUser } from '../../access/hooks';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { formatDate, parsePositiveInteger } from '../../shared/utils';
import { useEmployee } from '../hooks';

const detailTabs = [
    { id: 'overview', label: 'Overview' },
    { id: 'user', label: 'Linked User' },
] as const;

type DetailTabId = (typeof detailTabs)[number]['id'];

export function EmployeeDetailPage() {
    const { employeeId: employeeIdParam } = useParams();
    const employeeId = parsePositiveInteger(employeeIdParam ?? null, 0);
    const [searchParams, setSearchParams] = useSearchParams();
    const activeTab = (searchParams.get('tab') as DetailTabId | null) ?? 'overview';
    const employeeQuery = useEmployee(employeeId, employeeId > 0);
    const userQuery = useUser(employeeQuery.data?.user_id ?? 0, 'permissions', Boolean(employeeQuery.data?.user_id));

    if (employeeId <= 0) {
        return (
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
                <ErrorState description="The employee route is missing a valid employee ID." title="Invalid employee route" />
            </div>
        );
    }

    if (employeeQuery.isPending) {
        return (
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
                <LoadingState lines={8} />
            </div>
        );
    }

    if (employeeQuery.isError) {
        return (
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
                {isForbiddenError(employeeQuery.error) ? (
                    <ProtectedErrorState description={employeeQuery.error.message} />
                ) : (
                    <ErrorState description={employeeQuery.error.message} title="Unable to load employee detail" />
                )}
            </div>
        );
    }

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                actions={
                    <Link to={`/employees/${employeeQuery.data.id}/edit`}>
                        <Button variant="secondary">Edit Employee</Button>
                    </Link>
                }
                breadcrumbs={[{ label: 'Employees' }, { label: 'Employee List', href: '/employees' }, { label: employeeQuery.data.employee_code ?? `Employee #${employeeQuery.data.id}` }]}
                description="Employee detail pages keep workforce-specific fields and the linked user identity in one stable layout so later HR and access workflows can layer in cleanly."
                title={employeeQuery.data.employee_code ?? `Employee #${employeeQuery.data.id}`}
            />

            <ContentCard className="space-y-6">
                <div className="flex flex-wrap gap-2 border-b border-stone-200/80 pb-4">
                    {detailTabs.map((tab) => (
                        <button
                            key={tab.id}
                            className={activeTab === tab.id ? 'rounded-full bg-stone-950 px-4 py-2 text-sm font-medium text-white' : 'rounded-full bg-stone-100 px-4 py-2 text-sm font-medium text-stone-600 transition hover:bg-stone-200'}
                            onClick={() => setSearchParams({ tab: tab.id })}
                            type="button"
                        >
                            {tab.label}
                        </button>
                    ))}
                </div>

                {activeTab === 'overview' ? (
                    <SectionCard description="Core employee fields from the backend employee resource." title="Employment summary">
                        <dl className="grid gap-4 text-sm text-stone-700 md:grid-cols-2">
                            <div>
                                <dt className="text-xs uppercase tracking-[0.16em] text-stone-400">Employee Code</dt>
                                <dd className="mt-1 font-medium text-stone-950">{employeeQuery.data.employee_code ?? '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-[0.16em] text-stone-400">Job Title</dt>
                                <dd className="mt-1 font-medium text-stone-950">{employeeQuery.data.job_title ?? '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-[0.16em] text-stone-400">Organization Unit ID</dt>
                                <dd className="mt-1 font-medium text-stone-950">{employeeQuery.data.org_unit_id ?? '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-[0.16em] text-stone-400">Hire Date</dt>
                                <dd className="mt-1 font-medium text-stone-950">{formatDate(employeeQuery.data.hire_date)}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-[0.16em] text-stone-400">Termination Date</dt>
                                <dd className="mt-1 font-medium text-stone-950">{formatDate(employeeQuery.data.termination_date)}</dd>
                            </div>
                        </dl>
                    </SectionCard>
                ) : userQuery.isPending ? (
                    <LoadingState lines={4} />
                ) : userQuery.isError ? (
                    isForbiddenError(userQuery.error) ? (
                        <ProtectedErrorState description={userQuery.error.message} />
                    ) : (
                        <ErrorState description={userQuery.error.message} title="Unable to load linked user" />
                    )
                ) : userQuery.data ? (
                    <SectionCard description="The linked user record connects employee identity to system access, role bundles, and downstream auditability." title="Linked user">
                        <dl className="grid gap-4 text-sm text-stone-700 md:grid-cols-2">
                            <div>
                                <dt className="text-xs uppercase tracking-[0.16em] text-stone-400">Full Name</dt>
                                <dd className="mt-1 font-medium text-stone-950">{userQuery.data.full_name ?? '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-[0.16em] text-stone-400">Email</dt>
                                <dd className="mt-1 font-medium text-stone-950">{userQuery.data.email ?? '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-[0.16em] text-stone-400">Phone</dt>
                                <dd className="mt-1 font-medium text-stone-950">{userQuery.data.phone ?? '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-[0.16em] text-stone-400">Status</dt>
                                <dd className="mt-1">
                                    <StatusBadge tone={userQuery.data.active ? 'success' : 'default'}>{userQuery.data.active ? 'Active' : 'Inactive'}</StatusBadge>
                                </dd>
                            </div>
                        </dl>
                    </SectionCard>
                ) : (
                    <EmptyState className="border-0 bg-transparent p-0 shadow-none" description="No linked user record is available for this employee." title="No linked user" />
                )}
            </ContentCard>
        </div>
    );
}
