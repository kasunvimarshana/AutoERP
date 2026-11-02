import { fieldError, type ApiError } from '@/shared/api/apiError';
import { DecimalInput } from '@/shared/components/DecimalInput';
import type { VehicleServiceLineFormValue } from './lineForm';

export function LinePricingFields({ value, total, error, set }: {
    value: VehicleServiceLineFormValue;
    total: string;
    error: ApiError | null;
    set: <K extends keyof VehicleServiceLineFormValue>(
        key: K,
        value: VehicleServiceLineFormValue[K],
    ) => void;
}) {
    return (
        <>
            <DecimalInput
                label="Unit price"
                value={value.unit_price}
                error={fieldError(error, 'unit_price')}
                onChange={(event) => set('unit_price', event.target.value)}
            />
            <div className="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                <span className="text-xs font-semibold uppercase tracking-wide text-slate-500">Total</span>
                <strong className="mt-1 block text-lg tabular-nums text-slate-900">{total}</strong>
            </div>
        </>
    );
}
