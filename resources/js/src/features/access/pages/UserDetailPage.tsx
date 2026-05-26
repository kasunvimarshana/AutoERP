import { Link, useParams, useSearchParams } from 'react-router-dom';
import { ContentCard } from '../../../components/ui/ContentCard';
import { PageHeader } from '../../../components/layout/PageHeader';
import { Button } from '../../../components/ui/Button';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { SectionCard } from '../../../components/forms/SectionCard';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { StatusBadge } from '../../../components/tables';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { parsePositiveInteger } from '../../shared/utils';
import { useUser } from '../hooks';

const detailTabs = [
    { id: 'overview', label: 'Overview' },
    { id: 'roles', label: 'Roles' },
    { id: 'permissions', label: 'Permissions' },
] as const;

type DetailTabId = (typeof detailTabs)[number]['id'];

export function UserDetailPage() {
    const { userId: userIdParam } = useParams();
    const userId = parsePositiveInteger(userIdParam ?? null, 0);
    const [searchParams, setSearchParams] = useSearchParams();
    const activeTab = (searchParams.get('tab') as DetailTabId | null) ?? 'overview';
    const userQuery = useUser(userId, 'permissions', userId > 0);

    if (userId <= 0) {
        return (
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
                <ErrorState description="The user route is missing a valid user ID." title="Invalid user route" />
            </div>
        );
    }

    if (userQuery.isPending) {
        return (
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
                <LoadingState lines={8} />
            </div>
        );
    }

    if (userQuery.isError) {
        return (
            <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
                {isForbiddenError(userQuery.error) ? (
                    <ProtectedErrorState description={userQuery.error.message} />
                ) : (
                    <ErrorState description={userQuery.error.message} title="Unable to load user detail" />
                )}
            </div>
        );
    }

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                actions={
                    <Link to={`/users-access/users/${userQuery.data.id}/edit`}>
                        <Button variant="secondary">Edit User</Button>
                    </Link>
                }
                breadcrumbs={[{ label: 'Users & Access' }, { label: 'Users', href: '/users-access/users' }, { label: userQuery.data.full_name ?? userQuery.data.email ?? 'User' }]}
                description="User detail pages group profile data, role badges, and permission summaries so access reviews can happen without leaving the shared admin shell."
                title={userQuery.data.full_name ?? userQuery.data.email ?? 'User'}
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
                    <SectionCard description="Core profile and account state from the backend user resource." title="Profile summary">
                        <dl className="grid gap-4 text-sm text-stone-700 md:grid-cols-2">
                            <div>
                                <dt className="text-xs uppercase tracking-[0.16em] text-stone-400">Email</dt>
                                <dd className="mt-1 font-medium text-stone-950">{userQuery.data.email ?? '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-[0.16em] text-stone-400">Phone</dt>
                                <dd className="mt-1 font-medium text-stone-950">{userQuery.data.phone ?? '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-[0.16em] text-stone-400">Organization Unit ID</dt>
                                <dd className="mt-1 font-medium text-stone-950">{userQuery.data.org_unit_id ?? '-'}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-[0.16em] text-stone-400">Status</dt>
                                <dd className="mt-1">
                                    <StatusBadge tone={userQuery.data.active ? 'success' : 'default'}>{userQuery.data.active ? 'Active' : 'Inactive'}</StatusBadge>
                                </dd>
                            </div>
                        </dl>
                    </SectionCard>
                ) : activeTab === 'roles' ? (
                    <SectionCard description="Roles are rendered as badges with permission counts so access bundles are easy to review." title="Role summary">
                        {userQuery.data.roles.length > 0 ? (
                            <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                {userQuery.data.roles.map((role) => (
                                    <div key={role.id} className="rounded-2xl border border-stone-200 bg-white px-4 py-3">
                                        <p className="text-sm font-medium text-stone-950">{role.name}</p>
                                        <p className="mt-1 text-xs text-stone-500">{role.permissions.length} permissions</p>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <EmptyState className="border-0 bg-transparent p-0 shadow-none" description="This user has no roles assigned yet." title="No roles assigned" />
                        )}
                    </SectionCard>
                ) : (
                    <SectionCard description="Permission summaries flatten the user’s role permissions into one review surface for quick access audits." title="Permission summary">
                        {userQuery.data.permissions && userQuery.data.permissions.length > 0 ? (
                            <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                {userQuery.data.permissions.map((permission) => (
                                    <div key={permission.id} className="rounded-2xl border border-stone-200 bg-white px-4 py-3">
                                        <p className="text-sm font-medium text-stone-950">{permission.name}</p>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <EmptyState className="border-0 bg-transparent p-0 shadow-none" description="No permissions are currently exposed for this user." title="No permissions found" />
                        )}
                    </SectionCard>
                )}
            </ContentCard>
        </div>
    );
}
