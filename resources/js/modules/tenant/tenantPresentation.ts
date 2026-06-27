import type {
    TenantOnboardingReadiness,
    TenantPlanLimits,
    TenantPlanRevision,
} from './tenantTypes';

const READINESS_LABELS: Record<string, string> = {
    schema_compatible: 'Application database schema',
    root_organization: 'Protected root organization',
    permission_catalogue: 'Permission catalogue',
    super_admin_access: 'Super Admin access',
    authentication_provider: 'Authentication provider',
    administrator_invitation_accepted: 'Administrator invitation accepted',
    operational_administrator: 'Operational tenant administrator',
    base_currency: 'Base accounting currency',
    active_plan: 'Active plan revision',
    subscription_valid: 'Current usable subscription',
    primary_domain_ready: 'Tenant routing',
};

const READINESS_STEPS: Record<string, string> = {
    schema_compatible: 'tenant-activation-step',
    schema_incompatible: 'tenant-activation-step',
    root_organization: 'tenant-foundation-step',
    permission_catalogue: 'tenant-foundation-step',
    super_admin_access: 'tenant-foundation-step',
    authentication_provider: 'tenant-foundation-step',
    administrator_invitation_accepted: 'tenant-foundation-step',
    operational_administrator: 'tenant-foundation-step',
    base_currency: 'tenant-identity-step',
    active_plan: 'tenant-subscription-step',
    subscription_valid: 'tenant-subscription-step',
    subscription_data_invalid: 'tenant-subscription-step',
    primary_domain_ready: 'tenant-domain-step',
};

const RESERVED_PUBLIC_HOSTNAMES = new Set(['localhost', 'localhost.localdomain']);
const RESERVED_PUBLIC_TLDS = new Set(['example', 'invalid', 'localhost', 'local', 'test', 'internal']);

export function readinessLabel(key: string): string {
    return READINESS_LABELS[key.toLowerCase()] ?? humanize(key);
}

export function readinessStepId(key: string): string | null {
    return READINESS_STEPS[key.toLowerCase()] ?? null;
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
    if (RESERVED_PUBLIC_HOSTNAMES.has(hostname) || isIpv4Address(hostname)) {
        return 'Localhost and IP addresses are not public tenant domains. Use the configured local/testing fallback shown in readiness.';
    }
    if (hostname.length > 253) return 'Hostname must be 253 characters or fewer.';
    const labels = hostname.split('.');
    if (labels.length < 2) return 'Use a fully qualified hostname such as erp.example.com.';
    if (RESERVED_PUBLIC_TLDS.has(labels[labels.length - 1] ?? '')) {
        return 'Use a public hostname. Reserved local, test, internal, and example domains cannot become tenant domains.';
    }
    if (labels.some((label) => label.length === 0 || label.length > 63 || !/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/.test(label))) {
        return 'Hostname labels may contain letters, numbers, and internal hyphens only.';
    }
    return null;
}

function isIpv4Address(value: string): boolean {
    const octets = value.split('.');
    return octets.length === 4
        && octets.every((octet) => /^\d{1,3}$/.test(octet) && Number(octet) <= 255);
}

export function isFuture(value: string | null | undefined): boolean {
    if (!value) return false;
    const timestamp = new Date(value).getTime();
    return Number.isFinite(timestamp) && timestamp > Date.now();
}
