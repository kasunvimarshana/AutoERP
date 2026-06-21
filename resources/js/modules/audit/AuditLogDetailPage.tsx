import { useParams } from 'react-router-dom';
import { LinkButton } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DetailGrid } from '@/shared/components/DetailGrid';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { useApi } from '@/shared/hooks/useApi';
import { auditApi } from './auditApi';

export default function AuditLogDetailPage() {
    const { id } = useParams();
    const auditId = Number(id);
    const isValidAuditId = Number.isInteger(auditId) && auditId > 0;
    const request = useApi((signal) => auditApi.get(auditId, signal), [auditId], isValidAuditId);
    const record = request.data;

    if (!isValidAuditId) {
        return (
            <>
                <ContentHeader
                    title="Audit Log Detail"
                    description="The requested audit-log identifier is invalid."
                    actions={<LinkButton variant="secondary" to="/administration/audit-logs">Back</LinkButton>}
                />
                <Panel title="Invalid audit log">
                    <p className="text-sm text-slate-600">Open an audit record from the audit-log list.</p>
                </Panel>
            </>
        );
    }

    return (
        <>
            <ContentHeader
                title={record?.event_name ?? 'Audit Log Detail'}
                description="Immutable event evidence. Sensitive payloads are visible only with the dedicated permission."
                actions={<LinkButton variant="secondary" to="/administration/audit-logs">Back</LinkButton>}
            />
            <ErrorAlert error={request.error} />
            {request.loading || !record ? <LoadingState label="Loading audit event..." /> : (
                <div className="space-y-5">
                    <Panel title="Event">
                        <DetailGrid items={[
                            { label: 'Event UUID', value: <span className="break-all font-mono text-xs">{record.event_uuid}</span> },
                            { label: 'Category', value: record.event_category },
                            { label: 'Event name', value: record.event_name },
                            { label: 'Occurred', value: formatDateTime(record.occurred_at) },
                            { label: 'Recorded', value: formatDateTime(record.recorded_at) },
                            { label: 'Source module', value: record.source_module },
                        ]} />
                    </Panel>
                    <Panel title="Scope and actor">
                        <DetailGrid items={[
                            { label: 'Tenant', value: record.tenant.name ?? record.tenant.id ?? '-' },
                            { label: 'Organization unit', value: record.organization_unit.name ?? record.organization_unit.id ?? '-' },
                            { label: 'Actor type', value: record.actor.type },
                            { label: 'Actor', value: record.actor.name ?? record.actor.id ?? '-' },
                            { label: 'Subject type', value: record.subject.type },
                            { label: 'Subject', value: record.subject.reference ?? record.subject.id },
                        ]} />
                    </Panel>
                    <Panel title="Source">
                        <DetailGrid items={[
                            { label: 'Type', value: record.source.type ?? '-' },
                            { label: 'ID', value: record.source.id ?? '-' },
                            { label: 'Reference', value: record.source.reference ?? '-' },
                            { label: 'Tags', value: record.tags.length > 0 ? record.tags.join(', ') : '-' },
                            { label: 'Sensitive details', value: record.sensitive_details_visible ? 'Visible' : 'Restricted' },
                            { label: 'Producer key', value: record.producer_key ?? '-' },
                        ]} />
                    </Panel>
                    {record.sensitive_details_visible && (
                        <>
                            <JsonPanel title="Changes" value={record.changes} />
                            <JsonPanel title="Metadata" value={record.metadata} />
                            <JsonPanel title="Request context" value={record.request} />
                        </>
                    )}
                </div>
            )}
        </>
    );
}

function JsonPanel({ title, value }: { title: string; value: unknown }) {
    return (
        <Panel title={title}>
            <pre className="max-h-[32rem] overflow-auto whitespace-pre-wrap break-words rounded-lg bg-slate-950 p-4 text-xs text-slate-100">{JSON.stringify(value ?? {}, null, 2)}</pre>
        </Panel>
    );
}

function formatDateTime(value: string): string {
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? value : new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'medium' }).format(date);
}
