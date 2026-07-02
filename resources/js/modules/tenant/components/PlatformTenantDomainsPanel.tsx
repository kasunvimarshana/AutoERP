import { useState } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { ConfirmDialog } from '@/shared/components/ConfirmDialog';
import { CopyButton } from '@/shared/components/CopyButton';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { InlineFieldAction } from '@/shared/components/InlineFieldAction';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { SuccessAlert } from '@/shared/components/SuccessAlert';
import { useApi } from '@/shared/hooks/useApi';
import {
    changePlatformTenantDomain,
    createPlatformTenantDomain,
    deletePlatformTenantDomain,
    getTenantOnboardingReadiness,
    listPlatformTenantDomains,
    requestPlatformDomainVerification,
    verifyPlatformTenantDomain,
} from '../tenantApi';
import {
    formatTenantDateTime,
    hostnameError,
    isFuture,
    normalizeHostname,
    platformHostDomainError,
} from '../tenantPresentation';
import type {
    DomainVerificationChallenge,
    TenantDomain,
    TenantOnboardingReadiness,
    TenantRecord,
} from '../tenantTypes';
import { platformAuditHref } from '@/modules/platform-administration/platformAdministrationPresentation';

interface Props { tenant: TenantRecord; canManage: boolean; canAudit: boolean; disabled?: boolean; onChanged: () => void; }
type DomainAction = 'primary' | 'disable' | 'delete';
type PendingAction = { action: DomainAction; domain: TenantDomain } | null;

