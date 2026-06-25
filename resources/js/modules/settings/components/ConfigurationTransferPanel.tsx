import { useState, type ChangeEvent } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Modal } from '@/shared/components/Modal';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { Textarea } from '@/shared/components/Textarea';
import {
    applyGlobalConfigurationImport,
    exportGlobalConfiguration,
    previewGlobalConfigurationImport,
} from '../settingsApi';
import type {
    ConfigurationImportPreview,
    ConfigurationImportPreviewEntry,
    ConfigurationTransferDocument,
} from '../settingsTypes';

interface Props {
    canManage: boolean;
    onApplied: (message: string) => void;
}

export function ConfigurationTransferPanel({ canManage, onApplied }: Props) {
    const { confirm, confirmDialog } = useConfirmDialog();
    const [open, setOpen] = useState(false);
    const [document, setDocument] = useState<ConfigurationTransferDocument | null>(null);
    const [preview, setPreview] = useState<ConfigurationImportPreview | null>(null);
    const [reason, setReason] = useState('');
    const [working, setWorking] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    const columns: DataColumn<ConfigurationImportPreviewEntry>[] = [
        { key: 'setting', header: 'Setting', render: (entry) => <div><p className="font-semibold text-slate-900">{entry.label}</p><p className="text-xs text-slate-500">{entry.owner}</p></div> },
        { key: 'action', header: 'Action', render: (entry) => <StatusBadge status={entry.action} /> },
        { key: 'current', header: 'Current global value', render: (entry) => formatValue(entry.current_value) },
        { key: 'import', header: 'Imported value', render: (entry) => formatValue(entry.import_value) },
    ];

    async function exportConfiguration() {
        setWorking(true);
        setError(null);
        try {
            const payload = await exportGlobalConfiguration();
            const blob = new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const anchor = window.document.createElement('a');
            anchor.href = url;
            anchor.download = `autoerp-global-configuration-v${payload.schema_version}.json`;
            anchor.click();
            URL.revokeObjectURL(url);
        } catch (requestError: unknown) {
            setError(toApiError(requestError));
        } finally {
            setWorking(false);
        }
    }

    async function readFile(event: ChangeEvent<HTMLInputElement>) {
        const file = event.target.files?.[0];
        event.target.value = '';
        if (!file) return;
        setWorking(true);
        setError(null);
        setDocument(null);
        setPreview(null);
        setReason('');
        try {
            const parsed: unknown = JSON.parse(await file.text());
            if (!isTransferDocument(parsed)) {
                throw new Error('Select an AutoERP global configuration export file.');
            }
            const nextPreview = await previewGlobalConfigurationImport(parsed);
            setDocument(parsed);
            setPreview(nextPreview);
        } catch (requestError: unknown) {
            setError(requestError instanceof SyntaxError || requestError instanceof Error && !(requestError as { response?: unknown }).response
                ? toApiError({ message: requestError.message })
                : toApiError(requestError));
        } finally {
            setWorking(false);
        }
    }

    async function applyImport() {
        if (!document || !preview || reason.trim().length < 10) return;
        const confirmed = await confirm({
            title: 'Apply reviewed global configuration import',
            message: (
                <div className="space-y-2">
                    <p>This operation will create <strong>{preview.summary.create}</strong> and update <strong>{preview.summary.update}</strong> global default(s). {preview.summary.unchanged} value(s) are already identical.</p>
                    <p><strong>Sensitive settings are never included.</strong> Tenant and organization-unit overrides remain unchanged.</p>
                    <p>Audit reason: “{reason.trim()}”.</p>
                </div>
            ),
            confirmLabel: 'Apply import',
            danger: preview.summary.update > 0,
        });
        if (!confirmed) return;

        setWorking(true);
        setError(null);
        try {
            const result = await applyGlobalConfigurationImport(document, preview.confirmation_digest, reason.trim());
            close();
            onApplied(`Global configuration import applied: ${result.created} created, ${result.updated} updated, ${result.unchanged} unchanged.`);
        } catch (requestError: unknown) {
            setError(toApiError(requestError));
            if (toApiError(requestError).status === 409) setPreview(null);
        } finally {
            setWorking(false);
        }
    }

    function close() {
        if (working) return;
        setOpen(false);
        setDocument(null);
        setPreview(null);
        setReason('');
        setError(null);
    }

    return (
        <>
            <div className="flex flex-wrap gap-2">
                <Button variant="secondary" disabled={working} onClick={() => void exportConfiguration()}>Export approved defaults</Button>
                {canManage ? <Button variant="secondary" disabled={working} onClick={() => { setOpen(true); setError(null); }}>Import reviewed defaults</Button> : null}
            </div>
            <ErrorAlert error={error} title="Configuration transfer failed" />
            <Modal open={open} title="Import global configuration defaults" onClose={close} closeDisabled={working}>
                <div className="space-y-4">
                    <div className="rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900">
                        Select an AutoERP export file. The backend validates registered keys and values, excludes protected settings, and requires a reviewed preview before applying changes.
                    </div>
                    <label className="block text-sm font-medium text-slate-700">
                        Configuration export file
                        <input className="mt-1 block w-full rounded-lg border border-slate-300 bg-white p-2" type="file" accept="application/json,.json" disabled={working} onChange={(event) => void readFile(event)} />
                    </label>
                    <ErrorAlert error={error} title="Configuration import could not be prepared" />
                    {preview ? (
                        <>
                            <div className="grid gap-3 sm:grid-cols-4">
                                <Metric label="Total" value={preview.summary.total} />
                                <Metric label="Create" value={preview.summary.create} />
                                <Metric label="Update" value={preview.summary.update} />
                                <Metric label="Unchanged" value={preview.summary.unchanged} />
                            </div>
                            <DataTable
                                rows={preview.entries}
                                columns={columns}
                                rowKey={(entry) => entry.key}
                                emptyMessage="The export contains no transferable global settings."
                                mobileSummary={(entry) => entry.label}
                                mobileDetails={(entry) => <div><p>{entry.owner}</p><p>{entry.action}</p></div>}
                                rowBadge={(entry) => <StatusBadge status={entry.action} />}
                            />
                            <Textarea
                                label="Import reason"
                                value={reason}
                                onChange={(event) => setReason(event.target.value)}
                                placeholder="Explain the approved environment promotion or recovery action."
                                hint="At least 10 characters. The reason is retained in the platform audit trail."
                                disabled={working}
                            />
                            <div className="flex justify-end gap-2">
                                <Button variant="secondary" disabled={working} onClick={close}>Cancel</Button>
                                <Button loading={working} disabled={reason.trim().length < 10 || preview.summary.create + preview.summary.update === 0} onClick={() => void applyImport()}>Apply reviewed import</Button>
                            </div>
                        </>
                    ) : null}
                </div>
            </Modal>
            {confirmDialog}
        </>
    );
}

function Metric({ label, value }: { label: string; value: number }) {
    return <div className="rounded-lg bg-slate-50 p-3"><p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</p><p className="mt-1 text-xl font-semibold text-slate-900">{value}</p></div>;
}

function formatValue(value: unknown): string {
    if (value === null || value === undefined) return 'Not configured';
    if (typeof value === 'boolean') return value ? 'Enabled' : 'Disabled';
    if (typeof value === 'string' || typeof value === 'number') return String(value);
    return JSON.stringify(value);
}

function isTransferDocument(value: unknown): value is ConfigurationTransferDocument {
    if (!value || typeof value !== 'object') return false;
    const document = value as Partial<ConfigurationTransferDocument>;
    return Number.isInteger(document.schema_version)
        && document.scope === 'global'
        && Array.isArray(document.entries);
}
