import { useState } from 'react';
import type { ReactNode } from 'react';
import { Button } from '@/shared/components/Button';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { useApi } from '@/shared/hooks/useApi';
import type { NamedResource } from '@/shared/types/common';
import { listItemUsageModules } from '../itemApi';
import {
    bundleLineTypes,
    itemCodeTypes,
    itemPriceTypes,
    type ItemBundlePayload,
    type ItemCodePayload,
    type ItemPricePayload,
    type ItemSummary,
    type ItemUnitPayload,
    type ItemUsageRulePayload,
    type ItemVariantPayload,
    type ItemUsageModule,
} from '../itemTypes';
import { ItemLookupSelect } from './ItemLookupSelect';
import { ItemUomSelect } from './ItemUomSelect';

export interface OneShotDraft {
    units: Array<ItemUnitPayload & { uom: NamedResource }>;
    variants: ItemVariantPayload[];
    bundles: Array<ItemBundlePayload & { child_item: ItemSummary; uom?: NamedResource | null }>;
    prices: Array<ItemPricePayload & { uom?: NamedResource | null }>;
    codes: ItemCodePayload[];
    usageRules: ItemUsageRulePayload[];
}

export const emptyOneShotDraft: OneShotDraft = {
    units: [],
    variants: [],
    bundles: [],
    prices: [],
    codes: [],
    usageRules: [],
};

export function ItemOneShotBuilder({ section, value, onChange }: {
    section: 'units' | 'variants' | 'bundles' | 'prices' | 'codes' | 'usage_rules';
    value: OneShotDraft;
    onChange: (value: OneShotDraft) => void;
}) {
    if (section === 'units') return <UnitDraft value={value} onChange={onChange} />;
    if (section === 'variants') return <VariantDraft value={value} onChange={onChange} />;
    if (section === 'bundles') return <BundleDraft value={value} onChange={onChange} />;
    if (section === 'prices') return <PriceDraft value={value} onChange={onChange} />;
    if (section === 'codes') return <CodeDraft value={value} onChange={onChange} />;
    if (section === 'usage_rules') return <UsageDraft value={value} onChange={onChange} />;

    return null;
}

function UnitDraft({ value, onChange }: DraftProps) {
    const [uom, setUom] = useState<NamedResource | null>(null);
    const [role, setRole] = useState('purchase');
    const [factor, setFactor] = useState('1.000000');
    const [isDefault, setDefault] = useState(true);
    return <DraftSection title="Initial units" rows={value.units.map((row) => `${row.uom.code} - ${row.uom.name} / ${row.unit_role} / ${row.conversion_factor}`)} remove={(index) => onChange({ ...value, units: value.units.filter((_, rowIndex) => rowIndex !== index) })}>
        <ItemUomSelect value={uom} onChange={setUom} />
        <Select label="Role" value={role} onChange={(event) => setRole(event.target.value)} options={options(['purchase', 'sales', 'service', 'rental'])} />
        <Input label="Conversion factor" value={factor} onChange={(event) => setFactor(event.target.value)} />
        <label className="self-end pb-2 text-sm"><input className="mr-2" type="checkbox" checked={isDefault} onChange={(event) => setDefault(event.target.checked)} />Default Unit</label>
        <Button type="button" disabled={!uom} onClick={() => {
            if (!uom) return;
            const nextUnit = { uom, uom_id: Number(uom.id), unit_role: role, conversion_factor: factor, is_default: isDefault, is_active: true };
            onChange({
                ...value,
                units: [
                    ...(isDefault ? value.units.map((row) => ({ ...row, is_default: false })) : value.units),
                    nextUnit,
                ],
            });
            setUom(null);
        }}>Add unit</Button>
    </DraftSection>;
}

function VariantDraft({ value, onChange }: DraftProps) {
    const [code, setCode] = useState('');
    const [name, setName] = useState('');
    return <DraftSection title="Initial variants" rows={value.variants.map((row) => `${row.code} - ${row.name}`)} remove={(index) => onChange({ ...value, variants: value.variants.filter((_, rowIndex) => rowIndex !== index) })}>
        <Input label="Code" value={code} onChange={(event) => setCode(event.target.value)} />
        <Input label="Name" value={name} onChange={(event) => setName(event.target.value)} />
        <Button type="button" disabled={!code || !name} onClick={() => {
            onChange({ ...value, variants: [...value.variants, { code, name, is_active: true }] });
            setCode(''); setName('');
        }}>Add variant</Button>
    </DraftSection>;
}