export function PlatformTenantDomainsPanel({ tenant, canManage, canAudit, disabled = false, onChanged }: Props) {
    const [page, setPage] = useState(1);
    const domains = useApi((signal) => listPlatformTenantDomains(tenant.id, { page, per_page: 20 }, signal), [tenant.id, page], true, false);
    const readiness = useApi(
        (signal) => getTenantOnboardingReadiness(tenant.id, signal),
        [tenant.id, tenant.row_version, tenant.onboarding?.row_version],
        true,
        false,
    );
    const items = domains.data?.data ?? [];
    const [domainName, setDomainName] = useState('');
    const [busyId, setBusyId] = useState<number | null>(null);
    const [error, setError] = useState<ApiError | null>(null);
    const [success, setSuccess] = useState<string | null>(null);
    const [challenge, setChallenge] = useState<{ domainId: number; value: DomainVerificationChallenge } | null>(null);
    const [pendingAction, setPendingAction] = useState<PendingAction>(null);
    const normalizedDomain = normalizeHostname(domainName);
    const validationError = domainName.trim() === ''
        ? null
        : hostnameError(domainName) ?? platformHostDomainError(domainName);
    const mutationDisabled = disabled || busyId !== null || tenant.status === 'archived';

    async function mutate(id: number, operation: () => Promise<unknown>, message: string) {
        setBusyId(id); setError(null); setSuccess(null);
        try { await operation(); setSuccess(message); domains.reload(); readiness.reload(); onChanged(); }
        catch (requestError: unknown) { setError(toApiError(requestError)); domains.reload(); }
        finally { setBusyId(null); }
    }

    async function createDomain() {
        const nextError = hostnameError(domainName) ?? platformHostDomainError(domainName);
        if (nextError) return;

        setBusyId(0); setError(null); setSuccess(null);
        try { const created = await createPlatformTenantDomain(tenant.id, normalizedDomain); setDomainName(''); setSuccess(`${created.domain} was added. Generate its DNS challenge next.`); domains.reload(); readiness.reload(); onChanged(); }
        catch (requestError: unknown) { setError(toApiError(requestError)); }
        finally { setBusyId(null); }
    }

    async function requestChallenge(domain: TenantDomain) {
        setBusyId(domain.id); setError(null); setSuccess(null);
        try { const result = await requestPlatformDomainVerification(tenant.id, domain); setChallenge({ domainId: domain.id, value: result.challenge }); setSuccess(`DNS instructions were generated for ${domain.domain}.`); domains.reload(); }
        catch (requestError: unknown) { setError(toApiError(requestError)); }
        finally { setBusyId(null); }
    }

    function verify(domain: TenantDomain) {
        void mutate(domain.id, () => verifyPlatformTenantDomain(tenant.id, domain), `Ownership verification for ${domain.domain} was queued. Operational routing, TLS, and reachability are checked separately.`);
    }

    async function confirmAction() {
        if (!pendingAction) return;
        const target = pendingAction; setPendingAction(null);
        if (target.action === 'delete') {
            await mutate(target.domain.id, () => deletePlatformTenantDomain(tenant.id, target.domain), `${target.domain.domain} was deleted.`);
        } else {
            await mutate(target.domain.id, () => changePlatformTenantDomain(tenant.id, target.domain, target.action as Exclude<DomainAction, 'delete'>), target.action === 'primary' ? `${target.domain.domain} is now the primary access domain.` : `${target.domain.domain} was disabled.`);
        }
        if (challenge?.domainId === target.domain.id) setChallenge(null);
    }

    return (
        <section id="tenant-domain-step" className="scroll-mt-24 space-y-4" aria-labelledby="tenant-domains-title">
            <div><p className="text-xs font-semibold uppercase tracking-wide text-blue-700">Step 3</p><h3 id="tenant-domains-title" className="mt-1 font-semibold text-slate-900">Configure tenant routing</h3><p className="mt-1 text-sm text-slate-500">Production uses a verified public primary domain. Local/testing may instead use the explicit tenant-code fallback confirmed by backend readiness.</p><p className="mt-1 text-xs text-slate-500">Localhost and IP addresses are access hosts, not tenant-domain records, and are never stored in the tenant domain table.</p></div>
            <SuccessAlert message={success} onDismiss={() => setSuccess(null)} />
            <ErrorAlert error={readiness.error} title="Unable to inspect tenant routing" />
            <ErrorAlert error={domains.error} title="Unable to load tenant domains" /><ErrorAlert error={error} title="Domain action failed" />
            {readiness.loading && !readiness.data ? <LoadingState label="Checking tenant routing..." /> : null}
            {readiness.data ? <TenantRoutingGuidance tenant={tenant} readiness={readiness.data} /> : null}
            {canManage && tenant.status !== 'archived' ? (
                <div className="rounded-lg border border-slate-200 p-4">
                    <InlineFieldAction
                        id="tenant-hostname"
                        label="Public tenant hostname"
                        input={(
                            <Input
                                id="tenant-hostname"
                                value={domainName}
                                onChange={(event) => setDomainName(event.target.value)}
                                placeholder="erp.example.com"
                                error={validationError ?? undefined}
                                disabled={mutationDisabled}
                            />
                        )}
                        action={(
                            <Button
                                disabled={mutationDisabled || normalizedDomain === '' || Boolean(validationError)}
                                loading={busyId === 0}
                                onClick={() => void createDomain()}
                            >
                                Add domain
                            </Button>
                        )}
                        hint="Public hostname only. Protocols, ports, paths, queries, fragments, localhost, and IP addresses are rejected."
                    />
                </div>
            ) : null}
            {!canManage ? (
                <p className="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                    You have read-only access. Domain changes require the tenant-domain management permission.
                </p>
            ) : null}
            {domains.loading && !domains.data ? <LoadingState label="Loading tenant domains..." /> : null}
            <div className="space-y-3">
                {items.map((domain) => {
                    const activeChallenge = challenge?.domainId === domain.id || isFuture(domain.verification_expires_at);
                    return <article key={domain.id} className="rounded-lg border border-slate-200 p-4"><div className="flex flex-wrap items-start justify-between gap-3"><div><div className="flex flex-wrap items-center gap-2"><p className="font-semibold text-slate-900">{domain.domain}</p>{domain.is_primary ? <span className="rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold text-blue-800">Primary</span> : null}</div><div className="mt-2 flex flex-wrap gap-2"><StatusBadge status={`ownership_${domain.ownership_status}`} /><StatusBadge status={`routing_${domain.routing_status}`} /><StatusBadge status={`tls_${domain.tls_status}`} /><StatusBadge status={`reachability_${domain.reachability_status}`} /><StatusBadge status={domain.operational_status} /></div><p className="mt-2 text-xs text-slate-500">{domain.last_verified_at ? `Ownership verified ${formatTenantDateTime(domain.last_verified_at)}` : domain.verification_expires_at ? `Challenge expires ${formatTenantDateTime(domain.verification_expires_at)}` : 'DNS challenge has not been requested.'}</p>{domain.operational_error_message ? <p className="mt-2 text-xs text-rose-700">{domain.operational_error_message}</p> : null}</div><div className="flex flex-wrap gap-2">{canAudit ? <LinkButton variant="secondary" to={platformAuditHref({ tenant_id: tenant.id, subject_type: 'tenant_domain', subject_id: domain.id })}>Audit history</LinkButton> : null}{canManage && tenant.status !== 'archived' ? <>{domain.ownership_status !== 'verified' ? <><Button variant="secondary" disabled={mutationDisabled} loading={busyId === domain.id} onClick={() => void requestChallenge(domain)}>{activeChallenge ? 'Refresh DNS instructions' : 'Get DNS instructions'}</Button><Button disabled={mutationDisabled || !activeChallenge} loading={busyId === domain.id} onClick={() => verify(domain)}>Verify ownership</Button></> : null}{domain.operational_status === 'ready' && !domain.is_primary ? <Button variant="secondary" disabled={mutationDisabled} onClick={() => setPendingAction({ action: 'primary', domain })}>Make primary</Button> : null}{domain.status !== 'disabled' ? <Button variant="secondary" disabled={mutationDisabled} onClick={() => setPendingAction({ action: 'disable', domain })}>Disable</Button> : null}{!domain.is_primary ? <Button variant="danger" disabled={mutationDisabled} onClick={() => setPendingAction({ action: 'delete', domain })}>Delete</Button> : null}</> : null}</div></div>{challenge?.domainId === domain.id ? <div className="mt-4 space-y-3 rounded-lg bg-slate-50 p-4 text-sm text-slate-700"><DnsValue label="TXT host" value={challenge.value.host} /><DnsValue label="TXT value" value={challenge.value.value} /><p className="text-xs text-slate-500">Expires {formatTenantDateTime(challenge.value.expires_at)}. Publish the record, allow DNS propagation, then verify ownership.</p></div> : null}</article>;
                })}
                {items.length === 0 && !domains.loading ? <p className="rounded-lg bg-slate-50 p-4 text-sm text-slate-500">{readiness.data?.routing.mode === 'local_fallback' ? 'No public tenant domain exists. Local/testing routing is already ready through the configured fallback.' : 'No public tenant domain exists.'}</p> : null}
            </div>
            <Pagination meta={domains.data?.meta} onPageChange={setPage} />
            <ConfirmDialog open={pendingAction !== null} title={pendingAction ? actionTitle(pendingAction.action) : 'Confirm domain action'} message={pendingAction ? actionMessage(pendingAction, items, tenant.status) : null} confirmLabel={pendingAction ? actionConfirmLabel(pendingAction.action) : 'Confirm'} danger={pendingAction?.action !== 'primary'} loading={busyId !== null} onCancel={() => setPendingAction(null)} onConfirm={() => void confirmAction()} />
        </section>
    );
}

