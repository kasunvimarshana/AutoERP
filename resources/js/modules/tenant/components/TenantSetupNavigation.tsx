import { Button } from '@/shared/components/Button';
import type { TenantRecord } from '../tenantTypes';
import { humanize } from '../tenantPresentation';

export type TenantSetupStep = 'identity' | 'foundation' | 'domain' | 'subscription' | 'readiness';

const REQUIRED_FOUNDATION_STEPS = [
    'root_organization',
    'permission_catalogue',
    'super_admin_role',
    'authentication_provider',
    'initial_admin_invitation',
] as const;

const SETUP_STEPS: Array<{ key: TenantSetupStep; label: string }> = [
    { key: 'identity', label: '1. Identity' },
    { key: 'foundation', label: '2. Foundation' },
    { key: 'domain', label: '3. Domain' },
    { key: 'subscription', label: '4. Subscription' },
    { key: 'readiness', label: '5. Readiness' },
];

export function TenantSetupNavigation({ tenant, activeStep, onSelect }: {
    tenant: TenantRecord;
    activeStep: TenantSetupStep;
    onSelect: (step: TenantSetupStep) => void;
}) {
    return (
        <div className="mt-4 grid gap-2 border-t border-slate-100 pt-4 sm:grid-cols-2 lg:grid-cols-5">
            {SETUP_STEPS.map((step) => {
                const status = tenantSetupStepStatus(tenant, step.key);
                return (
                    <Button
                        key={step.key}
                        variant={activeStep === step.key ? 'primary' : 'ghost'}
                        onClick={() => onSelect(step.key)}
                    >
                        <span className="text-left">
                            {step.label}
                            <span className="block text-xs font-normal opacity-80">{status}</span>
                        </span>
                    </Button>
                );
            })}
        </div>
    );
}

export function BlockedTenantSetupStep({ message }: { message: string }) {
    return (
        <div className="rounded-lg border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
            <p className="font-semibold">This step is blocked</p>
            <p className="mt-1">{message}</p>
        </div>
    );
}

export function resolveTenantSetupStep(value: string | null): TenantSetupStep {
    return SETUP_STEPS.some((step) => step.key === value) ? value as TenantSetupStep : 'identity';
}

export function tenantFoundationProvisioned(tenant: TenantRecord): boolean {
    const completedSteps = new Set(tenant.onboarding?.completed_steps ?? []);

    return REQUIRED_FOUNDATION_STEPS.every((step) => completedSteps.has(step));
}

function tenantSetupStepStatus(tenant: TenantRecord, step: TenantSetupStep): string {
    if (step === 'identity') return tenant.base_currency ? 'Completed' : 'Action required';
    if (step === 'foundation') {
        if (!tenant.base_currency) return 'Blocked';
        return tenant.onboarding?.status === 'completed' ? 'Completed' : humanize(tenant.onboarding?.status ?? 'ready');
    }
    if (step === 'domain') {
        if (!tenantFoundationProvisioned(tenant)) return 'Blocked';
        return tenant.primary_domain?.operational_status === 'ready' ? 'Completed' : 'Action required';
    }
    if (step === 'subscription') {
        return tenant.current_subscription?.effective_status === 'active' || tenant.current_subscription?.effective_status === 'trial'
            ? 'Completed'
            : 'Action required';
    }

    return tenant.status === 'active' ? 'Completed' : 'Review blockers';
}
