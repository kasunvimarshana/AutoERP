import { Button } from '@/shared/components/Button';
import type { TenantOnboardingReadiness } from '../tenantTypes';
import {
    firstReadinessStep,
    focusTenantStep,
    humanize,
    readinessLabel,
    readinessStepId,
} from '../tenantPresentation';

interface Props {
    readiness: TenantOnboardingReadiness;
    compact?: boolean;
}

export function TenantReadinessSummary({ readiness, compact = false }: Props) {
    const failedChecks = Object.entries(readiness.checks).filter(([, passed]) => !passed);
    const routing = readiness.routing ?? {
        ready: false,
        mode: 'unavailable' as const,
        message: 'Routing capability data is unavailable. Deploy matching backend and frontend revisions.',
    };
    const databaseStrategy = readiness.infrastructure?.database?.strategy;

    return (
        <div className={`rounded-xl border p-4 text-sm ${readiness.ready ? 'border-emerald-200 bg-emerald-50 text-emerald-950' : 'border-amber-200 bg-amber-50 text-amber-950'}`}>
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p className="font-semibold">{readiness.ready ? 'Ready for activation' : 'Activation requirements remain'}</p>
                    <p className="mt-1">
                        {readiness.ready
                            ? 'All foundation, domain, subscription, and accounting checks passed.'
                            : `${failedChecks.length} required check${failedChecks.length === 1 ? '' : 's'} must be completed before activation.`}
                    </p>
                </div>
                {!readiness.ready && firstReadinessStep(readiness) ? (
                    <Button variant="secondary" onClick={() => focusTenantStep(firstReadinessStep(readiness))}>
                        Go to first requirement
                    </Button>
                ) : null}
            </div>

            <div className="mt-3 rounded-lg bg-white/70 px-3 py-2">
                <span className="font-medium">Routing mode:</span>{' '}
                {routing.mode === 'local_fallback' ? 'Explicit local/testing fallback' : routing.mode === 'verified_domain' ? 'Verified public domain' : 'Unavailable'}
                <span className="block text-xs opacity-80">{routing.message}</span>
            </div>
            {!compact ? (
                <div className="mt-2 grid gap-2 text-xs sm:grid-cols-3">
                    <div className="rounded-lg bg-white/70 px-3 py-2"><strong>Database:</strong> {humanize(databaseStrategy)}</div>
                    <div className="rounded-lg bg-white/70 px-3 py-2"><strong>Storage:</strong> shared private disk with tenant object keys</div>
                    <div className="rounded-lg bg-white/70 px-3 py-2"><strong>Mail:</strong> platform mailer</div>
                </div>
            ) : null}

            {!compact ? (
                <div className="mt-4 grid gap-2 sm:grid-cols-2">
                    {Object.entries(readiness.checks).map(([key, passed]) => (
                        <div key={key} className="flex items-center justify-between gap-3 rounded-lg bg-white/70 px-3 py-2">
                            <span>{readinessLabel(key)}</span>
                            <span className={passed ? 'font-semibold text-emerald-700' : 'font-semibold text-amber-700'}>
                                {passed ? 'Ready' : 'Required'}
                            </span>
                        </div>
                    ))}
                </div>
            ) : null}

            {readiness.blockers.length > 0 ? (
                <ul className="mt-4 space-y-2">
                    {readiness.blockers.map((blocker) => {
                        const stepId = readinessStepId(blocker.code);
                        return (
                            <li key={`${blocker.code}-${blocker.message}`} className="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-white/70 px-3 py-2">
                                <div className="min-w-0 flex-1">
                                    <p className="font-medium">{blocker.message}</p>
                                    <p className="mt-1 text-xs opacity-80"><strong>Owner:</strong> {blocker.owner} · <strong>Next action:</strong> {blocker.action}</p>
                                </div>
                                {stepId ? (
                                    <Button variant="ghost" onClick={() => focusTenantStep(stepId)}>Resolve</Button>
                                ) : null}
                            </li>
                        );
                    })}
                </ul>
            ) : null}
        </div>
    );
}
