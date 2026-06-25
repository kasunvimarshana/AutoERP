import { useMemo, useState, type FormEvent } from 'react';
import { useSearchParams } from 'react-router-dom';
import { LinkButton, Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Select } from '@/shared/components/Select';
import { useApi } from '@/shared/hooks/useApi';
import type { NamedResource } from '@/shared/types/common';
import { AUDIT_EVENT_CATEGORIES, type AuditLogSummary } from '@/modules/audit/auditTypes';
import { getPlatformTenantTarget, listPlatformTenantTargets } from '@/modules/tenant/tenantApi';
import { platformAdministrationApi } from './platformAdministrationApi';
import { formatPlatformDateTime, humanizePlatformValue } from './platformAdministrationPresentation';
import type { PlatformAuditFilters } from './platformAdministrationTypes';

interface TenantOption extends NamedResource {
    code: string;
}

const emptyFilters: PlatformAuditFilters = { per_page: 25 };

export default function PlatformAuditPage() {
    const [searchParams, setSearchParams] = useSearchParams();
    const initialFilters = useMemo(() => readAuditFilters(searchParams), [searchParams]);
    const initialTenantId = positiveInteger(searchParams.get('tenant_id'));
    const [draft, setDraft] = useState<PlatformAuditFilters>(initialFilters);
    const [filters, setFilters] = useState<PlatformAuditFilters>(initialFilters);
    const [tenantSelection, setTenantSelection] = useState<TenantOption | null | undefined>(undefined);
    const [cursorHistory, setCursorHistory] = useState<string[]>([]);
    const cursor = cursorHistory.at(-1);
    const linkedTenant = useApi(
        (signal) => getPlatformTenantTarget('audit', initialTenantId ?? 0, signal),
        [initialTenantId],
        initialTenantId !== null,
        false,
    );
    const tenant = tenantSelection === undefined
        ? linkedTenant.data ? { id: linkedTenant.data.id, name: linkedTenant.data.name, code: linkedTenant.data.code } : null
        : tenantSelection;
    const request = useApi(
        (signal) => platformAdministrationApi.listAudit({ ...filters, cursor }, signal),
        [filters, cursor],
        true,
        true,
    );

    const columns = useMemo<DataColumn<AuditLogSummary>[]>(() => [
        {
            key: 'event',
            header: 'Event',
            render: (record) => (
                <div>
                    <p className="font-semibold text-slate-900">{humanizePlatformValue(record.event_name)}</p>
                    <p className="text-xs text-slate-500">{record.source_module} · {humanizePlatformValue(record.event_category)}</p>
                </div>
            ),
        },
        {
            key: 'tenant',
            header: 'Tenant scope',
            render: (record) => record.tenant.name ?? 'Platform control plane',
        },
        {
            key: 'actor',
            header: 'Actor',
            render: (record) => (
                <div>
                    <p>{record.actor.name ?? humanizePlatformValue(record.actor.type)}</p>
                    <p className="text-xs text-slate-500">{humanizePlatformValue(record.actor.type)}</p>
                </div>
            ),
        },
        {
            key: 'subject',
            header: 'Subject',
            render: (record) => (
                <div>
                    <p>{record.subject.reference ?? humanizePlatformValue(record.subject.type)}</p>
                    <p className="text-xs text-slate-500">{humanizePlatformValue(record.subject.type)}</p>
                </div>
            ),
        },
        { key: 'occurred', header: 'Occurred', render: (record) => formatPlatformDateTime(record.occurred_at) },
        {
            key: 'actions',
            header: '',
            className: 'text-right',
            mobile: false,
            render: (record) => (
                <LinkButton variant="secondary" className="min-h-8 px-3 py-1 text-xs" to={`/administration/platform-audit/${record.id}`}>
                    Review
                </LinkButton>
            ),
        },
    ], []);

    function applyFilters(event: FormEvent) {
        event.preventDefault();
        const next = cleanFilters({ ...draft, tenant_id: tenant?.id });
        setFilters(next);
        setSearchParams(auditFiltersToSearchParams(next), { replace: true });
        setCursorHistory([]);
    }

    function resetFilters() {
        setDraft(emptyFilters);
        setFilters(emptyFilters);
        setTenantSelection(null);
        setSearchParams(new URLSearchParams(), { replace: true });
        setCursorHistory([]);
    }

    return (
        <>
            <ContentHeader
                title="Platform audit"
                description="Search immutable control-plane and tenant-scoped audit events. Sensitive payloads remain permission-gated and are never used as operational input."
            />

            {filters.subject_id ? (
                <div className="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
                    <p>This audit view is scoped to the related {humanizePlatformValue(filters.subject_type ?? 'record')} selected from another administration screen.</p>
                    <Button variant="secondary" onClick={() => {
                        const next = cleanFilters({ ...filters, subject_type: undefined, subject_id: undefined });
                        setDraft(next);
                        setFilters(next);
                        setSearchParams(auditFiltersToSearchParams(next), { replace: true });
                        setCursorHistory([]);
                    }}>Remove related-record filter</Button>
                </div>
            ) : null}

            <form className="mb-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm" onSubmit={applyFilters}>
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <GenericLookupSelect
                        label="Tenant scope"
                        value={tenant}
                        onChange={(selected) => { setTenantSelection(selected); return true; }}
                        search={searchTenants}
                        formatLabel={(selected) => `${selected.name} · ${selected.code}`}
                        placeholder="Search tenant name or code"
                        minSearchLength={0}
                        loadOnOpen
                    />
                    <Select
                        label="Category"
                        value={draft.event_category ?? ''}
                        options={AUDIT_EVENT_CATEGORIES.map((value) => ({ value, label: humanizePlatformValue(value) }))}
                        placeholder="All categories"
                        onChange={(event) => setDraft({ ...draft, event_category: event.target.value })}
                    />
                    <Input label="Event name" value={draft.event_name ?? ''} onChange={(event) => setDraft({ ...draft, event_name: event.target.value })} placeholder="Example: platform.operator.status_changed" />
                    <Input label="Source module" value={draft.source_module ?? ''} onChange={(event) => setDraft({ ...draft, source_module: event.target.value })} placeholder="Example: tenant or auth" />
                    <Select
                        label="Actor type"
                        value={draft.actor_type ?? ''}
                        options={['user', 'system', 'integration', 'job'].map((value) => ({ value, label: humanizePlatformValue(value) }))}
                        placeholder="All actor types"
                        onChange={(event) => setDraft({ ...draft, actor_type: event.target.value })}
                    />
                    <Input label="Subject type" value={draft.subject_type ?? ''} onChange={(event) => setDraft({ ...draft, subject_type: event.target.value })} placeholder="Example: tenant or platform_operator" />
                    <Input label="From date" type="date" value={draft.from_date ?? ''} onChange={(event) => setDraft({ ...draft, from_date: event.target.value })} />
                    <Input label="To date" type="date" value={draft.to_date ?? ''} onChange={(event) => setDraft({ ...draft, to_date: event.target.value })} />
                    <Select
                        label="Rows"
                        value={String(draft.per_page ?? 25)}
                        options={[25, 50, 100].map((value) => ({ value: String(value), label: String(value) }))}
                        onChange={(event) => setDraft({ ...draft, per_page: Number(event.target.value) })}
                    />
                </div>
                <div className="mt-4 flex flex-wrap justify-end gap-2">
                    <Button variant="secondary" onClick={resetFilters}>Reset</Button>
                    <Button type="submit">Apply filters</Button>
                </div>
            </form>

            <ErrorAlert error={request.error} title="Unable to load platform audit records" />
            {request.loading ? <LoadingState label="Loading platform audit records..." /> : (
                <DataTable
                    rows={request.data?.data ?? []}
                    columns={columns}
                    rowKey={(record) => record.id}
                    rowHref={(record) => `/administration/platform-audit/${record.id}`}
                    emptyMessage="No platform audit events match the current filters."
                    mobileSummary={(record) => humanizePlatformValue(record.event_name)}
                    mobileDetails={(record) => (
                        <div className="space-y-1">
                            <p>{record.tenant.name ?? 'Platform control plane'}</p>
                            <p>{record.actor.name ?? humanizePlatformValue(record.actor.type)}</p>
                            <p>{formatPlatformDateTime(record.occurred_at)}</p>
                        </div>
                    )}
                />
            )}

            <div className="mt-4 flex justify-between gap-3">
                <Button variant="secondary" disabled={cursorHistory.length === 0 || request.loading} onClick={() => setCursorHistory((current) => current.slice(0, -1))}>Previous</Button>
                <Button
                    variant="secondary"
                    disabled={!request.data?.meta.next_cursor || request.loading}
                    onClick={() => request.data?.meta.next_cursor && setCursorHistory((current) => [...current, request.data!.meta.next_cursor!])}
                >
                    Next
                </Button>
            </div>
        </>
    );
}

