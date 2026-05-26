import { useMemo } from 'react';
import { useSearchParams } from 'react-router-dom';
import { PageHeader } from '../../../components/layout/PageHeader';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { Input } from '../../../components/forms/Input';
import { DataTable, SearchFilterToolbar, StatusBadge, TablePagination, TableToolbar, type DataTableColumn } from '../../../components/tables';
import { ContentCard } from '../../../components/ui/ContentCard';
import { JsonPreview } from '../../../components/ui/JsonPreview';
import { Button } from '../../../components/ui/Button';
import { useTenant } from '../../auth/context/TenantContext';
import { useAuditLog, useAuditLogs } from '../hooks';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { formatDateTime, parsePositiveInteger } from '../../shared/utils';
import type { AuditLogRecord } from '../types';

export function AuditLogActivityPage() {
    const { tenantId } = useTenant();
    const [searchParams, setSearchParams] = useSearchParams();

    const page = parsePositiveInteger(searchParams.get('page'), 1);
    const event = searchParams.get('event') ?? '';
    const auditableType = searchParams.get('auditable_type') ?? '';
    const selectedAuditLogId = parsePositiveInteger(searchParams.get('audit_log_id'), 0);

    const auditLogsQuery = useAuditLogs({
        tenant_id: tenantId,
        page,
        per_page: 10,
        event: event || undefined,
        auditable_type: auditableType || undefined,
        sort: '-occurred_at',
    });

    const activeAuditLogId = selectedAuditLogId || auditLogsQuery.data?.items[0]?.id || 0;
    const auditLogQuery = useAuditLog(activeAuditLogId, activeAuditLogId > 0);

    function updateParams(updates: Record<string, string | number | undefined>) {
        setSearchParams((current) => {
            const next = new URLSearchParams(current);

            for (const [key, value] of Object.entries(updates)) {
                if (value === undefined || value === '') {
                    next.delete(key);
                } else {
                    next.set(key, String(value));
                }
            }

            if ('event' in updates || 'auditable_type' in updates) {
                next.set('page', '1');
            }

            return next;
        });
    }

    const columns: DataTableColumn<AuditLogRecord>[] = useMemo(
        () => [
            {
                key: 'event',
                header: 'Event',
                render: (auditLog) => (
                    <div>
                        <p className="font-medium text-stone-950">{auditLog.event}</p>
                        <p className="mt-1 text-xs text-stone-500">
                            {auditLog.auditable_type} #{auditLog.auditable_id}
                        </p>
                    </div>
                ),
            },
            { key: 'user_id', header: 'User', render: (auditLog) => <span className="text-sm text-stone-700">{auditLog.user_id ?? '-'}</span> },
            { key: 'tenant_id', header: 'Tenant', render: (auditLog) => <span className="text-sm text-stone-700">{auditLog.tenant_id ?? '-'}</span> },
            { key: 'occurred_at', header: 'Occurred', render: (auditLog) => <span className="text-sm text-stone-700">{formatDateTime(auditLog.occurred_at)}</span> },
            {
                key: 'actions',
                header: 'Detail',
                className: 'w-[10rem]',
                render: (auditLog) => (
                    <Button className="h-9 px-3 text-xs" onClick={() => updateParams({ audit_log_id: auditLog.id })} type="button" variant="secondary">
                        Inspect
                    </Button>
                ),
            },
        ],
        [setSearchParams],
    );

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[{ label: 'Audit Logs' }, { label: 'Activity' }]}
                description="Audit log activity is now backed by the real audit API, including log detail inspection for diffs, metadata, and request context."
                title="Audit Activity"
            />

            <ContentCard className="p-0">
                <TableToolbar description="Filter recent audit activity by event or auditable type, then inspect a record for the full trace payload." title="Audit trail">
                    <SearchFilterToolbar
                        filters={<Input className="w-full md:max-w-sm" label={undefined} onChange={(eventTarget) => updateParams({ auditable_type: eventTarget.target.value || undefined })} placeholder="Filter by auditable type" value={auditableType} />}
                        search={<Input className="w-full md:max-w-sm" label={undefined} onChange={(eventTarget) => updateParams({ event: eventTarget.target.value || undefined })} placeholder="Filter by event" value={event} />}
                        trailing={<div className="text-sm text-stone-500">{auditLogsQuery.data?.meta?.total ?? 0} audit events</div>}
                    />
                </TableToolbar>

                {auditLogsQuery.isPending ? (
                    <LoadingState className="m-6" lines={8} />
                ) : auditLogsQuery.isError ? (
                    isForbiddenError(auditLogsQuery.error) ? (
                        <ProtectedErrorState className="m-6" description={auditLogsQuery.error.message} />
                    ) : (
                        <ErrorState className="m-6" description={auditLogsQuery.error.message} title="Unable to load audit activity" />
                    )
                ) : (
                    <DataTable
                        columns={columns}
                        emptyState={<EmptyState className="m-6" description="No audit logs match the current filters." title="No audit activity found" />}
                        footer={<TablePagination meta={auditLogsQuery.data.meta} onPageChange={(nextPage) => updateParams({ page: nextPage })} />}
                        getRowKey={(auditLog) => auditLog.id}
                        rows={auditLogsQuery.data.items}
                    />
                )}
            </ContentCard>

            <ContentCard className="grid gap-6 lg:grid-cols-2">
                {!activeAuditLogId ? (
                    <EmptyState description="Choose an audit log from the table above to inspect the captured request context and field-level diff." title="No audit log selected" />
                ) : auditLogQuery.isPending ? (
                    <LoadingState lines={10} />
                ) : auditLogQuery.isError ? (
                    isForbiddenError(auditLogQuery.error) ? (
                        <ProtectedErrorState description={auditLogQuery.error.message} />
                    ) : (
                        <ErrorState description={auditLogQuery.error.message} title="Unable to load audit log detail" />
                    )
                ) : (
                    <>
                        <div className="space-y-4">
                            <div className="rounded-2xl border border-stone-200 bg-stone-50/70 p-5">
                                <h3 className="text-lg font-semibold text-stone-950">Request context</h3>
                                <div className="mt-4 grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <p className="text-xs uppercase tracking-[0.14em] text-stone-500">Event</p>
                                        <p className="mt-1 text-sm font-medium text-stone-950">{auditLogQuery.data.event}</p>
                                    </div>
                                    <div>
                                        <p className="text-xs uppercase tracking-[0.14em] text-stone-500">Occurred</p>
                                        <p className="mt-1 text-sm font-medium text-stone-950">{formatDateTime(auditLogQuery.data.occurred_at)}</p>
                                    </div>
                                    <div>
                                        <p className="text-xs uppercase tracking-[0.14em] text-stone-500">Auditable</p>
                                        <p className="mt-1 text-sm font-medium text-stone-950">
                                            {auditLogQuery.data.auditable_type} #{auditLogQuery.data.auditable_id}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-xs uppercase tracking-[0.14em] text-stone-500">User / Tenant</p>
                                        <p className="mt-1 text-sm font-medium text-stone-950">
                                            {auditLogQuery.data.user_id ?? '-'} / {auditLogQuery.data.tenant_id ?? '-'}
                                        </p>
                                    </div>
                                </div>
                                <div className="mt-4 flex flex-wrap gap-2">
                                    {(auditLogQuery.data.tags ?? []).map((tag) => (
                                        <StatusBadge key={tag}>{tag}</StatusBadge>
                                    ))}
                                </div>
                            </div>

                            <div className="rounded-2xl border border-stone-200 bg-white p-5">
                                <h3 className="text-lg font-semibold text-stone-950">Request metadata</h3>
                                <dl className="mt-4 space-y-3 text-sm text-stone-700">
                                    <div>
                                        <dt className="text-xs uppercase tracking-[0.14em] text-stone-500">URL</dt>
                                        <dd className="mt-1 break-all">{auditLogQuery.data.url || '-'}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-xs uppercase tracking-[0.14em] text-stone-500">IP Address</dt>
                                        <dd className="mt-1">{auditLogQuery.data.ip_address || '-'}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-xs uppercase tracking-[0.14em] text-stone-500">User Agent</dt>
                                        <dd className="mt-1 break-all">{auditLogQuery.data.user_agent || '-'}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <div className="space-y-4">
                            <div>
                                <h3 className="text-lg font-semibold text-stone-950">Field diff</h3>
                                <JsonPreview className="mt-3" value={auditLogQuery.data.diff} />
                            </div>
                            <div>
                                <h3 className="text-lg font-semibold text-stone-950">Previous values</h3>
                                <JsonPreview className="mt-3" value={auditLogQuery.data.old_values} />
                            </div>
                            <div>
                                <h3 className="text-lg font-semibold text-stone-950">New values</h3>
                                <JsonPreview className="mt-3" value={auditLogQuery.data.new_values} />
                            </div>
                            <div>
                                <h3 className="text-lg font-semibold text-stone-950">Metadata</h3>
                                <JsonPreview className="mt-3" value={auditLogQuery.data.metadata} />
                            </div>
                        </div>
                    </>
                )}
            </ContentCard>
        </div>
    );
}
