import { useEffect, useRef, useState } from 'react';
import { Button } from '@/shared/components/Button';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { isNonNegativeDecimal, isPositiveDecimal } from '@/shared/utils/decimal';
import { getPurchaseItemContext } from '../purchaseApi';
import type { PurchaseItemContext } from '../purchaseTypes';
import { ItemLookupSelect } from './PurchaseLookups';
import {
    previewLineAmounts,
    type EditablePurchaseLine,
    type PurchaseLineCalculationType,
    type PurchaseLineEditorConfig,
    type PurchaseLineField,
} from './purchaseLineModel';

type LineFormErrors = Partial<Record<'item' | 'uom' | 'quantity' | 'unit_price', string>>;

const calculationOptions = [
    { value: 'fixed', label: 'Fixed' },
    { value: 'percentage', label: 'Percentage' },
];

export function PurchaseLineForm({ line, mode, config, supplierId, currencyId, warehouseId, purchaseDate, errorFor, onSave, onCancel }: {
    line: EditablePurchaseLine;
    mode: 'create' | 'edit';
    config: PurchaseLineEditorConfig;
    supplierId?: number;
    currencyId?: number;
    warehouseId?: number;
    purchaseDate?: string;
    errorFor: (field: PurchaseLineField) => string | undefined;
    onSave: (line: EditablePurchaseLine) => void;
    onCancel: () => void;
}) {
    const [draft, setDraft] = useState(line);
    const [context, setContext] = useState<PurchaseItemContext | null>(null);
    const [errors, setErrors] = useState<LineFormErrors>({});
    const formRef = useRef<HTMLFormElement>(null);

    const set = <K extends keyof EditablePurchaseLine>(key: K, value: EditablePurchaseLine[K]) => {
        setDraft((current) => ({ ...current, [key]: value }));
        if (key in errors) {
            setErrors((current) => ({ ...current, [key]: undefined }));
        }
    };
    const formError = (field: keyof LineFormErrors) => errors[field] ?? errorFor(fieldToBackendField(field));
    const preview = previewLineAmounts(draft);

    useEffect(() => {
        if (!draft.item?.id) {
            setContext(null);
            return;
        }

        const controller = new AbortController();
        void getPurchaseItemContext(draft.item.id, {
            supplier_id: supplierId,
            item_variant_id: draft.item_variant_id ?? undefined,
            currency_id: currencyId,
            warehouse_id: warehouseId,
            uom_id: draft.uom?.id,
            purchase_date: purchaseDate,
        }, controller.signal)
            .then((nextContext) => {
                if (controller.signal.aborted) return;
                setContext(nextContext);
                setDraft((current) => {
                    if (current.item?.id !== nextContext.item.id) return current;
                    const resolvedUomId = nextContext.uom_id ?? nextContext.default_purchase_uom_id;
                    const defaultUom = nextContext.allowed_purchase_uoms.find((row) => row.id === resolvedUomId);

                    return {
                        ...current,
                        description: current.description || nextContext.description || '',
                        uom: current.auto_uom && defaultUom?.uom ? defaultUom.uom : current.uom,
                        unit_price: current.auto_price && nextContext.unit_price ? nextContext.unit_price : current.unit_price,
                        tax_group_id: config.taxMode === 'tax_group' && !current.tax_group_id && typeof nextContext.tax_defaults?.tax_group_id === 'number'
                            ? String(nextContext.tax_defaults.tax_group_id)
                            : current.tax_group_id,
                        price_source_label: nextContext.price_source_label,
                        price_source: nextContext.price_source,
                        price_source_id: nextContext.price_source_id ?? null,
                        pricing_context_hash: nextContext.pricing_context_hash ?? null,
                    };
                });
            })
            .catch(() => undefined);

        return () => controller.abort();
    }, [config.taxMode, draft.item?.id, draft.item_variant_id, draft.uom?.id, supplierId, currencyId, warehouseId, purchaseDate]);

    useEffect(() => {
        if (Object.keys(errors).length === 0) return;
        const invalid = formRef.current?.querySelector<HTMLElement>('[aria-invalid="true"]');
        invalid?.focus();
    }, [errors]);

    const variantOptions = context?.variants.map((variant) => ({
        value: variant.id,
        label: [variant.code, variant.name].filter(Boolean).join(' - ') || `Variant #${variant.id}`,
    })) ?? [];
    const uomOptions = context?.allowed_purchase_uoms.map((row) => ({
        value: row.id,
        label: [row.uom?.code, row.uom?.name].filter(Boolean).join(' - ') || `UOM #${row.id}`,
    })) ?? [];
    const taxGroupOptions = config.taxGroupOptions?.map((group) => ({
        value: group.id,
        label: [group.code, group.name].filter(Boolean).join(' - ') || `Tax group #${group.id}`,
    })) ?? [];

    return (
        <form ref={formRef} className="space-y-5" onSubmit={(event) => {
            event.preventDefault();
            const nextErrors = validateLineForm(draft, config);
            setErrors(nextErrors);
            if (Object.keys(nextErrors).length > 0) return;
            onSave(draft);
        }}>
            <section className="space-y-4">
                <div>
                    <h3 className="font-semibold text-slate-900">Basic Details</h3>
                    <p className="text-sm text-slate-500">Item, quantity, UOM, and price are enough for most lines.</p>
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                    <ItemLookupSelect
                        value={draft.item}
                        onChange={(item) => {
                            setDraft((current) => ({
                                ...current,
                                item,
                                item_variant: null,
                                item_variant_id: null,
                                description: item?.name ?? '',
                                uom: null,
                                unit_price: current.auto_price === false && current.pricing_state !== 'persisted'
                                    ? current.unit_price
                                    : config.defaultLine?.unit_price ?? current.unit_price,
                                tax_group_id: '',
                                price_source_label: undefined,
                                price_source: null,
                                price_source_id: null,
                                pricing_context_hash: null,
                                manual_price_confirmed: current.auto_price === false && current.pricing_state !== 'persisted' ? false : current.manual_price_confirmed,
                                auto_price: current.auto_price === false && current.pricing_state !== 'persisted' ? false : true,
                                pricing_state: current.auto_price === false && current.pricing_state !== 'persisted' ? 'manual_confirmed' : 'auto',
                                auto_uom: true,
                            }));
                            setErrors((current) => ({ ...current, item: undefined }));
                        }}
                        error={formError('item') ?? errorFor('item_id')}
                    />
                    <Select
                        label="Variant"
                        value={draft.item_variant_id ?? ''}
                        options={variantOptions}
                        disabled={!draft.item || variantOptions.length === 0}
                        error={errorFor('item_variant_id')}
                        onChange={(event) => {
                            const variantId = event.target.value ? Number(event.target.value) : null;
                            const variant = context?.variants.find((row) => row.id === variantId) ?? null;
                            setDraft((current) => ({
                                ...current,
                                item_variant: variant,
                                item_variant_id: variantId,
                                price_source_label: undefined,
                                price_source: null,
                                price_source_id: null,
                                pricing_context_hash: null,
                                manual_price_confirmed: current.auto_price === false && current.pricing_state !== 'persisted' ? false : current.manual_price_confirmed,
                                auto_price: current.auto_price === false && current.pricing_state !== 'persisted' ? false : true,
                                pricing_state: current.auto_price === false && current.pricing_state !== 'persisted' ? 'manual_confirmed' : 'auto',
                                auto_uom: true,
                            }));
                        }}
                    />
                    <DecimalInput label="Quantity" value={draft.quantity} error={formError('quantity')} onChange={(event) => set('quantity', event.target.value)} />
                    <Select
                        label="UOM"
                        value={draft.uom?.id ?? ''}
                        options={uomOptions}
                        disabled={!context}
                        error={formError('uom') ?? errorFor('uom_id')}
                        onChange={(event) => {
                            const uomId = event.target.value ? Number(event.target.value) : null;
                            const selected = context?.allowed_purchase_uoms.find((row) => row.id === uomId)?.uom ?? null;
                            setDraft((current) => ({
                                ...current,
                                uom: selected,
                                auto_uom: false,
                                price_source_label: undefined,
                                price_source: null,
                                price_source_id: null,
                                pricing_context_hash: null,
                                manual_price_confirmed: current.auto_price === false && current.pricing_state !== 'persisted' ? false : current.manual_price_confirmed,
                                auto_price: current.auto_price === false && current.pricing_state !== 'persisted' ? false : true,
                                pricing_state: current.auto_price === false && current.pricing_state !== 'persisted' ? 'manual_confirmed' : 'auto',
                            }));
                        }}
                    />
                    <DecimalInput
                        label={config.unitLabel}
                        value={draft.unit_price}
                        error={formError('unit_price')}
                        onChange={(event) => setDraft((current) => ({
                            ...current,
                            unit_price: event.target.value,
                            auto_price: false,
                            pricing_state: 'manual_confirmed',
                            manual_price_confirmed: true,
                            pricing_context_hash: context?.pricing_context_hash ?? current.pricing_context_hash ?? null,
                        }))}
                    />
                    <Input className="sm:col-span-2" label="Description" value={draft.description} onChange={(event) => set('description', event.target.value)} />
                </div>
                {draft.price_source_label && <p className="text-xs text-slate-500">{draft.price_source_label}</p>}
            </section>

            <details className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <summary className="cursor-pointer font-semibold text-slate-800">Advanced pricing</summary>
                <p className="mt-1 text-sm text-slate-500">Advanced pricing is optional.</p>
                <div className="mt-4 grid gap-4 sm:grid-cols-2">
                    <CalculationField
                        label="Discount"
                        type={draft.discount_calculation_type}
                        rate={draft.discount_rate}
                        amount={draft.discount_amount}
                        typeError={errorFor('discount_calculation_type')}
                        valueError={errorFor(draft.discount_calculation_type === 'percentage' ? 'discount_rate' : 'discount_amount')}
                        onTypeChange={(type) => set('discount_calculation_type', type)}
                        onValueChange={(value) => set(draft.discount_calculation_type === 'percentage' ? 'discount_rate' : 'discount_amount', value)}
                    />
                    {config.taxMode === 'manual' ? (
                        <CalculationField
                            label="Tax"
                            type={draft.tax_calculation_type}
                            rate={draft.tax_rate}
                            amount={draft.tax_amount}
                            typeError={errorFor('tax_calculation_type')}
                            valueError={errorFor(draft.tax_calculation_type === 'percentage' ? 'tax_rate' : 'tax_amount')}
                            onTypeChange={(type) => set('tax_calculation_type', type)}
                            onValueChange={(value) => set(draft.tax_calculation_type === 'percentage' ? 'tax_rate' : 'tax_amount', value)}
                        />
                    ) : (
                        <Select
                            label="Tax group"
                            value={draft.tax_group_id}
                            options={taxGroupOptions}
                            placeholder="Default"
                            error={errorFor('tax_group_id')}
                            onChange={(event) => set('tax_group_id', event.target.value)}
                        />
                    )}
                    <CalculationField
                        label="Charge"
                        type={draft.charge_calculation_type}
                        rate={draft.charge_rate}
                        amount={draft.charge_amount}
                        typeError={errorFor('charge_calculation_type')}
                        valueError={errorFor(draft.charge_calculation_type === 'percentage' ? 'charge_rate' : 'charge_amount')}
                        onTypeChange={(type) => set('charge_calculation_type', type)}
                        onValueChange={(value) => set(draft.charge_calculation_type === 'percentage' ? 'charge_rate' : 'charge_amount', value)}
                    />
                </div>
            </details>

            <div className="rounded-lg border border-slate-200 p-4 text-sm">
                <h3 className="font-semibold text-slate-900">Line Preview</h3>
                <div className="mt-3 grid gap-2 sm:grid-cols-5">
                    <PreviewValue label="Subtotal" value={preview.subtotal} />
                    <PreviewValue label="Discount" value={preview.discount} />
                    <PreviewValue label="Tax" value={config.taxMode === 'manual' ? preview.tax : 'Backend'} />
                    <PreviewValue label="Charge" value={preview.charge} />
                    <PreviewValue label="Total" value={config.taxMode === 'manual' ? preview.total : 'Backend'} />
                </div>
            </div>

            <div className="flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button>
                <Button type="submit">{mode === 'edit' ? 'Save line' : 'Add line'}</Button>
            </div>
        </form>
    );
}