async function searchTenants({ search, page, perPage, signal }: { search: string; page: number; perPage: number; signal: AbortSignal }) {
    return listPlatformTenantTargets('audit', { search, page, perPage, signal });
}

function cleanFilters(filters: PlatformAuditFilters): PlatformAuditFilters {
    return Object.fromEntries(
        Object.entries(filters).filter(([, value]) => value !== '' && value !== null && value !== undefined),
    ) as PlatformAuditFilters;
}


function readAuditFilters(params: URLSearchParams): PlatformAuditFilters {
    const perPage = positiveInteger(params.get('per_page'));
    const tenantId = positiveInteger(params.get('tenant_id'));
    return cleanFilters({
        per_page: perPage && [25, 50, 100].includes(perPage) ? perPage : 25,
        tenant_id: tenantId ?? undefined,
        event_category: params.get('event_category') ?? undefined,
        event_name: params.get('event_name') ?? undefined,
        source_module: params.get('source_module') ?? undefined,
        actor_type: params.get('actor_type') ?? undefined,
        actor_id: params.get('actor_id') ?? undefined,
        subject_type: params.get('subject_type') ?? undefined,
        subject_id: params.get('subject_id') ?? undefined,
        from_date: params.get('from_date') ?? undefined,
        to_date: params.get('to_date') ?? undefined,
    });
}

function auditFiltersToSearchParams(filters: PlatformAuditFilters): URLSearchParams {
    const params = new URLSearchParams();
    for (const [key, value] of Object.entries(filters)) {
        if (value !== undefined && value !== null && value !== '' && key !== 'cursor') {
            params.set(key, String(value));
        }
    }
    return params;
}

function positiveInteger(value: string | null): number | null {
    const parsed = Number(value);
    return Number.isSafeInteger(parsed) && parsed > 0 ? parsed : null;
}
