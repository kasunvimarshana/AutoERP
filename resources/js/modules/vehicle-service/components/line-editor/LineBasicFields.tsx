import { fieldError, type ApiError } from '@/shared/api/apiError';
import { listUoms } from '@/shared/api/referenceApi';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { Input } from '@/shared/components/Input';
import { LookupSelect } from '@/shared/components/LookupSelect';
import type { VehicleServiceLineFormValue } from './lineForm';

export function LineBasicFields({ value, error, set }: {
    value: VehicleServiceLineFormValue;
    error: ApiError | null;
    set: <K extends keyof VehicleServiceLineFormValue>(
        key: K,
        value: VehicleServiceLineFormValue[K],
    ) => void;
}) {
    return (
        <>
            <Input
                label="Description"
                value={value.description}
                error={fieldError(error, 'description')}
                onChange={(event) => set('description', event.target.value)}
            />
            <DecimalInput
                label="Quantity"
                value={value.quantity}
                error={fieldError(error, 'quantity')}
                onChange={(event) => set('quantity', event.target.value)}
            />
            <LookupSelect
                label="UOM"
                value={value.uom}
                error={fieldError(error, 'uom_id')}
                onChange={(uom) => set('uom', uom)}
                search={listUoms}
                loadOnOpen
                minSearchLength={0}
            />
        </>
    );
}
