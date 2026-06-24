import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Modal } from '@/shared/components/Modal';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { listTenantPlanRevisions } from '../tenantApi';
import { formatLimitLabel, formatPlanMoney, formatTenantDateTime, humanize } from '../tenantPresentation';
import type { TenantPlan } from '../tenantTypes';

interface Props {
    plan: TenantPlan | null;
    onClose: () => void;
}

export function TenantPlanRevisionHistory({ plan, onClose }: Props) {
    const revisions = useApi(
        (signal) => listTenantPlanRevisions(plan?.id ?? 0, signal),
        [plan?.id],
        plan !== null,
        false,
    );

    return (
        <Modal open={plan !== null} title={plan ? `${plan.name} revision history` : 'Plan revision history'} onClose={onClose}>
            <div className="space-y-4">
                <ErrorAlert error={revisions.error} />
                {revisions.loading && !revisions.data ? <LoadingState label="Loading revision history..." /> : null}
                {(revisions.data ?? []).map((revision) => (
                    <article key={revision.id} className="rounded-lg border border-slate-200 p-4">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p className="font-semibold text-slate-900">Revision {revision.revision_number}</p>
                                <p className="mt-1 text-sm text-slate-500">Effective {formatTenantDateTime(revision.effective_at)} · created {formatTenantDateTime(revision.created_at)}</p>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                {revision.id === plan?.latest_revision?.id ? <StatusBadge status="latest" /> : null}
                                <span className="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">{revision.current_subscription_count ?? 0} current</span>
                                <span className="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">{revision.historical_subscription_count ?? 0} historical</span>
                            </div>
                        </div>
                        <p className="mt-3 text-sm font-medium text-slate-900">{formatPlanMoney(revision)}</p>
                        <div className="mt-3 grid gap-3 text-sm md:grid-cols-2">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Enabled modules</p>
                                <p className="mt-1 text-slate-700">{revision.features.enabled_modules.length > 0 ? revision.features.enabled_modules.map(humanize).join(', ') : 'No commercial modules'}</p>
                            </div>
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Usage limits</p>
                                <p className="mt-1 text-slate-700">{Object.keys(revision.limits).length > 0
                                    ? Object.entries(revision.limits).map(([key, value]) => `${formatLimitLabel(key)}: ${value}`).join(' · ')
                                    : 'No plan limits'}</p>
                            </div>
                        </div>
                    </article>
                ))}
                {(revisions.data ?? []).length === 0 && !revisions.loading ? <p className="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">This plan has no revisions.</p> : null}
                <div className="flex justify-end"><Button variant="secondary" onClick={onClose}>Close</Button></div>
            </div>
        </Modal>
    );
}