function BundleDraft({ value, onChange }: DraftProps) {
    const [child, setChild] = useState<ItemSummary | null>(null);
    const [uom, setUom] = useState<NamedResource | null>(null);
    const [quantity, setQuantity] = useState('1.000000');
    const [lineType, setLineType] = useState('stock');
    return <DraftSection title="Initial bundle lines" rows={value.bundles.map((row) => `${row.child_item.code} - ${row.child_item.name} / ${row.quantity}`)} remove={(index) => onChange({ ...value, bundles: value.bundles.filter((_, rowIndex) => rowIndex !== index) })}>
        <ItemLookupSelect label="Child item" value={child} onChange={setChild} />
        <Input label="Quantity" value={quantity} onChange={(event) => setQuantity(event.target.value)} />
        <ItemUomSelect value={uom} onChange={setUom} />
        <Select label="Line type" value={lineType} onChange={(event) => setLineType(event.target.value)} options={options(bundleLineTypes)} />
        <Button type="button" disabled={!child} onClick={() => {
            if (!child) return;
            onChange({ ...value, bundles: [...value.bundles, { child_item: child, child_item_id: Number(child.id), quantity, uom, uom_id: uom ? Number(uom.id) : null, line_type: lineType, is_required: true, sort_order: value.bundles.length }] });
            setChild(null); setUom(null);
        }}>Add bundle line</Button>
    </DraftSection>;
}

function PriceDraft({ value, onChange }: DraftProps) {
    const [priceType, setPriceType] = useState('standard');
    const [amount, setAmount] = useState('0.000000');
    const [uom, setUom] = useState<NamedResource | null>(null);
    return <DraftSection title="Initial prices" rows={value.prices.map((row) => `${row.price_type} / ${row.amount}${row.uom ? ` / ${row.uom.code}` : ''}`)} remove={(index) => onChange({ ...value, prices: value.prices.filter((_, rowIndex) => rowIndex !== index) })}>
        <Select label="Price type" value={priceType} onChange={(event) => setPriceType(event.target.value)} options={options(itemPriceTypes)} />
        <Input label="Amount" value={amount} onChange={(event) => setAmount(event.target.value)} />
        <ItemUomSelect value={uom} onChange={setUom} />
        <Button type="button" onClick={() => onChange({ ...value, prices: [...value.prices, { price_type: priceType, amount, uom, uom_id: uom ? Number(uom.id) : null, is_active: true }] })}>Add price</Button>
    </DraftSection>;
}

function CodeDraft({ value, onChange }: DraftProps) {
    const [codeType, setCodeType] = useState('internal_code');
    const [code, setCode] = useState('');
    return <DraftSection title="Initial codes" rows={value.codes.map((row) => `${row.code_type} / ${row.code}`)} remove={(index) => onChange({ ...value, codes: value.codes.filter((_, rowIndex) => rowIndex !== index) })}>
        <Select label="Code type" value={codeType} onChange={(event) => setCodeType(event.target.value)} options={options(itemCodeTypes)} />
        <Input label="Code" value={code} onChange={(event) => setCode(event.target.value)} />
        <Button type="button" disabled={!code} onClick={() => {
            const isPrimary = value.codes.length === 0;
            onChange({
                ...value,
                codes: [
                    ...(isPrimary ? value.codes.map((row) => ({ ...row, is_primary: false })) : value.codes),
                    { code_type: codeType, code, is_primary: isPrimary },
                ],
            });
            setCode('');
        }}>Add code</Button>
    </DraftSection>;
}

function UsageDraft({ value, onChange }: DraftProps) {
    const modules = useApi((signal) => listItemUsageModules(signal), []);
    const [moduleCode, setModuleCode] = useState('');
    const moduleOptions = usageModuleOptions(modules.data);

    return <DraftSection title="Initial usage rules" rows={value.usageRules.map((row) => `${row.module_code} / ${row.is_enabled ? 'enabled' : 'disabled'}`)} remove={(index) => onChange({ ...value, usageRules: value.usageRules.filter((_, rowIndex) => rowIndex !== index) })}>
        <div>
            <ErrorAlert error={modules.error} />
            <Select label="Module" value={moduleCode} onChange={(event) => setModuleCode(event.target.value)} options={moduleOptions} disabled={modules.loading || moduleOptions.length === 0} />
        </div>
        <Button type="button" disabled={!moduleCode} onClick={() => { onChange({ ...value, usageRules: [...value.usageRules, { module_code: moduleCode, is_enabled: true }] }); setModuleCode(''); }}>Add usage rule</Button>
    </DraftSection>;
}

function usageModuleOptions(modules: ItemUsageModule[] | null) {
    return (modules ?? []).map((module) => ({
        value: module.code,
        label: module.name,
    }));
}

function DraftSection({ title, rows, remove, children }: { title: string; rows: string[]; remove: (index: number) => void; children: ReactNode }) {
    return <div>
        <h3 className="mb-3 font-semibold text-slate-900">{title}</h3>
        <div className="grid items-end gap-4 md:grid-cols-2 xl:grid-cols-4">{children}</div>
        <div className="mt-5 space-y-2">{rows.map((row, index) => <div key={`${row}-${index}`} className="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2 text-sm"><span>{row}</span><button type="button" className="font-semibold text-rose-600" onClick={() => remove(index)}>Remove</button></div>)}</div>
    </div>;
}

function options(values: readonly string[]) {
    return values.map((value) => ({ value, label: value.replaceAll('_', ' ') }));
}

interface DraftProps {
    value: OneShotDraft;
    onChange: (value: OneShotDraft) => void;
}
