import { useState } from 'react';
import { platformAuditHref } from '@/modules/platform-administration/platformAdministrationPresentation';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button, LinkButton } from '@/shared/components/Button';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Modal } from '@/shared/components/Modal';
import { Pagination } from '@/shared/components/Pagination';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import { formatBusinessDateTime } from '@/shared/utils/businessDate';
import { listConfigurationHistory, rollbackConfigurationEntry } from '../settingsApi';
import type {
    ConfigurationEntry,
    ConfigurationRevision,
    ConfigurationScope,
    PlatformConfigurationTarget,
} from '../settingsTypes';

interface Props {
    entry: ConfigurationEntry | null;
    scope: ConfigurationScope;
    platformTarget?: PlatformConfigurationTarget;
    canRollback: boolean;
    canAudit: boolean;
    onClose: () => void;
    onChanged: (message: string) => void;
}

export function ConfigurationHistoryModal({
    entry,
    scope,
    platformTarget,
    canRollback,
    canAudit,
    onClose,
    onChanged,
}: Props) {
    const { confirm, confirmDialog } = useConfirmDialog();
    const [page, setPage] = useState(1);
    const [selected, setSelected] = useState<ConfigurationRevision | null>(null);
    const [reason, setReason] = useState('');
    const [working, setWorking] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const history = useApi(
        (signal) => listConfigurationHistory(scope, entry?.key ?? '', page, signal, platformTarget),
        [scope, entry?.key, page, platformTarget?.tenant_id, platformTarget?.organization_unit_id],
        entry !== null,
        true,
    );

    async function rollback() {
        if (!entry || !selected || reason.trim().length < 10) return;
        const accepted = await confirm({
            title: 'Restore historical configuration',
            message: (
                <div className="space-y-2">
                    <p>Restore the selected <strong>{entry.label}</strong> revision?</p>
                    <p>This creates a new immutable revision. Existing history is never rewritten.</p>
                    <p className="text-sm text-slate-600">Reason: “{reason.trim()}”</p>
                </div>
            ),
            confirmLabel: selected.configured ? 'Restore revision' : 'Restore unconfigured state',
            danger: false,
        });
        if (!accepted) return;

        setWorking(true);
        setError(null);
        try {
            const restored = await rollbackConfigurationEntry(
                scope,
                entry,
                selected.id,
                reason.trim(),
                platformTarget,
            );
            onChanged(restored
                ? `${entry.label} was restored as a new immutable revision.`
                : `${entry.label} was restored to its unconfigured state.`);
            setSelected(null);
            setReason('');
            history.reload();
        } catch (requestError: unknown) {
            setError(toApiError(requestError));
            history.reload();
        } finally {
            setWorking(false);
        }
    }

    const auditSubject = entry ? configurationAuditSubject(scope, platformTarget, entry.key) : null;

    return (
        <>
            <Modal
                open={entry !== null}
                title={entry ? `${entry.label} revision history` : 'Configuration revision history'}
                onClose={onClose}
                closeDisabled={working}
            >
                <div className="space-y-4">
                    <ErrorAlert error={history.error ?? error} title="Unable to load or restore configuration history" />
                    {history.loading && !history.data ? <LoadingState label="Loading immutable revision history..." /> : null}
                    {(history.data?.data ?? []).map((revision) => (
                        <article key={revision.id} className={`rounded-lg border p-4 ${selected?.id === revision.id ? 'border-blue-400 bg-blue-50' : 'border-slate-200'}`}>
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div className="flex flex-wrap items-center gap-2">
                                        <StatusBadge status={revision.operation} />
                                        <p className="text-sm font-semibold text-slate-900">{formatBusinessDateTime(revision.created_at)}</p>
                                    </div>
                                    <p className="mt-2 text-sm text-slate-700">{revisionValue(revision)}</p>
                                    <p className="mt-1 text-xs text-slate-500">By {revision.actor?.name ?? 'System process'}{revision.resulting_row_version ? ` · resulting version ${revision.resulting_row_version}` : ''}</p>
                                    {revision.reason ? <p className="mt-2 rounded bg-slate-50 p-2 text-xs text-slate-600">{revision.reason}</p> : null}
                                </div>
                                {canRollback ? (
                                    <Button
                                        variant="secondary"
                                        disabled={working}
                                        onClick={() => { setSelected(revision); setReason(''); setError(null); }}
                                    >
                                        Select revision
                                    </Button>
                                ) : null}
                            </div>
                        </article>
                    ))}
                    {(history.data?.data ?? []).length === 0 && !history.loading ? <p className="rounded-lg bg-slate-50 p-4 text-sm text-slate-600">No immutable revisions have been recorded for this setting.</p> : null}
                    <Pagination meta={history.data?.meta} onPageChange={setPage} />

                    {selected && canRollback ? (
                        <div className="space-y-3 rounded-lg border border-blue-200 bg-blue-50 p-4">
                            <p className="text-sm font-semibold text-blue-950">Restore revision from {formatBusinessDateTime(selected.created_at)}</p>
                            <Textarea
                                label="Rollback reason"
                                value={reason}
                                minLength={10}
                                maxLength={1000}
                                required
                                disabled={working}
                                onChange={(event) => setReason(event.target.value)}
                                hint="At least 10 characters. The reason is stored in the new revision and platform audit trail."
                            />
                            <div className="flex flex-wrap justify-end gap-2">
                                <Button variant="secondary" disabled={working} onClick={() => { setSelected(null); setReason(''); }}>Cancel rollback</Button>
                                <Button loading={working} disabled={reason.trim().length < 10} onClick={() => void rollback()}>Restore as new revision</Button>
                            </div>
                        </div>
                    ) : null}

                    <div className="flex flex-wrap justify-end gap-2 border-t border-slate-100 pt-4">
                        {canAudit && auditSubject ? (
                            <LinkButton
                                variant="secondary"
                                to={platformAuditHref({
                                    source_module: 'configuration',
                                    subject_type: 'configuration_entry',
                                    subject_id: auditSubject,
                                    tenant_id: platformTarget?.tenant_id,
                                })}
                            >
                                View related platform audit
                            </LinkButton>
                        ) : null}
                        <Button variant="secondary" disabled={working} onClick={onClose}>Close</Button>
                    </div>
                </div>
            </Modal>
            {confirmDialog}
        </>
    );
}

function revisionValue(revision: ConfigurationRevision): string {
    if (revision.sensitive) return revision.display_value ?? (revision.configured ? 'Configured (protected)' : 'Not configured');
    if (!revision.configured) return 'Not configured';
    if (revision.value === null || revision.value === undefined) return 'Configured as empty';
    if (typeof revision.value === 'boolean') return revision.value ? 'Enabled' : 'Disabled';
    if (typeof revision.value === 'string') return revision.value;
    if (Array.isArray(revision.value)) return revision.value.map(String).join(', ');
    return JSON.stringify(revision.value);
}

function configurationAuditSubject(
    scope: ConfigurationScope,
    platformTarget: PlatformConfigurationTarget | undefined,
    key: string,
): string {
    return [scope, platformTarget?.tenant_id, platformTarget?.organization_unit_id, key]
        .filter((part) => part !== undefined && part !== null && String(part) !== '')
        .join(':');
}
