import { useState } from 'react';
import { fieldError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { Textarea } from '@/shared/components/Textarea';
import type { VehicleDocumentPayload } from '../vehicleTypes';

const documentTypes = ['registration', 'insurance', 'emission_test', 'revenue_license', 'fitness_certificate', 'lease_document', 'ownership_document', 'warranty', 'other'];
const statuses = ['pending', 'active', 'expired', 'revoked'];
const emptyDocument: VehicleDocumentPayload = { document_type: 'registration', document_number: '', issued_date: '', expiry_date: '', file_path: '', status: 'pending', notes: '' };

export function VehicleDocumentDraftEditor({
    documents,
    onChange,
    error,
}: {
    documents: VehicleDocumentPayload[];
    onChange: (documents: VehicleDocumentPayload[]) => void;
    error: ApiError | null;
}) {
    const [draft, setDraft] = useState<VehicleDocumentPayload>(emptyDocument);

    const addDocument = () => {
        onChange([...documents, draft]);
        setDraft(emptyDocument);
    };

    return (
        <div className="space-y-4">
            <div className="grid gap-3 md:grid-cols-3">
                <Select label="Document Type" value={draft.document_type} options={documentTypes.map((value) => ({ value, label: value.replaceAll('_', ' ') }))} onChange={(event) => setDraft({ ...draft, document_type: event.target.value as VehicleDocumentPayload['document_type'] })} error={fieldError(error, 'documents.0.document_type')} />
                <Input label="Reference Number" value={draft.document_number ?? ''} onChange={(event) => setDraft({ ...draft, document_number: event.target.value })} error={fieldError(error, 'documents.0.document_number')} />
                <Input label="File Path" value={draft.file_path ?? ''} onChange={(event) => setDraft({ ...draft, file_path: event.target.value })} error={fieldError(error, 'documents.0.file_path')} />
                <Input label="Issue Date" type="date" value={draft.issued_date ?? ''} onChange={(event) => setDraft({ ...draft, issued_date: event.target.value })} error={fieldError(error, 'documents.0.issued_date')} />
                <Input label="Expiry Date" type="date" value={draft.expiry_date ?? ''} onChange={(event) => setDraft({ ...draft, expiry_date: event.target.value })} error={fieldError(error, 'documents.0.expiry_date')} />
                <Select label="Status" value={draft.status} options={statuses.map((value) => ({ value, label: value.replaceAll('_', ' ') }))} onChange={(event) => setDraft({ ...draft, status: event.target.value as VehicleDocumentPayload['status'] })} error={fieldError(error, 'documents.0.status')} />
                <div className="md:col-span-3">
                    <Textarea label="Remarks" value={draft.notes ?? ''} onChange={(event) => setDraft({ ...draft, notes: event.target.value })} error={fieldError(error, 'documents.0.notes')} />
                </div>
            </div>
            <Button type="button" variant="secondary" onClick={addDocument}>Add Document</Button>
            <DocumentCards documents={documents} onRemove={(index) => onChange(documents.filter((_, itemIndex) => itemIndex !== index))} />
        </div>
    );
}

function DocumentCards({ documents, onRemove }: { documents: VehicleDocumentPayload[]; onRemove: (index: number) => void }) {
    if (documents.length === 0) {
        return <p className="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">No documents added.</p>;
    }

    return (
        <div className="grid gap-3 md:grid-cols-2">
            {documents.map((document, index) => (
                <article key={`${document.document_type}-${index}`} className="rounded-lg border border-slate-200 bg-white p-4">
                    <div className="flex items-start justify-between gap-3">
                        <div>
                            <p className="font-semibold text-slate-900">{document.document_type.replaceAll('_', ' ')}</p>
                            <p className="text-sm text-slate-500">{document.document_number || 'No reference number'}</p>
                        </div>
                        <StatusBadge status={document.status} />
                    </div>
                    <dl className="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div><dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">Issue Date</dt><dd>{document.issued_date || '-'}</dd></div>
                        <div><dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">Expiry Date</dt><dd>{document.expiry_date || '-'}</dd></div>
                        <div className="sm:col-span-2"><dt className="text-xs font-semibold uppercase tracking-wide text-slate-500">File</dt><dd className="break-all">{document.file_path || '-'}</dd></div>
                    </dl>
                    <div className="mt-4 flex justify-end">
                        <Button type="button" variant="ghost" onClick={() => onRemove(index)}>Remove</Button>
                    </div>
                </article>
            ))}
        </div>
    );
}
