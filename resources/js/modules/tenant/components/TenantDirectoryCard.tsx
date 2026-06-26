import { StatusBadge } from '@/shared/components/StatusBadge';
import { formatTenantDateTime, humanize } from '../tenantPresentation';
import type { TenantRecord } from '../tenantTypes';

export function TenantDirectoryCard({ tenant, selected, onSelect }: {
    tenant: TenantRecord;
    selected: boolean;
    onSelect: () => void;
}) {
    const onboarding = tenant.onboarding?.status ?? 'pending';
    const subscription = tenant.current_subscription;

    return (
        <button
            type="button"
            onClick={onSelect}
            aria-pressed={selected}
            className="w-full rounded-lg border border-slate-200 p-4 text-left transition hover:border-blue-300 hover:bg-blue-50/30 aria-pressed:border-blue-500 aria-pressed:bg-blue-50"
        >
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p className="font-semibold text-slate-900">{tenant.name}</p>
                    <p className="mt-1 text-sm text-slate-500">{tenant.code} · {tenant.primary_domain?.domain ?? 'Primary domain required'}</p>
                </div>
                <StatusBadge status={tenant.status} />
            </div>
            <div className="mt-3 grid gap-2 text-xs text-slate-600 sm:grid-cols-3">
                <span>Foundation: <strong>{humanize(onboarding)}</strong></span>
                <span>Plan: <strong>{subscription?.plan_name ?? subscription?.revision?.plan?.name ?? 'Not assigned'}</strong></span>
                <span>Period: <strong>{subscription?.ends_at ? `Ends ${formatTenantDateTime(subscription.ends_at)}` : subscription ? humanize(subscription.effective_status) : 'Required'}</strong></span>
            </div>
        </button>
    );
}
