import { useState } from 'react';
import { LinkButton } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import { platformAuditHref } from '@/modules/platform-administration/platformAdministrationPresentation';
import { listTenantSubscriptionHistory } from '../tenantApi';
import { formatTenantDateTime } from '../tenantPresentation';
import type { TenantSubscriptionRevision } from '../tenantTypes';

const HISTORY_PAGE_SIZE = 20;

export function TenantSubscriptionHistory({
    tenantId,
    subscriptionVersion,
    canAudit,
}: {
    tenantId: number;
    subscriptionVersion: number | null;
    canAudit: boolean;
}) {
    const [page, setPage] = useState(1);
    const history = useApi(
        (signal) => listTenantSubscriptionHistory(
            tenantId,
            { page, per_page: HISTORY_PAGE_SIZE },
            signal,
        ),
        [tenantId, page, subscriptionVersion],
        true,
        false,
    );

    return (
        <div className="space-y-3">
            <div>
                <h4 className="font-semibold text-slate-900">Subscription history</h4>
                <p className="mt-1 text-sm text-slate-500">
                    Immutable commercial revisions are retained in full. Use pagination to review older revisions.
                </p>
            </div>

            <ErrorAlert error={history.error} title="Unable to load subscription history" />
            {history.loading ? <LoadingState label="Loading subscription history..." /> : null}
            {!history.loading && !history.error && (history.data?.data.length ?? 0) === 0 ? (
                <p className="text-sm text-slate-500">No subscription revisions have been recorded.</p>
            ) : null}
            {!history.error && (history.data?.data.length ?? 0) > 0 ? (
                <HistoryTable tenantId={tenantId} subscriptions={history.data?.data ?? []} canAudit={canAudit} />
            ) : null}
            <Pagination meta={history.data?.meta} onPageChange={setPage} />
        </div>
    );
}

function HistoryTable({
    tenantId,
    subscriptions,
    canAudit,
}: {
    tenantId: number;
    subscriptions: TenantSubscriptionRevision[];
    canAudit: boolean;
}) {
    return (
        <div className="overflow-x-auto rounded-lg border border-slate-200">
            <table className="min-w-full text-left text-sm">
                <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th className="px-3 py-2">Revision</th>
                        <th className="px-3 py-2">Operation</th>
                        <th className="px-3 py-2">Plan snapshot</th>
                        <th className="px-3 py-2">Period</th>
                        <th className="px-3 py-2">Reason</th>
                        {canAudit ? <th className="px-3 py-2 text-right">Audit</th> : null}
                    </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                    {subscriptions.map((subscription) => (
                        <tr key={subscription.id}>
                            <td className="px-3 py-2">#{subscription.revision_number}</td>
                            <td className="px-3 py-2"><StatusBadge status={subscription.operation} /></td>
                            <td className="px-3 py-2">
                                {subscription.plan_name}
                                <br />
                                <span className="text-xs text-slate-500">
                                    {subscription.price} {subscription.currency_code ?? ''}
                                </span>
                            </td>
                            <td className="px-3 py-2 text-xs">
                                {formatTenantDateTime(subscription.starts_at)}
                                <br />
                                {subscription.contract_status === 'trial' && subscription.trial_ends_at
                                    ? `trial to ${formatTenantDateTime(subscription.trial_ends_at)}`
                                    : subscription.ends_at
                                        ? `to ${formatTenantDateTime(subscription.ends_at)}`
                                        : 'Open ended'}
                            </td>
                            <td className="max-w-xs px-3 py-2 text-xs text-slate-600">
                                {subscription.change_reason ?? '—'}
                            </td>
                            {canAudit ? (
                                <td className="px-3 py-2 text-right">
                                    <LinkButton
                                        className="min-h-8 px-3 py-1 text-xs"
                                        variant="secondary"
                                        to={platformAuditHref({
                                            tenant_id: tenantId,
                                            subject_type: 'tenant_subscription',
                                            subject_id: subscription.id,
                                        })}
                                    >
                                        View
                                    </LinkButton>
                                </td>
                            ) : null}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
