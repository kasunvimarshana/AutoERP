import { useState, type FormEvent } from 'react';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Modal } from '@/shared/components/Modal';
import { notifySuccess } from '@/shared/notifications/appToast';
import { createRentalCalculation } from '../vehicleRentalApi';
import type { RentalCalculation, RentalCalculationSide, RentalReference } from '../vehicleRentalTypes';
import { RentalAgreementLookup, type RentalLookupOption } from './VehicleRentalLookups';

interface RentalCalculationDialogProps {
    open: boolean;
    side?: RentalCalculationSide;
    onClose: () => void;
    onSaved: (calculation: RentalCalculation) => void;
}

export function RentalCalculationDialog(props: RentalCalculationDialogProps) {
    return <RentalCalculationDialogForm key={`${props.open ? 'open' : 'closed'}:${props.side ?? 'all'}`} {...props} />;
}

function RentalCalculationDialogForm({
    open,
    side,
    onClose,
    onSaved,
}: RentalCalculationDialogProps) {
    const [agreement, setAgreement] = useState<RentalReference | null>(null);
    const [periodStart, setPeriodStart] = useState('');
    const [periodEnd, setPeriodEnd] = useState('');
    const [error, setError] = useState<ApiError | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const customerSide = side === 'customer';
    const title = customerSide ? 'Prepare customer billing period' : side === 'owner' ? 'Prepare owner settlement period' : 'New rental calculation';
    const actionLabel = customerSide ? 'Prepare billing' : side === 'owner' ? 'Prepare settlement' : 'Calculate';

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setSubmitting(true);
        setError(null);
        try {
            const saved = await createRentalCalculation(agreement?.id ?? 0, {
                period_start: periodStart,
                period_end: periodEnd,
            });
            notifySuccess(customerSide
                ? 'Customer billing period prepared successfully.'
                : side === 'owner'
                    ? 'Owner settlement period prepared successfully.'
                    : 'Vehicle Rental calculation snapshot created successfully.');
            onSaved(saved);
            onClose();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <Modal open={open} title={title} onClose={onClose} closeDisabled={submitting}>
            <form className="space-y-4" onSubmit={(event) => void submit(event)}>
                <p className="text-sm text-slate-700">
                    Finalized running charts are consumed only by this financial side. The other side remains independently available.
                </p>
                <ErrorAlert error={error} inline />
                <RentalAgreementLookup
                    value={agreement}
                    kind={side === 'customer' ? 'customer' : side === 'owner' ? 'owner' : undefined}
                    purpose="calculation"
                    required
                    onChange={(value: RentalLookupOption | null) => setAgreement(value)}
                    error={fieldError(error, 'agreement_id')}
                />
                <div className="grid gap-4 md:grid-cols-2">
                    <Input label="Period start" type="date" required value={periodStart} error={fieldError(error, 'period_start')} onChange={(event) => setPeriodStart(event.target.value)} />
                    <Input label="Period end" type="date" required min={periodStart || undefined} value={periodEnd} error={fieldError(error, 'period_end')} onChange={(event) => setPeriodEnd(event.target.value)} />
                </div>
                <div className="flex justify-end gap-2">
                    <Button type="button" variant="secondary" disabled={submitting} onClick={onClose}>Cancel</Button>
                    <Button type="submit" loading={submitting}>{actionLabel}</Button>
                </div>
            </form>
        </Modal>
    );
}
