import { useState } from 'react';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { IncidentStatus, type RentalIncident } from './incidentEvidence';
import { attachIncidentFile, downloadIncidentFile } from './incidentEvidenceApi';

export function IncidentAttachmentsPanel({ incident, canRecord, busy, setBusy, onUpdated }: {
    incident: RentalIncident;
    canRecord: boolean;
    busy: boolean;
    setBusy: (value: boolean) => void;
    onUpdated: () => void;
}) {
    const [file, setFile] = useState<File | null>(null);
    const [error, setError] = useState<ApiError | null>(null);
    const canUpload = canRecord && incident.status === IncidentStatus.Recorded;
    async function upload() {
        if (!file || busy) return;
        setBusy(true);
        setError(null);
        try {
            await attachIncidentFile(incident, file);
            onUpdated();
        } catch (failure) {
            setError(toApiError(failure));
        } finally {
            setBusy(false);
        }
    }
    return <details className="rounded-lg border p-3">
        <summary className="cursor-pointer font-medium">File proofs ({incident.attachments.length})</summary>
        <div className="mt-3 space-y-3">
            {error && <ErrorAlert error={error} />}
            {incident.attachments.map(attachment => <div key={attachment.id} className="flex items-center justify-between gap-3">
                <span className="break-all text-sm">{attachment.original_filename}</span>
                <Button type="button" variant="secondary" disabled={busy} onClick={async () => {
                    setError(null);
                    try { await downloadIncidentFile(incident.id, attachment); }
                    catch (failure) { setError(toApiError(failure)); }
                }}>Download {attachment.original_filename}</Button>
            </div>)}
            {canUpload ? <>
                <Input label="Add evidence file" type="file" accept="application/pdf,image/jpeg,image/png" disabled={busy}
                    hint="PDF, JPEG or PNG, up to 10 MB. Saved files cannot be replaced or deleted."
                    onChange={event => setFile(event.target.files?.[0] ?? null)} />
                <Button type="button" disabled={busy || !file} onClick={() => void upload()}>Attach file</Button>
            </> : <p className="text-sm text-slate-600">Files are retained with the evidence. Reviewed incidents cannot accept new files.</p>}
        </div>
    </details>;
}
