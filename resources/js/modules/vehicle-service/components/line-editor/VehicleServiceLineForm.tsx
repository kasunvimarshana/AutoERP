import { useState } from 'react';
import type { ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LineAdvancedFields } from './LineAdvancedFields';
import { LineBasicFields } from './LineBasicFields';
import { LinePricingFields } from './LinePricingFields';
import { LineSourceTypeFields } from './LineSourceTypeFields';
import { LineSummary } from './LineSummary';
import {
    calculateLinePreview,
    type VehicleServiceLineFormValue,
} from './lineForm';

export function VehicleServiceLineForm({ value, mode, error, saving, onSave, onCancel }: {
    value: VehicleServiceLineFormValue;
    mode: 'create' | 'edit';
    error: ApiError | null;
    saving: boolean;
    onSave: (value: VehicleServiceLineFormValue) => void;
    onCancel: () => void;
}) {
    const [draft, setDraft] = useState(value);
    const set = <K extends keyof VehicleServiceLineFormValue>(
        key: K,
        next: VehicleServiceLineFormValue[K],
    ) => setDraft((current) => ({ ...current, [key]: next }));
    const preview = calculateLinePreview(draft);

    return (
        <form
            className="space-y-5"
            onSubmit={(event) => {
                event.preventDefault();
                if (!saving) onSave(draft);
            }}
        >
            <ErrorAlert error={error} />
            <section className="space-y-4">
                <div>
                    <h3 className="font-semibold text-slate-900">Line details</h3>
                    <p className="text-sm text-slate-500">
                        Select the source and item, then enter quantity, UOM, and pricing.
                    </p>
                </div>
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <LineSourceTypeFields value={draft} error={error} onChange={setDraft} />
                    <LineBasicFields value={draft} error={error} set={set} />
                    <LinePricingFields value={draft} total={preview.total} error={error} set={set} />
                </div>
            </section>

            <LineAdvancedFields
                value={draft}
                error={error}
                set={set}
                onChange={setDraft}
            />
            <LineSummary preview={preview} />

            <div className="flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button>
                <Button type="submit" loading={saving}>
                    {mode === 'edit' ? 'Save line' : 'Add line'}
                </Button>
            </div>
        </form>
    );
}
