import { useMemo, useState, type FormEvent } from 'react';
import { Button, LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Select } from '@/shared/components/Select';
import { useApi } from '@/shared/hooks/useApi';
import { auditApi } from './auditApi';
import { AUDIT_EVENT_CATEGORIES, type AuditListFilters, type AuditLogSummary } from './auditTypes';

const emptyFilters: AuditListFilters = { per_page: 25 };

export default function AuditLogListPage() {
    const [draft, setDraft] = useState<AuditListFilters>(emptyFilters);
    const [filters, setFilters] = useState<AuditListFilters>(emptyFilters);
    const [cursorHistory, setCursorHistory] = useState<string[]>([]);
    const cursor = cursorHistory.at(-1);
    const request = useApi(
        (signal) => auditApi.list({ ...filters, cursor }, signal),
        [filters, cursor],
        true,
        true,
    );

    const columns = useMemo<DataColumn<AuditLogSummary>[]>(() => [
        {
            key: 'event',
            header: 'Event',
            render: (row) => <div><p className="font-semibold text-slate-900">{row.event_name}</p><p className="text-xs text-slate-500">{row.event_category}</p></div>,
        },
        { key: 'module', header: 'Module', render: (row) => row.source_module },
        {
            key: 'actor',
            header: 'Actor',
            render: (row) => <div><p>{row.actor.name ?? row.actor.id ?? '-'}</p><p className="text-xs text-slate-500">{row.actor.type}</p></div>,
        },
        {
            key: 'subject',
            header: 'Subject',
            render: (row) => <div><p>{row.subject.reference ?? row.subject.id}</p><p className="text-xs text-slate-500">{row.subject.type}</p></div>,
        },
        { key: 'organization', header: 'Organization Unit', render: (row) => row.organization_unit.name ?? '-' },
        { key: 'occurred_at', header: 'Occurred', render: (row) => formatDateTime(row.occurred_at) },
        {
            key: 'actions',
            header: '',
            className: 'text-right',
            mobile: false,
            render: (row) => <LinkButton variant="secondary" className="min-h-8 px-3 py-1 text-xs" to={`/administration/audit-logs/${row.id}`}>View</LinkButton>,
        },
    ], []);

    const applyFilters = (event: FormEvent) => {
        event.preventDefault();
        setFilters(cleanFilters(draft));
        setCursorHistory([]);
    };

    const resetFilters = () => {
        setDraft(emptyFilters);
        setFilters(emptyFilters);
        setCursorHistory([]);
    };

    const nextCursor = request.data?.meta.next_cursor;

    return (
        <>
            <ContentHeader title="Audit Logs" description="Read-only, tenant-scoped history of security-sensitive and business events." />
            <form className="mb-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm" onSubmit={applyFilters}>
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <Select label="Category" value={draft.event_category ?? ''} options={categoryOptions} placeholder="All categories" onChange={(event) => setDraft({ ...draft, event_category: event.target.value })} />
                    <Input label="Event name" value={draft.event_name ?? ''} onChange={(event) => setDraft({ ...draft, event_name: event.target.value })} placeholder="purchase.fast_purchase.completed" />
                    <Input label="Source module" value={draft.source_module ?? ''} onChange={(event) => setDraft({ ...draft, source_module: event.target.value })} placeholder="purchase" />
                    <Select label="Actor type" value={draft.actor_type ?? ''} options={actorOptions} placeholder="All actor types" onChange={(event) => setDraft({ ...draft, actor_type: event.target.value })} />
                    <Input label="Actor ID" value={draft.actor_id ?? ''} onChange={(event) => setDraft({ ...draft, actor_id: event.target.value })} />
                    <Input label="Subject type" value={draft.subject_type ?? ''} onChange={(event) => setDraft({ ...draft, subject_type: event.target.value })} />
                    <Input label="Subject ID" value={draft.subject_id ?? ''} onChange={(event) => setDraft({ ...draft, subject_id: event.target.value })} />
                    <Select label="Rows" value={String(draft.per_page ?? 25)} options={pageSizeOptions} onChange={(event) => setDraft({ ...draft, per_page: Number(event.target.value) })} />
                    <Input label="From date" type="date" value={draft.from_date ?? ''} onChange={(event) => setDraft({ ...draft, from_date: event.target.value })} />
                    <Input label="To date" type="date" value={draft.to_date ?? ''} onChange={(event) => setDraft({ ...draft, to_date: event.target.value })} />
                </div>
                <div className="mt-4 flex flex-wrap justify-end gap-2">
                    <Button variant="secondary" onClick={resetFilters}>Reset</Button>
                    <Button type="submit">Apply filters</Button>
                </div>
            </form>
            <ErrorAlert error={request.error} />
            {request.loading ? <LoadingState label="Loading audit logs..." /> : (
                <DataTable
                    rows={request.data?.data ?? []}
                    columns={columns}
                    rowKey={(row) => row.id}
                    rowHref={(row) => `/administration/audit-logs/${row.id}`}
                    emptyMessage="No audit events match the current filters."
                />
            )}
            <div className="mt-4 flex justify-between gap-3">
                <Button variant="secondary" disabled={cursorHistory.length === 0 || request.loading} onClick={() => setCursorHistory((current) => current.slice(0, -1))}>Previous</Button>
                <Button variant="secondary" disabled={!nextCursor || request.loading} onClick={() => nextCursor && setCursorHistory((current) => [...current, nextCursor])}>Next</Button>
            </div>
        </>
    );
}

function cleanFilters(filters: AuditListFilters): AuditListFilters {
    return Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== '' && value !== null && value !== undefined)) as AuditListFilters;
}

function formatDateTime(value: string): string {
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? value : new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'medium' }).format(date);
}

const categoryOptions = AUDIT_EVENT_CATEGORIES.map((value) => ({ value, label: value.replaceAll('_', ' ') }));
const actorOptions = ['user', 'system', 'integration', 'job'].map((value) => ({ value, label: value }));
const pageSizeOptions = [25, 50, 100].map((value) => ({ value: String(value), label: String(value) }));
