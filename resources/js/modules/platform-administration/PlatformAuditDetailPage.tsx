import { LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { useApi } from '@/shared/hooks/useApi';
import { Link, useParams } from 'react-router-dom';
import { platformAdministrationApi } from './platformAdministrationApi';
import { parsePositiveInteger } from '@/shared/utils/routeParams';
import { formatPlatformDateTime, humanizePlatformValue } from './platformAdministrationPresentation';

export default function PlatformAuditDetailPage() {
    const { id: idParam } = useParams();
    const id = parsePositiveInteger(idParam);
    const request = useApi((signal) => platformAdministrationApi.getAudit(id ?? 0, signal), [id], id !== null, true);

    if (id === null) {
        return <div className="py-20 text-center"><ContentHeader title="Invalid audit link" description="This link does not identify a valid audit event. No data request was sent." /><Link className="text-sm font-semibold text-sky-700 hover:underline" to="/administration/platform-audit">Return to platform audit</Link></div>;
    }
    if (request.loading) return <LoadingState label="Loading platform audit event..." />;
    if (request.error) return <ErrorAlert error={request.error} title="Unable to load platform audit event" />;
    if (!request.data) return null;

    const record = request.data;
    return (
        <>
            <ContentHeader
                title={humanizePlatformValue(record.event_name)}
                description="Read-only platform audit event. Audit records are evidence and cannot be edited from this screen."
                actions={<LinkButton variant="secondary" to="/administration/platform-audit">Back to audit</LinkButton>}
            />
            <div className="space-y-5">
                <Panel title="Event summary">
                    <dl className="grid gap-4 text-sm md:grid-cols-2 xl:grid-cols-4">
                        <Detail label="Category" value={humanizePlatformValue(record.event_category)} />
                        <Detail label="Source module" value={humanizePlatformValue(record.source_module)} />
                        <Detail label="Tenant scope" value={record.tenant.name ?? 'Platform control plane'} />
                        <Detail label="Organization unit" value={record.organization_unit.name ?? 'Not applicable'} />
                        <Detail label="Actor" value={record.actor.name ?? humanizePlatformValue(record.actor.type)} />
                        <Detail label="Subject" value={record.subject.reference ?? humanizePlatformValue(record.subject.type)} />
                        <Detail label="Occurred" value={formatPlatformDateTime(record.occurred_at)} />
                        <Detail label="Recorded" value={formatPlatformDateTime(record.recorded_at)} />
                    </dl>
                </Panel>

                <Panel title="Source context">
                    <dl className="grid gap-4 text-sm md:grid-cols-2 xl:grid-cols-4">
                        <Detail label="Module" value={humanizePlatformValue(record.source.module)} />
                        <Detail label="Source type" value={humanizePlatformValue(record.source.type)} />
                        <Detail label="Source reference" value={record.source.reference ?? 'Not recorded'} />
                        <Detail label="Tags" value={record.tags.length > 0 ? record.tags.map(humanizePlatformValue).join(', ') : 'None'} />
                    </dl>
                </Panel>

                {record.sensitive_details_visible ? (
                    <>
                        <Panel title="Recorded changes">
                            <StructuredObject value={record.changes} empty="No structured changes were recorded." />
                        </Panel>
                        <Panel title="Audit metadata">
                            <StructuredObject value={record.metadata} empty="No additional metadata was recorded." />
                        </Panel>
                        {record.request ? (
                            <Panel title="Request context">
                                <dl className="grid gap-4 text-sm md:grid-cols-2 xl:grid-cols-3">
                                    <Detail label="HTTP method" value={record.request.method ?? 'Not captured'} />
                                    <Detail label="Route" value={record.request.route_name ?? record.request.route_path ?? 'Not captured'} />
                                    <Detail label="IP address" value={record.request.ip_address ?? 'Not captured'} />
                                    <Detail label="Authentication guard" value={record.request.actor_guard ?? 'Not captured'} />
                                    <Detail label="Authentication provider" value={record.request.actor_provider ?? 'Not captured'} />
                                    <Detail label="User agent" value={record.request.user_agent ?? 'Not captured'} />
                                </dl>
                            </Panel>
                        ) : null}
                    </>
                ) : (
                    <div className="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                        Sensitive changes, metadata, and request context are hidden because the current operator has standard audit-view permission only.
                    </div>
                )}
            </div>
        </>
    );
}

function Detail({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</dt>
            <dd className="mt-1 break-words font-medium text-slate-900">{value}</dd>
        </div>
    );
}

function StructuredObject({ value, empty }: { value: Record<string, unknown> | null | undefined; empty: string }) {
    if (!value || Object.keys(value).length === 0) return <p className="text-sm text-slate-500">{empty}</p>;
    return (
        <dl className="grid gap-3 text-sm md:grid-cols-2">
            {Object.entries(value).map(([key, entry]) => (
                <div key={key} className="rounded-lg bg-slate-50 p-3">
                    <dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">{humanizePlatformValue(key)}</dt>
                    <dd className="mt-1 break-words text-slate-900">{formatValue(entry)}</dd>
                </div>
            ))}
        </dl>
    );
}

function formatValue(value: unknown): string {
    if (value === null || value === undefined || value === '') return 'Not recorded';
    if (typeof value === 'boolean') return value ? 'Yes' : 'No';
    if (typeof value === 'string' || typeof value === 'number') return String(value);
    return JSON.stringify(value, null, 2);
}
