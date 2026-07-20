import { useState, type FormEvent } from 'react';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Modal } from '@/shared/components/Modal';
import { notifySuccess } from '@/shared/notifications/appToast';
import { createRentalCalculation } from '../vehicleRentalApi';
import type { RentalCalculation, RentalCalculationSide } from '../vehicleRentalTypes';
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
    const [agreement, setAgreement] = useState<RentalLookupOption | null>(null);
    const [periodMonth, setPeriodMonth] = useState('');
    const [periodStart, setPeriodStart] = useState('');
    const [periodEnd, setPeriodEnd] = useState('');
    const [error, setError] = useState<ApiError | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const customerSide = side === 'customer';
    const monthly = agreement?.billingBasis === 'monthly';
    const title = customerSide ? 'Prepare customer billing period' : side === 'owner' ? 'Prepare owner settlement period' : 'New rental calculation';
    const actionLabel = customerSide ? 'Prepare billing' : side === 'owner' ? 'Prepare settlement' : 'Calculate';

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setSubmitting(true);
        setError(null);
        try {
            const period = monthly
                ? completeMonth(periodMonth)
                : { start: periodStart, end: periodEnd };
            const saved = await createRentalCalculation(agreement?.id ?? 0, {
                period_start: period.start,
                period_end: period.end,
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

    const selectAgreement = (value: RentalLookupOption | null) => {
        setAgreement(value);
        setPeriodMonth('');
        setPeriodStart('');
        setPeriodEnd('');
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
                    onChange={selectAgreement}
                    error={fieldError(error, 'agreement_id')}
                />
                {monthly ? (
                    <div className="space-y-2">
                        <Input
                            label="Billing month"
                            type="month"
                            required
                            min={monthValue(agreement?.startsOn)}
                            max={monthValue(agreement?.endsOn)}
                            value={periodMonth}
                            error={fieldError(error, 'period_start') ?? fieldError(error, 'period_end')}
                            onChange={(event) => setPeriodMonth(event.target.value)}
                        />
                        <p className="text-sm text-slate-600">Monthly agreements are prepared for one complete calendar month. Partial-month proration is not configured.</p>
                    </div>
                ) : (
                    <div className="grid gap-4 md:grid-cols-2">
                        <Input
                            label="Period start"
                            type="date"
                            required
                            min={agreement?.startsOn ?? undefined}
                            max={agreement?.endsOn ?? undefined}
                            value={periodStart}
                            error={fieldError(error, 'period_start')}
                            onChange={(event) => setPeriodStart(event.target.value)}
                        />
                        <Input
                            label="Period end"
                            type="date"
                            required
                            min={periodStart || agreement?.startsOn || undefined}
                            max={agreement?.endsOn ?? undefined}
                            value={periodEnd}
                            error={fieldError(error, 'period_end')}
                            onChange={(event) => setPeriodEnd(event.target.value)}
                        />
                    </div>
                )}
                <div className="flex justify-end gap-2">
                    <Button type="button" variant="secondary" disabled={submitting} onClick={onClose}>Cancel</Button>
                    <Button type="submit" loading={submitting}>{actionLabel}</Button>
                </div>
            </form>
        </Modal>
    );
}

function completeMonth(value: string): { start: string; end: string } {
    const [year, month] = value.split('-').map(Number);
    if (!year || !month) return { start: '', end: '' };
    const end = new Date(Date.UTC(year, month, 0)).toISOString().slice(0, 10);
    return { start: `${value}-01`, end };
}

function monthValue(value?: string | null): string | undefined {
    return value ? value.slice(0, 7) : undefined;
}
