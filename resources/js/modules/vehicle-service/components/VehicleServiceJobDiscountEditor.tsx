import { useState } from 'react';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { FormDrawer } from '@/shared/components/Drawer';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { MoneyDisplay } from '@/shared/components/MoneyDisplay';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { addDecimal, compareDecimalStrings, percentageOfDecimal, subtractDecimal } from '@/shared/utils/decimal';
import { removeVehicleServiceJobDiscount, setVehicleServiceJobDiscount } from '../vehicleServiceApi';
import type { VehicleServiceDiscountCalculationType, VehicleServiceJob } from '../vehicleServiceTypes';

const ZERO = '0.000000';
const calculationOptions = [
    { value: 'fixed', label: 'Fixed amount' },
    { value: 'percentage', label: 'Percentage' },
];

export function VehicleServiceJobDiscountEditor({ job, onChanged }: {
    job: VehicleServiceJob;
    onChanged: (job: VehicleServiceJob) => void;
}) {
    const [open, setOpen] = useState(false);
    const [calculationType, setCalculationType] = useState<VehicleServiceDiscountCalculationType>('fixed');
    const [value, setValue] = useState(ZERO);
    const [reason, setReason] = useState('');
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const current = job.job_discount ?? null;
    const previewAmount = calculationType === 'percentage'
        ? percentageOfDecimal(job.job_discount_base, value || ZERO)
        : (value || ZERO);
    const totalBeforeJobDiscount = addDecimal(job.grand_total, job.job_discount_amount);
    const previewGrandTotal = subtractDecimal(totalBeforeJobDiscount, previewAmount);

    const show = () => {
        setCalculationType(current?.calculation_type ?? 'fixed');
        setValue(current
            ? (current.calculation_type === 'percentage' ? current.rate : current.fixed_amount)
            : ZERO);
        setReason('');
        setError(null);
        setOpen(true);
    };

    const save = async () => {
        if (saving) return;
        setSaving(true);
        setError(null);
        try {
            const updated = await setVehicleServiceJobDiscount(job.id, {
                expected_version: job.row_version ?? 0,
                calculation_type: calculationType,
                rate: calculationType === 'percentage' ? value : ZERO,
                fixed_amount: calculationType === 'fixed' ? value : ZERO,
                reason,
            });
            onChanged(updated);
            setOpen(false);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    };

    const remove = async () => {
        if (!current || saving) return;
        setSaving(true);
        setError(null);
        try {
            const updated = await removeVehicleServiceJobDiscount(job.id, job.row_version ?? 0, reason);
            onChanged(updated);
            setOpen(false);
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    };

    return (
        <>
            <Button
                type="button"
                variant="secondary"
                disabled={compareDecimalStrings(job.job_discount_base, ZERO) <= 0}
                onClick={show}
            >
                {current ? 'Edit job discount' : 'Add job discount'}
            </Button>
            <FormDrawer
                open={open}
                title={current ? 'Edit whole-job discount' : 'Add whole-job discount'}
                onClose={() => !saving && setOpen(false)}
                closeDisabled={saving}
            >
                <form className="space-y-5" onSubmit={(event) => {
                    event.preventDefault();
                    void save();
                }}>
                    <ErrorAlert error={error} />
                    <div className="grid gap-4 sm:grid-cols-2">
                        <Select
                            label="Discount type"
                            value={calculationType}
                            options={calculationOptions}
                            error={fieldError(error, 'calculation_type')}
                            onChange={(event) => setCalculationType(event.target.value as VehicleServiceDiscountCalculationType)}
                        />
                        <DecimalInput
                            label={calculationType === 'percentage' ? 'Rate (%)' : 'Amount'}
                            value={value}
                            error={fieldError(error, calculationType === 'percentage' ? 'rate' : 'fixed_amount')}
                            onChange={(event) => setValue(event.target.value)}
                        />
                    </div>
                    <Textarea
                        label="Reason"
                        value={reason}
                        error={fieldError(error, 'reason')}
                        hint="Required for adding, changing, or removing a discount."
                        onChange={(event) => setReason(event.target.value)}
                    />
                    <div className="grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 sm:grid-cols-3">
                        <Preview label="Eligible amount" value={job.job_discount_base} />
                        <Preview label="Discount" value={previewAmount} />
                        <Preview label="New grand total" value={previewGrandTotal} />
                    </div>
                    <div className="flex flex-wrap justify-between gap-3">
                        <div>
                            {current && (
                                <Button type="button" variant="secondary" loading={saving} onClick={() => void remove()}>
                                    Remove discount
                                </Button>
                            )}
                        </div>
                        <div className="flex gap-2">
                            <Button type="button" variant="secondary" disabled={saving} onClick={() => setOpen(false)}>Cancel</Button>
                            <Button type="submit" loading={saving}>{current ? 'Save discount' : 'Add discount'}</Button>
                        </div>
                    </div>
                </form>
            </FormDrawer>
        </>
    );
}

function Preview({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <span className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</span>
            <div className="mt-1 font-semibold text-slate-900"><MoneyDisplay value={value} /></div>
        </div>
    );
}
