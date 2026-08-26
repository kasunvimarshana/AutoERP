import { useState } from 'react';
import type { ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { VehicleServiceInventoryLocationFields } from '../VehicleServiceInventoryLocationFields';
import { LineAdvancedFields } from './LineAdvancedFields';
import { LineBasicFields } from './LineBasicFields';
import { LinePricingFields } from './LinePricingFields';
import { isInventoryLineItem, LineItemFields } from './LineItemFields';
import { LineSummary } from './LineSummary';
import {
    calculateLinePreview,
    type VehicleServiceLineFormValue,
} from './lineForm';

export function VehicleServiceLineForm({
    value,
    mode,
    error,
    saving,
    canIssueInventory,
    onSave,
    onCancel,
}: {
    value: VehicleServiceLineFormValue;
    mode: 'create' | 'edit';
    error: ApiError | null;
    saving: boolean;
    canIssueInventory: boolean;
    onSave: (value: VehicleServiceLineFormValue, issueStock: boolean) => void;
    onCancel: () => void;
}) {
    const [draft, setDraft] = useState(value);
    const set = <K extends keyof VehicleServiceLineFormValue>(
        key: K,
        next: VehicleServiceLineFormValue[K],
    ) => setDraft((current) => ({ ...current, [key]: next }));
    const preview = calculateLinePreview(draft);
    const showIssueControls = canIssueInventory
        && mode === 'create'
        && draft.item !== null
        && draft.source === 'inventory_item'
        && isInventoryLineItem(draft.item);
    const canIssueOnCreate = showIssueControls
        && draft.issueWarehouse !== null
        && draft.issueLocation !== null;

    return (
        <form
            className="space-y-5"
            onSubmit={(event) => {
                event.preventDefault();
                if (!saving) onSave(draft, false);
            }}
        >
            <ErrorAlert error={error} />
            <section className="space-y-4">
                <div>
                    <h3 className="font-semibold text-slate-900">Line details</h3>
                    <p className="text-sm text-slate-500">
                        Search for an item, then enter quantity, UOM, and pricing.
                    </p>
                </div>
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <LineItemFields value={draft} error={error} onChange={setDraft} />
                    {showIssueControls && (
                        <div className="sm:col-span-2 lg:col-span-3">
                            <VehicleServiceInventoryLocationFields
                                value={{ warehouse: draft.issueWarehouse, location: draft.issueLocation }}
                                onChange={({ warehouse, location }) => setDraft((current) => ({
                                    ...current,
                                    issueWarehouse: warehouse,
                                    issueLocation: location,
                                }))}
                                disabled={saving}
                            />
                        </div>
                    )}
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

            <div className="flex flex-wrap justify-end gap-2">
                <Button type="button" variant="secondary" disabled={saving} onClick={onCancel}>Cancel</Button>
                <Button type="submit" loading={saving}>
                    {mode === 'edit' ? 'Save line' : 'Add line'}
                </Button>
                {showIssueControls && (
                    <Button
                        type="button"
                        loading={saving}
                        disabled={!canIssueOnCreate}
                        onClick={() => onSave(draft, true)}
                    >
                        Add & issue stock
                    </Button>
                )}
            </div>
        </form>
    );
}
