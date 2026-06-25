import type {
    TenantOnboardingReadiness,
    TenantPlanLimits,
    TenantPlanRevision,
} from './tenantTypes';

const READINESS_LABELS: Record<string, string> = {
    onboarding_state: 'Foundation provisioning',
    organization_structure: 'Root organization structure',
    permission_catalogue: 'Permission catalogue',
    super_admin_role: 'Super Admin access',
    authentication_provider: 'Authentication provider',
    initial_admin_invitation: 'Initial administrator invitation',
    base_currency: 'Base accounting currency',
    verified_primary_domain: 'Verified primary domain',
    subscription: 'Current subscription',
    active_subscription: 'Active subscription period',
};

const READINESS_STEPS: Record<string, string> = {
    onboarding_state: 'tenant-foundation-step',
    organization_structure: 'tenant-foundation-step',
    permission_catalogue: 'tenant-foundation-step',
    super_admin_role: 'tenant-foundation-step',
    authentication_provider: 'tenant-foundation-step',
    initial_admin_invitation: 'tenant-foundation-step',
    base_currency: 'tenant-identity-step',
    verified_primary_domain: 'tenant-domain-step',
    subscription: 'tenant-subscription-step',
    active_subscription: 'tenant-subscription-step',
};

export function readinessLabel(key: string): string {
    return READINESS_LABELS[key] ?? humanize(key);
}

export function readinessStepId(key: string): string | null {
    return READINESS_STEPS[key] ?? null;
}

export function firstReadinessStep(readiness: TenantOnboardingReadiness | null): string | null {
    if (!readiness) return null;
    const failedCheck = Object.entries(readiness.checks).find(([, passed]) => !passed)?.[0];
    return failedCheck ? readinessStepId(failedCheck) : null;
}

export function focusTenantStep(stepId: string | null): void {
    if (!stepId) return;
    document.getElementById(stepId)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

export function formatTenantDateTime(value: string | null | undefined, fallback = 'Not set'): string {
    if (!value) return fallback;
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? fallback : date.toLocaleString();
}

export function formatTenantDate(value: string | null | undefined, fallback = 'Not set'): string {
    if (!value) return fallback;
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? fallback : date.toLocaleDateString();
}

export function formatPlanMoney(revision: Pick<TenantPlanRevision, 'price' | 'currency' | 'billing_interval'>): string {
    const numeric = Number(revision.price);
    const amount = Number.isFinite(numeric)
        ? new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 6 }).format(numeric)
        : revision.price;
    const currency = revision.currency?.code ?? revision.currency?.symbol ?? '';
    return `${currency} ${amount} / ${humanize(revision.billing_interval)}`.trim();
}

export function formatLimitLabel(key: keyof TenantPlanLimits | string): string {
    return {
        max_users: 'Users',
        max_organization_units: 'Organization units',
        max_warehouses: 'Warehouses',
        max_storage_mb: 'Document storage (MB)',
    }[key] ?? humanize(key);
}

export function humanize(value: unknown): string {
    if (typeof value !== 'string' || value.trim() === '') return 'Not available';

    return value
        .replaceAll('_', ' ')
        .replaceAll('-', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());
}

export function normalizeHostname(value: string): string {
    return value.trim().toLowerCase().replace(/\.$/, '');
}

export function hostnameError(value: string): string | null {
    const hostname = normalizeHostname(value);
    if (hostname === '') return 'Enter a tenant hostname.';
    if (hostname.includes('://') || hostname.includes('/') || hostname.includes(':') || hostname.includes('?') || hostname.includes('#')) {
        return 'Enter a hostname only, without a protocol, port, path, query, or fragment.';
    }
    if (hostname.length > 253) return 'Hostname must be 253 characters or fewer.';
    const labels = hostname.split('.');
    if (labels.length < 2) return 'Use a fully qualified hostname such as erp.example.com.';
    if (labels.some((label) => label.length === 0 || label.length > 63 || !/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/.test(label))) {
        return 'Hostname labels may contain letters, numbers, and internal hyphens only.';
    }
    return null;
}

export function isFuture(value: string | null | undefined): boolean {
    if (!value) return false;
    const timestamp = new Date(value).getTime();
    return Number.isFinite(timestamp) && timestamp > Date.now();
}
