import { formatTenantDateTime } from '../tenantPresentation';
import type { TenantRecord } from '../tenantTypes';

export function TenantIdentitySummary({ tenant }: { tenant: TenantRecord }) {
    return (
        <section id="tenant-identity-step" className="space-y-4" aria-labelledby="tenant-identity-title">
            <div>
                <p className="text-xs font-semibold uppercase tracking-wide text-blue-700">Step 1</p>
                <h3 id="tenant-identity-title" className="mt-1 font-semibold text-slate-900">Tenant identity and accounting base</h3>
                <p className="mt-1 text-sm text-slate-500">Confirm stable identifiers and base currency before provisioning the tenant foundation.</p>
            </div>
            <dl className="grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
                <Detail label="Tenant code" value={tenant.code} />
                <Detail label="URL slug" value={tenant.slug} />
                <Detail
                    label="Base currency"
                    value={tenant.base_currency ? `${tenant.base_currency.code} — ${tenant.base_currency.name}` : 'Required before activation'}
                    warning={!tenant.base_currency}
                />
                <Detail label="Tenant logo" value={tenant.has_logo ? 'Configured' : 'Not configured'} />
                <Detail label="Last updated" value={formatTenantDateTime(tenant.updated_at)} />
            </dl>
        </section>
    );
}

function Detail({ label, value, warning = false }: { label: string; value: string; warning?: boolean }) {
    return (
        <div className={`rounded-lg p-3 ${warning ? 'bg-amber-50' : 'bg-slate-50'}`}>
            <dt className="text-xs font-medium uppercase tracking-wide text-slate-500">{label}</dt>
            <dd className={`mt-1 font-medium ${warning ? 'text-amber-800' : 'text-slate-900'}`}>{value}</dd>
        </div>
    );
}