function TenantRoutingGuidance({ tenant, readiness }: { tenant: TenantRecord; readiness: TenantOnboardingReadiness }) {
    const routing = readiness.routing;
    const localFallback = routing.local_fallback;

    if (routing.mode === 'local_fallback') {
        return (
            <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-950">
                <p className="font-semibold">Local/testing routing is ready</p>
                <p className="mt-1">Tenant <strong>{tenant.code}</strong> resolves through the configured fallback. Do not add <code>127.0.0.1</code> or <code>localhost</code> as tenant domains; continue to subscription and final readiness.</p>
            </div>
        );
    }

    if (localFallback?.supported) {
        const tenantCodeSetting = `TENANT_LOCAL_FALLBACK_TENANT_CODE=${tenant.code}`;
        return (
            <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
                <p className="font-semibold">Local/testing routing is not ready for this tenant</p>
                <p className="mt-1">Configure the backend for tenant code <strong>{tenant.code}</strong>, clear Laravel configuration cache, then refresh this step. A public domain is optional for local/testing only.</p>
                {localFallback.enabled && localFallback.configured_tenant_code && !localFallback.matches_tenant ? (
                    <p className="mt-2 text-xs">The fallback currently targets <strong>{localFallback.configured_tenant_code}</strong>, not {tenant.code}.</p>
                ) : null}
                <div className="mt-3 space-y-2">
                    <ConfigurationValue value="TENANT_LOCAL_FALLBACK_ENABLED=true" />
                    <ConfigurationValue value={tenantCodeSetting} />
                </div>
            </div>
        );
    }

    return (
        <div className="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-950">
            <p className="font-semibold">A verified public domain is required in this environment</p>
            <p className="mt-1">Add a real fully qualified hostname, complete DNS ownership verification, and wait for routing, TLS, and reachability checks. IP addresses cannot become tenant domains.</p>
        </div>
    );
}

function ConfigurationValue({ value }: { value: string }) {
    return (
        <div className="grid gap-2 sm:grid-cols-[1fr_auto] sm:items-center">
            <code className="break-all rounded bg-white px-3 py-2 font-mono text-xs">{value}</code>
            <CopyButton value={value} label="Copy configuration value" />
        </div>
    );
}

function DnsValue({ label, value }: { label: string; value: string }) { return <div className="grid gap-2 sm:grid-cols-[7rem_1fr_auto] sm:items-center"><strong>{label}</strong><code className="break-all rounded bg-white px-3 py-2 font-mono text-xs">{value}</code><CopyButton value={value} label={`Copy ${label.toLowerCase()}`} /></div>; }
function actionTitle(action: DomainAction): string { return action === 'primary' ? 'Change primary tenant domain' : action === 'disable' ? 'Disable tenant domain' : 'Delete tenant domain'; }
function actionConfirmLabel(action: DomainAction): string { return action === 'primary' ? 'Make primary' : action === 'disable' ? 'Disable domain' : 'Delete domain'; }
function actionMessage(pending: Exclude<PendingAction, null>, domains: TenantDomain[], tenantStatus: TenantRecord['status']) { const fallback = domains.find((domain) => domain.id !== pending.domain.id && domain.operational_status === 'ready'); if (pending.action === 'primary') return <p>Use <strong>{pending.domain.domain}</strong> as the tenant’s primary access domain?</p>; if (pending.action === 'delete') return <p>Permanently delete <strong>{pending.domain.domain}</strong>?</p>; if (pending.domain.is_primary) return <div className="space-y-2"><p>Disable the primary domain <strong>{pending.domain.domain}</strong>?</p><p>{fallback ? `${fallback.domain} can be promoted as the operational fallback.` : tenantStatus === 'active' ? 'No operational fallback exists. The backend will suspend the tenant to prevent an unreachable workspace.' : 'A new ready primary domain will be required before activation.'}</p></div>; return <p>Disable <strong>{pending.domain.domain}</strong>?</p>; }