function CalculationField({ label, type, rate, amount, typeError, valueError, onTypeChange, onValueChange }: {
    label: string;
    type: PurchaseLineCalculationType;
    rate: string;
    amount: string;
    typeError?: string;
    valueError?: string;
    onTypeChange: (type: PurchaseLineCalculationType) => void;
    onValueChange: (value: string) => void;
}) {
    return (
        <>
            <Select
                label={`${label} type`}
                value={type}
                options={calculationOptions}
                error={typeError}
                onChange={(event) => onTypeChange(event.target.value as PurchaseLineCalculationType)}
            />
            <DecimalInput
                label={type === 'percentage' ? `${label} value (%)` : `${label} value`}
                value={type === 'percentage' ? rate : amount}
                error={valueError}
                onChange={(event) => onValueChange(event.target.value)}
            />
        </>
    );
}

function validateLineForm(line: EditablePurchaseLine, config: PurchaseLineEditorConfig): LineFormErrors {
    const errors: LineFormErrors = {};
    if (!line.item) errors.item = 'Select an item.';
    if (!line.uom) errors.uom = 'Select a UOM.';
    if (!isPositiveDecimal(line.quantity)) errors.quantity = 'Quantity must be greater than zero.';
    if (config.unitPriceMustBePositive) {
        if (!isPositiveDecimal(line.unit_price)) errors.unit_price = `${config.unitLabel} must be greater than zero.`;
    } else if (!isNonNegativeDecimal(line.unit_price)) {
        errors.unit_price = `${config.unitLabel} cannot be negative.`;
    }
    if (line.auto_price === false && line.pricing_state !== 'persisted' && !line.manual_price_confirmed) {
        errors.unit_price = 'Manual price must be confirmed for the current line context.';
    }
    return errors;
}

function fieldToBackendField(field: keyof LineFormErrors): PurchaseLineField {
    if (field === 'item') return 'item_id';
    if (field === 'uom') return 'uom_id';
    return field;
}

function PreviewValue({ label, value }: { label: string; value: string }) {
    return <div><span className="text-xs uppercase text-slate-500">{label}</span><strong className="block tabular-nums text-slate-900">{value}</strong></div>;
}
