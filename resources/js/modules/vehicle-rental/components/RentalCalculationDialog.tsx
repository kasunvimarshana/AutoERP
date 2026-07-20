import { useEffect, useState, type FormEvent } from 'react';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Modal } from '@/shared/components/Modal';
import { notifySuccess } from '@/shared/notifications/appToast';
import { createRentalCalculation } from '../vehicleRentalApi';
import type { RentalCalculation, RentalReference } from '../vehicleRentalTypes';
import { RentalAgreementLookup, type RentalLookupOption } from './VehicleRentalLookups';

export function RentalCalculationDialog({
    open,
    onClose,
    onSaved,
}: {
    open: boolean;
    onClose: () => void;
    onSaved: (calculation: RentalCalculation) => void;
}) {
    const [agreement, setAgreement] = useState<RentalReference | null>(null);
    const [periodStart, setPeriodStart] = useState('');
    const [periodEnd, setPeriodEnd] = useState('');
    const [error, setError] = useState<ApiError | null>(null);
    const [submitting, setSubmitting] = useState(false);

    useEffect(() => {
        if (!open) return;
        setAgreement(null);
        setPeriodStart('');
        setPeriodEnd('');
        setError(null);
        setSubmitting(false);
    }, [open]);

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setSubmitting(true);
        setError(null);
        try {
            const saved = await createRentalCalculation(agreement?.id ?? 0, {
                period_start: periodStart,
                period_end: periodEnd,
            });
            notifySuccess('Vehicle Rental calculation snapshot created successfully.');
            onSaved(saved);
            onClose();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <Modal open={open} title="New rental calculation" onClose={onClose} closeDisabled={submitting}>
            <form className="space-y-4" onSubmit={(event) => void submit(event)}>
                <p className="text-sm text-slate-700">The selected agreement determines whether this creates the customer or owner snapshot. Finalized charts remain independently consumable by the other side.</p>
                <ErrorAlert error={error} inline />
                <RentalAgreementLookup value={agreement} required onChange={(value: RentalLookupOption | null) => setAgreement(value)} error={fieldError(error, 'agreement_id')} />
                <div className="grid gap-4 md:grid-cols-2">
                    <Input label="Period start" type="date" required value={periodStart} error={fieldError(error, 'period_start')} onChange={(event) => setPeriodStart(event.target.value)} />
                    <Input label="Period end" type="date" required min={periodStart || undefined} value={periodEnd} error={fieldError(error, 'period_end')} onChange={(event) => setPeriodEnd(event.target.value)} />
                </div>
                <div className="flex justify-end gap-2">
                    <Button type="button" variant="secondary" disabled={submitting} onClick={onClose}>Cancel</Button>
                    <Button type="submit" loading={submitting}>Calculate</Button>
                </div>
            </form>
        </Modal>
    );
}
