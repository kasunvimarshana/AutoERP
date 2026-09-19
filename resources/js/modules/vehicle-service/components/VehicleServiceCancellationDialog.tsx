import { useState, type FormEvent } from 'react';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Modal } from '@/shared/components/Modal';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { Textarea } from '@/shared/components/Textarea';
import { useApi } from '@/shared/hooks/useApi';
import { cancelVehicleServiceJob, getVehicleServiceCancellationPreview } from '../vehicleServiceApi';
import type { VehicleServiceJob } from '../vehicleServiceTypes';

export function VehicleServiceCancellationDialog({ job, onClose, onCancelled }: {
    job: VehicleServiceJob;
    onClose: () => void;
    onCancelled: (cancelled: VehicleServiceJob) => Promise<void>;
}) {
    const preview = useApi((signal) => getVehicleServiceCancellationPreview(job.id, signal), [job.id]);
    const [reason, setReason] = useState('');
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        if (busy || !preview.data?.can_cancel || !reason.trim()) return;
        setBusy(true);
        setError(null);
        try {
            const cancelled = await cancelVehicleServiceJob(job.id, preview.data.row_version, reason.trim());
            await onCancelled(cancelled);
        } catch (failure) {
            setError(toApiError(failure));
            // A concurrent edit or document change requires a fresh confirmation.
            preview.reload();
        } finally {
            setBusy(false);
        }
    };

    return (
        <Modal open title={`Cancel ${job.job_number}`} onClose={onClose} closeDisabled={busy}>
            <form onSubmit={submit} className="space-y-4">
                <ErrorAlert error={error ?? preview.error} inline />
                {preview.loading && <LoadingState />}
                {preview.data && !preview.loading && <>
                    {preview.data.blockers.length > 0 && <div role="alert" className="rounded-lg bg-amber-50 p-3 text-sm text-amber-900">
                        {preview.data.blockers.map((blocker) => <p key={blocker}>{blocker}</p>)}
                    </div>}
                    <p className="text-sm text-slate-700">This permanently cancels the job. Original records remain in the audit history.</p>
                    {preview.data.stock_returns.length > 0 ? <section className="space-y-2 text-sm">
                        <h3 className="font-semibold">Stock returned to inventory</h3>
                        <ul className="list-disc space-y-1 pl-5">
                            {preview.data.stock_returns.map((item, index) => <li key={index}>
                                {item.description}: {item.quantity} {item.uom} — {[item.warehouse, item.location].filter(Boolean).join(' / ')}
                            </li>)}
                        </ul>
                        <p>Inventory value reversed: <MoneyDisplay value={preview.data.inventory_value} /></p>
                        <p className="text-amber-800">Confirm only after all issued items are physically returned and suitable for restocking.</p>
                    </section> : <p className="text-sm text-slate-600">No issued stock to return.</p>}
                    <p className="text-sm">Commission removed from payable totals: <MoneyDisplay value={preview.data.commission_amount} /></p>
                    {preview.data.can_cancel && <Textarea label="Cancellation reason" value={reason} onChange={(event) => setReason(event.target.value)} required disabled={busy} />}
                </>}
                <div className="flex justify-end gap-2">
                    <Button type="button" variant="secondary" onClick={onClose} disabled={busy}>Keep job</Button>
                    <Button type="submit" variant="danger" loading={busy} disabled={preview.loading || !preview.data?.can_cancel || !reason.trim()}>Confirm cancellation</Button>
                </div>
            </form>
        </Modal>
    );
}
