import { useState, type FormEvent } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ConfirmDialog } from '@/shared/components/ConfirmDialog';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { useApi } from '@/shared/hooks/useApi';
import { accessApi, type AccessUserDocument } from './accessApi';

const documentTypeOptions = [
    { value: 'identity', label: 'Identity document' },
    { value: 'employment', label: 'Employment document' },
    { value: 'certification', label: 'Certification' },
    { value: 'other', label: 'Other' },
];

export function UserDocumentsPanel({ userId, canManage }: { userId: number; canManage: boolean }) {
    const [page, setPage] = useState(1);
    const documents = useApi(
        (signal) => accessApi.listUserDocuments(userId, { page, per_page: 20 }, signal),
        [userId, page],
        true,
        false,
    );
    const [editing, setEditing] = useState<AccessUserDocument | null>(null);
    const [removeTarget, setRemoveTarget] = useState<AccessUserDocument | null>(null);
    const [name, setName] = useState('');
    const [documentType, setDocumentType] = useState('other');
    const [file, setFile] = useState<File | null>(null);
    const [busy, setBusy] = useState<number | 'form' | null>(null);
    const [error, setError] = useState<ApiError | null>(null);

    const items = documents.data?.data ?? [];

    function startEdit(document: AccessUserDocument) {
        setEditing(document);
        setName(document.name);
        setDocumentType(document.document_type);
        setFile(null);
        setError(null);
    }

    function resetForm() {
        setEditing(null);
        setName('');
        setDocumentType('other');
        setFile(null);
    }

    async function submit(event: FormEvent) {
        event.preventDefault();
        if (!canManage || name.trim() === '' || (!editing && !file)) return;
        setBusy('form');
        setError(null);
        try {
            const payload = new FormData();
            payload.append('name', name.trim());
            payload.append('document_type', documentType);
            if (file) payload.append('file', file);
            if (editing) {
                payload.append('expected_version', String(editing.row_version));
                await accessApi.updateUserDocument(userId, editing.id, payload);
            } else {
                await accessApi.createUserDocument(userId, payload);
            }
            resetForm();
            documents.reload();
        } catch (caught) {
            setError(toApiError(caught));
        } finally {
            setBusy(null);
        }
    }

    async function remove() {
        if (!removeTarget) return;
        setBusy(removeTarget.id);
        setError(null);
        try {
            await accessApi.deleteUserDocument(userId, removeTarget.id, removeTarget.row_version);
            if (editing?.id === removeTarget.id) resetForm();
            setRemoveTarget(null);
            documents.reload();
        } catch (caught) {
            setError(toApiError(caught));
        } finally {
            setBusy(null);
        }
    }

    async function download(document: AccessUserDocument) {
        setBusy(document.id);
        setError(null);
        try {
            await accessApi.downloadUserDocument(userId, document);
        } catch (caught) {
            setError(toApiError(caught));
        } finally {
            setBusy(null);
        }
    }

    if (documents.loading && !documents.data) return <LoadingState label="Loading user documents..." />;

    return (
        <div className="space-y-4">
            <ErrorAlert error={documents.error ?? error} />
            {canManage && (
                <Panel title={editing ? `Edit ${editing.name}` : 'Upload a private user document'}>
                    <form className="grid gap-4 md:grid-cols-2" onSubmit={submit}>
                        <Input
                            label="Document name"
                            value={name}
                            maxLength={160}
                            onChange={(event) => setName(event.target.value)}
                            placeholder="National identity card"
                            required
                        />
                        <Select
                            label="Document type"
                            value={documentType}
                            options={documentTypeOptions}
                            onChange={(event) => setDocumentType(event.target.value)}
                            required
                        />
                        <Input
                            className="md:col-span-2"
                            label={editing ? 'Replacement file (optional)' : 'File'}
                            type="file"
                            accept="application/pdf,image/jpeg,image/png,image/webp"
                            onChange={(event) => setFile(event.target.files?.[0] ?? null)}
                            required={!editing}
                            hint="PDF, JPEG, PNG, or WebP. The server verifies the real type, size, checksum, and private storage location."
                        />
                        <div className="flex gap-2 md:col-span-2 md:justify-end">
                            {editing && <Button variant="secondary" onClick={resetForm}>Cancel</Button>}
                            <Button type="submit" loading={busy === 'form'}>
                                {editing ? 'Save Document' : 'Upload Document'}
                            </Button>
                        </div>
                    </form>
                </Panel>
            )}

            <div className="space-y-3">
                {items.map((document) => (
                    <Panel key={document.id}>
                        <div className="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                            <div>
                                <p className="font-semibold text-slate-900">{document.name}</p>
                                <p className="mt-1 text-sm text-slate-500">
                                    {documentTypeLabel(document.document_type)} · {document.original_filename} · {formatBytes(document.size_bytes)}
                                </p>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                <Button variant="secondary" loading={busy === document.id} onClick={() => void download(document)}>Download</Button>
                                {canManage && (
                                    <>
                                        <Button variant="secondary" onClick={() => startEdit(document)}>Edit</Button>
                                        <Button variant="danger" onClick={() => setRemoveTarget(document)}>Remove</Button>
                                    </>
                                )}
                            </div>
                        </div>
                    </Panel>
                ))}
                {items.length === 0 && <Panel><p className="text-sm text-slate-500">No private user documents have been uploaded.</p></Panel>}
            </div>
            <Pagination meta={documents.data?.meta} onPageChange={setPage} />
            <ConfirmDialog
                open={removeTarget !== null}
                title="Remove user document?"
                message="The document record will be archived and its private stored file will be removed through durable cleanup."
                confirmLabel="Remove Document"
                loading={removeTarget !== null && busy === removeTarget.id}
                onCancel={() => setRemoveTarget(null)}
                onConfirm={() => void remove()}
            />
        </div>
    );
}

function documentTypeLabel(value: string): string {
    return documentTypeOptions.find((option) => option.value === value)?.label ?? 'Other';
}

function formatBytes(value: number): string {
    if (value < 1024) return `${value} B`;
    if (value < 1024 * 1024) return `${(value / 1024).toFixed(1)} KB`;
    return `${(value / (1024 * 1024)).toFixed(1)} MB`;
}
