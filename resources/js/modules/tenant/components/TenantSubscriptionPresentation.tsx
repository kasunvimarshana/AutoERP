import { parseEnabledTenantModules } from '@/app/access/tenantModules';
import { StatusBadge } from '@/shared/components/StatusBadge';
import {
    formatLimitLabel,
    formatPlanMoney,
    formatTenantDateTime,
    humanize,
} from '../tenantPresentation';
import type {
    TenantCurrentSubscription,
    TenantPlanRevision,
    TenantSubscriptionReadiness,
} from '../tenantTypes';

export function CurrentSubscriptionSummary({ subscription }: { subscription: TenantCurrentSubscription }) {
    return (
        <div className="rounded-lg border border-slate-200 p-4 text-sm">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p className="font-semibold text-slate-900">
                        {subscription.plan_name} · subscription revision {subscription.revision_number}
                    </p>
                    <p className="mt-1 text-slate-500">
                        {subscription.price} {subscription.currency_code ?? ''} / {humanize(subscription.billing_interval)}
                    </p>
                    <p className="mt-1 text-xs text-slate-500">
                        Started {formatTenantDateTime(subscription.starts_at)}
                        {subscription.trial_ends_at ? ` · trial ends ${formatTenantDateTime(subscription.trial_ends_at)}` : ''}
                        {subscription.ends_at ? ` · ends ${formatTenantDateTime(subscription.ends_at)}` : subscription.contract_status === 'active' ? ' · open ended' : ''}
                    </p>
                    {subscription.current_state_reason ? (
                        <p className="mt-2 text-xs text-slate-600">{subscription.current_state_reason}</p>
                    ) : null}
                </div>
                <div className="flex gap-2">
                    <StatusBadge status={subscription.current_state} />
                    <StatusBadge status={subscription.effective_status} />
                </div>
            </div>
        </div>
    );
}

export function SubscriptionComparison({ current, proposed, readiness }: {
    current: TenantCurrentSubscription | null;
    proposed: TenantPlanRevision;
    readiness: TenantSubscriptionReadiness | null;
}) {
    const currentModules = parseEnabledTenantModules(current?.plan_features.enabled_modules ?? null) ?? new Set();
    const proposedModules = parseEnabledTenantModules(proposed.features.enabled_modules) ?? new Set();
    const added = [...proposedModules].filter((module) => !currentModules.has(module));
    const removed = [...currentModules].filter((module) => !proposedModules.has(module));
    const currentLimits = current?.plan_limits ?? {};
    const limitKeys = [...new Set([
        ...Object.keys(currentLimits),
        ...Object.keys(proposed.limits),
        ...Object.keys(readiness?.usage ?? {}),
    ])].sort();

    return (
        <div className="space-y-4 rounded-lg bg-slate-50 p-4 text-sm">
            <div className="grid gap-3 md:grid-cols-2">
                <ComparisonValue
                    label="Current contract"
                    value={current ? `${current.plan_name} · subscription revision ${current.revision_number}` : 'No current subscription'}
                />
                <ComparisonValue
                    label="Proposed contract"
                    value={`Plan revision ${proposed.revision_number} · ${formatPlanMoney(proposed)}`}
                />
            </div>
            <div className="grid gap-3 md:grid-cols-2">
                <ModuleChanges title="Modules added" values={added} empty="No modules added" tone="emerald" />
                <ModuleChanges title="Modules removed" values={removed} empty="No modules removed" tone="amber" />
            </div>
            {limitKeys.length > 0 ? (
                <div className="overflow-x-auto rounded-lg border border-slate-200 bg-white">
                    <table className="min-w-full text-left text-sm">
                        <thead className="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th className="px-3 py-2">Limit</th>
                                <th className="px-3 py-2">Current</th>
                                <th className="px-3 py-2">Proposed</th>
                                <th className="px-3 py-2">Usage</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {limitKeys.map((key) => (
                                <tr key={key}>
                                    <td className="px-3 py-2 font-medium text-slate-900">{formatLimitLabel(key)}</td>
                                    <td className="px-3 py-2">{displayLimit(currentLimits[key as keyof typeof currentLimits])}</td>
                                    <td className="px-3 py-2">{displayLimit(proposed.limits[key as keyof typeof proposed.limits])}</td>
                                    <td className="px-3 py-2">{readiness?.usage[key] ?? 'Check impact'}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            ) : null}
        </div>
    );
}

export function ReadinessResult({ readiness }: { readiness: TenantSubscriptionReadiness }) {
    return (
        <div className={`rounded-lg border p-4 text-sm ${readiness.ready
            ? 'border-emerald-200 bg-emerald-50 text-emerald-900'
            : 'border-amber-200 bg-amber-50 text-amber-950'}`}
        >
            <p className="font-semibold">{readiness.ready ? 'Safe to continue' : 'Action blocked'}</p>
            {readiness.removed_modules.length > 0 ? (
                <p className="mt-2">Modules removed: {readiness.removed_modules.map(humanize).join(', ')}</p>
            ) : null}
            {readiness.blockers.length > 0 ? (
                <ul className="mt-2 space-y-1">
                    {readiness.blockers.map((blocker) => (
                        <li key={`${blocker.code}-${blocker.message}`}>• {blocker.message}</li>
                    ))}
                </ul>
            ) : (
                <p className="mt-2">Current usage fits the selected limits and no required module closeout is pending.</p>
            )}
        </div>
    );
}

export function PermissionNotice({ message }: { message: string }) {
    return <p className="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">{message}</p>;
}

function ComparisonValue({ label, value }: { label: string; value: string }) {
    return <div><p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</p><p className="mt-1 font-medium text-slate-900">{value}</p></div>;
}

function ModuleChanges({ title, values, empty, tone }: {
    title: string;
    values: string[];
    empty: string;
    tone: 'emerald' | 'amber';
}) {
    return (
        <div>
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{title}</p>
            <p className={`mt-1 ${tone === 'emerald' ? 'text-emerald-700' : 'text-amber-700'}`}>
                {values.length > 0 ? values.map(humanize).join(', ') : empty}
            </p>
        </div>
    );
}

function displayLimit(value: number | undefined): string {
    return value === undefined ? 'Unlimited' : String(value);
}
