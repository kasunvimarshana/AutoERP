import { useState, type FormEvent } from 'react';
import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { ApiError, toApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { Drawer } from '@/shared/components/Drawer';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { useApi } from '@/shared/hooks/useApi';
import {
    organizationUnitApi,
    type OrganizationUnitDocument,
    type OrganizationUnitSummary,
} from '../organizationUnitApi';
import { organizationUnitPermissions } from '../organizationUnitPermissions';
import { OrganizationUnitLegalProfilePanel } from './OrganizationUnitLegalProfilePanel';

export function OrganizationUnitDetailDrawer({
    unit,
    onClose,
    onUpdated,
}: {
    unit: OrganizationUnitSummary | null;
    onClose: () => void;
    onUpdated: (unit: OrganizationUnitSummary) => void;
}) {
    const auth = useAuth();
    const { confirm, confirmDialog } = useConfirmDialog();
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const [busy, setBusy] = useState(false);
    const documents = useApi(
        (signal) => organizationUnitApi.listDocuments(unit?.id ?? 0, { page: 1, per_page: 100 }, signal),
        [unit?.id],
        Boolean(unit && hasPermission(auth, organizationUnitPermissions.documentsView)),
    );
    const canUpdate = hasPermission(auth, organizationUnitPermissions.update);
    const canManageDocuments = hasPermission(auth, organizationUnitPermissions.documentsManage);

    if (!unit) return null;

    const replaceLogo = async (file: File) => {
        setBusy(true);
        setActionError(null);
        try {
            onUpdated(await organizationUnitApi.replaceLogo(unit, file));
        } catch (caught: unknown) {
            setActionError(toApiError(caught));
        } finally {
            setBusy(false);
        }
    };

    const removeLogo = async () => {
        if (!await confirm({
            title: 'Remove organization-unit logo',
            message: `Remove the logo from “${unit.name}”? Tenant branding will be used as the fallback.`,
            confirmLabel: 'Remove logo',
        })) return;
        setBusy(true);
        setActionError(null);
        try {
            onUpdated(await organizationUnitApi.removeLogo(unit));
        } catch (caught: unknown) {
            setActionError(toApiError(caught));
        } finally {
            setBusy(false);
        }
    };

    return (
        <Drawer open title={unit.name} onClose={onClose} closeDisabled={busy}>
            <div className="space-y-5">
                <ErrorAlert error={actionError} />
                <Panel>
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{unit.code}</p>
                            <p className="mt-1 text-sm text-slate-600">{unit.path}</p>
                        </div>
                        <StatusBadge status={unit.lifecycle_status} />
                    </div>
                    <dl className="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                        <Detail label="Type" value={unit.type?.name ?? 'Not assigned'} />
                        <Detail label="Hierarchy level" value={String(unit.depth)} />
                        <Detail label="Parent" value={unit.parent?.name ?? 'Tenant root'} />
                        <Detail label="Current version" value={String(unit.row_version)} />
                    </dl>
                    {unit.description && <p className="mt-5 border-t border-slate-100 pt-4 text-sm text-slate-700">{unit.description}</p>}
                </Panel>

                <OrganizationUnitLegalProfilePanel
                    unit={unit}
                    canManage={canUpdate && unit.lifecycle_status !== 'retired'}
                />

                {canUpdate && unit.lifecycle_status !== 'retired' && (
                    <Panel>
                        <h3 className="font-semibold text-slate-900">Document branding</h3>
                        <p className="mt-1 text-sm text-slate-600">Upload a private JPG, PNG, or WebP logo. The tenant logo remains the fallback.</p>
                        <div className="mt-4 flex flex-wrap gap-2">
                            <label className="inline-flex min-h-10 cursor-pointer items-center rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">
                                {unit.has_logo ? 'Replace logo' : 'Upload logo'}
                                <input
                                    type="file"
                                    className="sr-only"
                                    accept="image/jpeg,image/png,image/webp"
                                    disabled={busy}
                                    onChange={(event) => {
                                        const file = event.target.files?.[0];
                                        event.target.value = '';
                                        if (file) void replaceLogo(file);
                                    }}
                                />
                            </label>
                            {unit.has_logo && <Button variant="danger" disabled={busy} onClick={() => void removeLogo()}>Remove logo</Button>}
                        </div>
                    </Panel>
                )}

                {hasPermission(auth, organizationUnitPermissions.documentsView) && (
                    <OrganizationUnitDocumentsPanel
                        unit={unit}
                        documents={documents.data?.data ?? []}
                        loading={documents.loading}
                        error={documents.error}
                        canManage={canManageDocuments && unit.lifecycle_status !== 'retired'}
                        onReload={documents.reload}
                    />
                )}
            </div>
            {confirmDialog}
        </Drawer>
    );
}

function OrganizationUnitDocumentsPanel({
    unit,
    documents,
    loading,
    error,
    canManage,
    onReload,
}: {
    unit: OrganizationUnitSummary;
    documents: OrganizationUnitDocument[];
    loading: boolean;
    error: ApiError | null;
    canManage: boolean;
    onReload: () => void;
}) {
    const { confirm, confirmDialog } = useConfirmDialog();
    const [name, setName] = useState('');
    const [documentType, setDocumentType] = useState('');
    const [file, setFile] = useState<File | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const [deletingId, setDeletingId] = useState<number | null>(null);
    const columns: DataColumn<OrganizationUnitDocument>[] = [
        { key: 'name', header: 'Document', render: (row) => <div><p className="font-semibold text-slate-900">{row.name}</p><p className="text-xs text-slate-500">{row.original_filename}</p></div> },
        { key: 'type', header: 'Type', render: (row) => row.document_type ?? row.mime_type },
        { key: 'size', header: 'Size', render: (row) => formatBytes(row.size_bytes) },
        {
            key: 'actions',
            header: 'Actions',
            className: 'text-right',
            render: (row) => (
                <div className="flex justify-end gap-2">
                    <Button variant="secondary" className="min-h-8 px-3 py-1 text-xs" onClick={() => void organizationUnitApi.downloadDocument(unit.id, row)}>Download</Button>
                    {canManage && (
                        <Button
                            variant="danger"
                            className="min-h-8 px-3 py-1 text-xs"
                            loading={deletingId === row.id}
                            onClick={() => void deleteDocument(row)}
                        >
                            Remove
                        </Button>
                    )}
                </div>
            ),
        },
    ];

    const upload = async (event: FormEvent) => {
        event.preventDefault();
        if (!name.trim() || !file) {
            setActionError(new ApiError('Document name and file are required.', 422, 'INVALID_DOCUMENT', 'validation'));
            return;
        }
        setSubmitting(true);
        setActionError(null);
        try {
            await organizationUnitApi.createDocument(unit.id, {
                name: name.trim(),
                document_type: documentType.trim() || null,
                file,
            });
            setName('');
            setDocumentType('');
            setFile(null);
            onReload();
        } catch (caught: unknown) {
            setActionError(toApiError(caught));
        } finally {
            setSubmitting(false);
        }
    };

    const deleteDocument = async (document: OrganizationUnitDocument) => {
        if (!await confirm({
            title: 'Remove organization-unit document',
            message: `Remove “${document.name}”? The private stored object will be queued for cleanup.`,
            confirmLabel: 'Remove document',
        })) return;
        setDeletingId(document.id);
        setActionError(null);
        try {
            await organizationUnitApi.deleteDocument(unit.id, document);
            onReload();
        } catch (caught: unknown) {
            setActionError(toApiError(caught));
        } finally {
            setDeletingId(null);
        }
    };

    return (
        <Panel>
            <h3 className="font-semibold text-slate-900">Private documents</h3>
            <p className="mt-1 text-sm text-slate-600">Files are stored under this tenant and organization unit. Raw paths and storage keys are never exposed.</p>
            <ErrorAlert error={actionError ?? error} />
            {canManage && (
                <form className="mt-4 grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4" onSubmit={(event) => void upload(event)}>
                    <div className="grid gap-3 sm:grid-cols-2">
                        <Input label="Document name" value={name} maxLength={255} onChange={(event) => setName(event.target.value)} required />
                        <Input label="Document type" value={documentType} maxLength={100} hint="Example: registration, tax certificate, lease." onChange={(event) => setDocumentType(event.target.value)} />
                    </div>
                    <Input
                        label="File"
                        type="file"
                        accept=".pdf,.jpg,.jpeg,.png,.webp,.txt,.csv,.docx,.xlsx"
                        onChange={(event) => setFile(event.target.files?.[0] ?? null)}
                        required
                    />
                    <div className="flex justify-end"><Button type="submit" loading={submitting}>Upload document</Button></div>
                </form>
            )}
            <div className="mt-4">
                {loading ? <LoadingState label="Loading documents…" /> : (
                    <DataTable rows={documents} columns={columns} rowKey={(row) => row.id} emptyMessage="No documents have been uploaded." />
                )}
            </div>
            {confirmDialog}
        </Panel>
    );
}

function Detail({ label, value }: { label: string; value: string }) {
    return <div><dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</dt><dd className="mt-1 text-slate-800">{value}</dd></div>;
}

function formatBytes(bytes: number): string {
    if (!Number.isFinite(bytes) || bytes < 1024) return `${Math.max(0, bytes)} B`;
    const units = ['KB', 'MB', 'GB'];
    let value = bytes / 1024;
    let index = 0;
    while (value >= 1024 && index < units.length - 1) {
        value /= 1024;
        index += 1;
    }
    return `${value.toFixed(value >= 10 ? 0 : 1)} ${units[index]}`;
}
