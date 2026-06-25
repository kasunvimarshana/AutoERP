import { useState, type FormEvent } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ConfirmDialog } from '@/shared/components/ConfirmDialog';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { Panel } from '@/shared/components/Panel';
import { useApi } from '@/shared/hooks/useApi';
import {
    createTenantDocument,
    deleteTenantDocument,
    downloadTenantDocument,
    listTenantDocuments,
    updateTenantDocument,
} from '../tenantApi';
import type { TenantDocument } from '../tenantTypes';

export function TenantDocumentsPanel({ canManage }: { canManage: boolean }) {
    const [page, setPage] = useState(1);
    const documents = useApi((signal) => listTenantDocuments({ page, per_page: 20 }, signal), [page], true, false);
    const items = documents.data?.data ?? [];
    const [editing, setEditing] = useState<TenantDocument | null>(null);
    const [name, setName] = useState('');
    const [type, setType] = useState('');
    const [file, setFile] = useState<File | null>(null);
    const [busy, setBusy] = useState<number | 'form' | null>(null);
    const [error, setError] = useState<ApiError | null>(null);
    const [removeTarget, setRemoveTarget] = useState<TenantDocument | null>(null);

    function edit(document: TenantDocument) {
        setEditing(document); setName(document.name); setType(document.document_type ?? ''); setFile(null); setError(null);
    }
    function reset() { setEditing(null); setName(''); setType(''); setFile(null); }

    async function submit(event: FormEvent) {
        event.preventDefault();
        if (!canManage || (!editing && !file)) return;
        setBusy('form'); setError(null);
        try {
            const payload = new FormData();
            payload.append('name', name.trim());
            payload.append('document_type', type.trim());
            if (file) payload.append('file', file);
            if (editing) {
                payload.append('expected_version', String(editing.row_version));
                await updateTenantDocument(editing.id, payload);
                documents.reload();
            } else {
                await createTenantDocument(payload);
                documents.reload();
            }
            reset();
        } catch (requestError: unknown) { setError(toApiError(requestError)); }
        finally { setBusy(null); }
    }

    async function remove() {
        if (!removeTarget) return;
        setBusy(removeTarget.id); setError(null);
        try {
            await deleteTenantDocument(removeTarget);
            documents.reload();
            if (editing?.id === removeTarget.id) reset();
            setRemoveTarget(null);
        } catch (requestError: unknown) { setError(toApiError(requestError)); }
        finally { setBusy(null); }
    }

    async function download(document: TenantDocument) {
        setBusy(document.id); setError(null);
        try { await downloadTenantDocument(document); }
        catch (requestError: unknown) { setError(toApiError(requestError)); }
        finally { setBusy(null); }
    }

    if (documents.loading && !documents.data) return <LoadingState label="Loading tenant documents..." />;

    return (
        <div className="space-y-5">
            <ErrorAlert error={documents.error ?? error} />
            {canManage && <Panel title={editing ? `Edit ${editing.name}` : 'Upload a private tenant document'}>
                <form className="grid gap-4 md:grid-cols-2" onSubmit={submit}>
                    <Input label="Document name" value={name} onChange={(event) => setName(event.target.value)} placeholder="VAT certificate" required />
                    <Input label="Document type" value={type} onChange={(event) => setType(event.target.value)} placeholder="Tax registration" />
                    <Input className="md:col-span-2" label={editing ? 'Replacement file (optional)' : 'File'} type="file" accept="application/pdf,image/jpeg,image/png" onChange={(event) => setFile(event.target.files?.[0] ?? null)} required={!editing} hint="PDF, JPEG, or PNG. The server verifies file type, size, checksum, and private storage path." />
                    <div className="flex gap-2 md:col-span-2 md:justify-end">
                        {editing && <Button variant="secondary" onClick={reset}>Cancel</Button>}
                        <Button type="submit" loading={busy === 'form'}>{editing ? 'Save document' : 'Upload document'}</Button>
                    </div>
                </form>
            </Panel>}
            <div className="space-y-3">
                {items.map((document) => <Panel key={document.id}>
                    <div className="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                        <div><div className="flex flex-wrap items-center gap-2"><p className="font-semibold text-slate-900">{document.name}</p><StatusBadge status={document.scanned_at ? 'scanned' : 'pending_scan'} /></div><p className="mt-1 text-sm text-slate-500">{document.document_type ?? 'General document'} · {document.original_filename} · {formatBytes(document.size_bytes)}</p>{document.scanned_at ? <p className="mt-1 text-xs text-slate-500">Scanned by {document.scan_engine ?? 'configured scanner'}</p> : null}</div>
                        <div className="flex flex-wrap gap-2"><Button variant="secondary" loading={busy === document.id} onClick={() => void download(document)}>Download</Button>{canManage && <><Button variant="secondary" onClick={() => edit(document)}>Edit</Button><Button variant="danger" onClick={() => setRemoveTarget(document)}>Remove</Button></>}</div>
                    </div>
                </Panel>)}
                {items.length === 0 && <Panel><p className="text-sm text-slate-500">No private tenant documents have been uploaded.</p></Panel>}
            </div>
            <Pagination meta={documents.data?.meta} onPageChange={setPage} />
            <ConfirmDialog open={removeTarget !== null} title="Remove document?" message="The database record and private stored file will be removed." confirmLabel="Remove document" loading={removeTarget !== null && busy === removeTarget.id} onCancel={() => setRemoveTarget(null)} onConfirm={() => void remove()} />
        </div>
    );
}

function formatBytes(value: number): string {
    if (value < 1024) return `${value} B`;
    if (value < 1024 * 1024) return `${(value / 1024).toFixed(1)} KB`;
    return `${(value / (1024 * 1024)).toFixed(1)} MB`;
}
