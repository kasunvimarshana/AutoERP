import { useState } from 'react';
import { PLATFORM_HOME_PATH } from '@/app/routePaths';
import { LinkButton } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Modal } from '@/shared/components/Modal';
import { Pagination } from '@/shared/components/Pagination';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { listTenantPlanAssignments } from '../tenantApi';
import { formatTenantDateTime } from '../tenantPresentation';
import type { TenantPlan } from '../tenantTypes';

const PAGE_SIZE = 20;

export function TenantPlanAssignmentsModal({ plan, onClose }: {
    plan: TenantPlan | null;
    onClose: () => void;
}) {
    const [page, setPage] = useState(1);

    const assignments = useApi(
        (signal) => listTenantPlanAssignments(plan?.id ?? 0, { page, per_page: PAGE_SIZE }, signal),
        [plan?.id, page],
        plan !== null,
        false,
    );

    return (
        <Modal open={plan !== null} title={plan ? `${plan.name} tenant assignments` : 'Tenant assignments'} onClose={onClose}>
            <div className="space-y-4">
                <p className="text-sm text-slate-600">
                    This list shows tenants whose current subscription pointer uses this plan. Effective and historical counts are shown separately in the plan catalogue.
                </p>
                <ErrorAlert error={assignments.error} title="Unable to load assigned tenants" />
                {assignments.loading ? <LoadingState label="Loading assigned tenants..." /> : null}
                {!assignments.loading && !assignments.error && (assignments.data?.data.length ?? 0) === 0 ? (
                    <p className="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">No tenants are currently assigned to this plan.</p>
                ) : null}
                {(assignments.data?.data.length ?? 0) > 0 ? (
                    <div className="space-y-2">
                        {assignments.data?.data.map((tenant) => (
                            <div key={tenant.id} className="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 p-3">
                                <div>
                                    <p className="font-medium text-slate-900">{tenant.name}</p>
                                    <p className="mt-1 text-xs text-slate-500">
                                        {tenant.code} · {tenant.current_subscription?.effective_status ?? 'missing'}
                                        {tenant.current_subscription?.ends_at
                                            ? ` · ends ${formatTenantDateTime(tenant.current_subscription.ends_at)}`
                                            : ''}
                                    </p>
                                </div>
                                <div className="flex items-center gap-2">
                                    <StatusBadge status={tenant.status} />
                                    <LinkButton variant="secondary" to={`${PLATFORM_HOME_PATH}?tenant=${tenant.id}&plan_id=${plan?.id ?? ''}&step=subscription`}>
                                        Manage tenant
                                    </LinkButton>
                                </div>
                            </div>
                        ))}
                    </div>
                ) : null}
                <Pagination meta={assignments.data?.meta} onPageChange={setPage} />
            </div>
        </Modal>
    );
}
