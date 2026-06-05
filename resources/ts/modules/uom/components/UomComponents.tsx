import { FormEvent, MouseEvent, useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { ApiError } from '../../../services/api/apiErrors';
import { AuditTimeline } from '../../../shared/components/business/AuditTimeline';
import { PreviewPanel } from '../../../shared/components/business/PreviewPanel';
import { StatusBadge } from '../../../shared/components/business/StatusBadge';
import { DataTable } from '../../../shared/components/data/DataTable';
import { FieldError } from '../../../shared/components/forms/FieldError';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { Button } from '../../../shared/components/ui/Button';
import { Card } from '../../../shared/components/ui/Card';
import { Checkbox } from '../../../shared/components/ui/Checkbox';
import { ConfirmDialog } from '../../../shared/components/ui/ConfirmDialog';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { Textarea } from '../../../shared/components/ui/Textarea';
import { uomApi } from '../services/uomApi';
import type {
    UomAuditEntry,
    UomCategory,
    UomConversion,
    UomConversionFormInput,
    UomConversionPreview,
    UomItemUsage,
    UomLookupOption,
    UomUnit,
    UomUnitFormInput,
    UomUnitStatus,
    UomUnitType,
} from '../types/uom.types';

const typeOptions = [
    { label: 'Unit / Count', value: 'UNIT' },
    { label: 'Mass', value: 'MASS' },
    { label: 'Volume', value: 'VOLUME' },
    { label: 'Length', value: 'LENGTH' },
    { label: 'Area', value: 'AREA' },
    { label: 'Time', value: 'TIME' },
    { label: 'Distance', value: 'DISTANCE' },
    { label: 'Other', value: 'OTHER' },
];

function FieldLabel({ children }: { children: string }) {
    return <label className="text-xs font-bold uppercase tracking-wide text-slate-500">{children}</label>;
}

function checked(formData: FormData, name: string) {
    return formData.get(name) === 'on';
}

function optionForUnit(unit: UomUnit) {
    return { label: `${unit.code} - ${unit.name}`, value: unit.id };
}

export function UomCompatibilityBadge({ compatible }: { compatible: boolean }) {
    return <StatusBadge status={compatible ? 'Compatible' : 'Not Compatible'} />;
}

export function UomUnitSummaryCard({ unit }: { unit: UomUnit }) {
    return (
        <Card className="p-5">
            <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <div className="flex flex-wrap items-center gap-2">
                        <p className="text-xs font-bold uppercase tracking-widest text-slate-400">{unit.code}</p>
                        <StatusBadge status={unit.status} />
                        <StatusBadge status={unit.isBase ? 'Base Unit' : 'Conversion Unit'} />
                    </div>
                    <h2 className="mt-2 text-xl font-bold text-slate-950">{unit.name}</h2>
                    <p className="mt-1 text-sm text-slate-500">{unit.description || `${unit.category} unit`}</p>
                </div>
                <div className="grid gap-3 text-sm md:grid-cols-3">
                    <div><p className="text-xs font-bold uppercase tracking-wide text-slate-400">Symbol</p><p className="mt-1 font-semibold text-slate-800">{unit.symbol}</p></div>
                    <div><p className="text-xs font-bold uppercase tracking-wide text-slate-400">Category</p><p className="mt-1 font-semibold text-slate-800">{unit.category}</p></div>
                    <div><p className="text-xs font-bold uppercase tracking-wide text-slate-400">Precision</p><p className="mt-1 font-semibold text-slate-800">{unit.precision}</p></div>
                </div>
            </div>
        </Card>
    );
}

export function UomConversionTable({
    conversions,
    onStatusChange,
}: {
    conversions: UomConversion[];
    onStatusChange?: (conversion: UomConversion) => Promise<void>;
}) {
    const [selected, setSelected] = useState<UomConversion | null>(null);
    const [isSaving, setIsSaving] = useState(false);

    async function confirmStatusChange() {
        if (!selected || !onStatusChange) {
            return;
        }

        setIsSaving(true);
        try {
            await onStatusChange(selected);
            setSelected(null);
        } finally {
            setIsSaving(false);
        }
    }

    return (
        <>
            <DataTable
                columns={[
                    { header: 'From', key: 'fromUnitCode' },
                    { header: 'To', key: 'toUnitCode' },
                    { header: 'Factor', key: 'factor' },
                    { header: 'Category', key: 'category' },
                    { header: 'Item', key: 'itemName', render: (row) => row.itemName || 'General' },
                    { header: 'Direction', key: 'direction', render: (row) => <StatusBadge status={row.direction === 'bidirectional' ? 'Bidirectional' : 'One way'} /> },
                    { header: 'Status', key: 'isActive', render: (row) => <StatusBadge status={row.isActive ? 'Active' : 'Inactive'} /> },
                    { header: 'Updated', key: 'updatedAt' },
                    {
                        header: 'Actions',
                        key: 'actions',
                        render: (row) => (
                            <div className="flex flex-wrap gap-2">
                                <Link className="font-semibold text-slate-950" to={`/uom/conversions/${row.id}/edit`}>Edit</Link>
                                {onStatusChange ? (
                                    <button className="font-semibold text-slate-500" onClick={() => setSelected(row)} type="button">
                                        {row.isActive ? 'Deactivate' : 'Activate'}
                                    </button>
                                ) : null}
                            </div>
                        ),
                    },
                ]}
                getRowKey={(row) => row.id}
                rows={conversions}
            />
            <ConfirmDialog
                message={`This will ${selected?.isActive ? 'deactivate' : 'activate'} the ${selected?.fromUnitCode ?? ''} to ${selected?.toUnitCode ?? ''} conversion.`}
                onCancel={() => setSelected(null)}
                onConfirm={confirmStatusChange}
                open={selected !== null}
                title={isSaving ? 'Updating conversion...' : 'Confirm conversion status'}
            />
        </>
    );
}

export function UomPrecisionPanel({ unit }: { unit: UomUnit }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Decimal precision', value: String(unit.precision) },
                { label: 'Fractional quantity', value: unit.allowFractional ? 'Allowed' : 'Whole numbers only' },
                { label: 'Purchase', value: unit.usableForPurchase ? 'Enabled' : 'Disabled' },
                { label: 'Sales', value: unit.usableForSales ? 'Enabled' : 'Disabled' },
                { label: 'Inventory', value: unit.usableForInventory ? 'Enabled' : 'Disabled' },
                { label: 'Service', value: unit.usableForService ? 'Enabled' : 'Disabled' },
                { label: 'Rental', value: unit.usableForRental ? 'Enabled' : 'Disabled' },
            ]}
            status="Backend API"
            subtitle="Unit precision and usage flags are stored by the UOM API."
            title="Unit Setup"
        />
    );
}

export function UomItemUsagePanel({ usage }: { usage: UomItemUsage }) {
    return (
        <PreviewPanel
            rows={[
                { label: 'Item references', value: usage.items },
                { label: 'Conversions from this unit', value: usage.conversionsFrom },
                { label: 'Conversions to this unit', value: usage.conversionsTo },
                { label: 'Inventory references', value: usage.inventory },
                { label: 'Purchase references', value: usage.purchase },
                { label: 'Service references', value: usage.service },
            ]}
            status="Backend API"
            subtitle="Reference counts are read from tenant-scoped backend tables."
            title="UOM Usage"
        />
    );
}

export function UomActivityTimeline({ entries }: { entries: UomAuditEntry[] }) {
    if (!entries.length) {
        return <EmptyState description="No audit entries have been recorded for this unit." title="No activity" />;
    }

    return <AuditTimeline events={entries.map((entry) => ({ actor: entry.actor, description: entry.description, time: entry.time }))} />;
}

export function UomConversionPreviewPanel({ preview }: { preview?: UomConversionPreview }) {
    if (!preview) {
        return <PreviewPanel rows={[{ label: 'Result', value: 'Submit a quantity to request a backend conversion.' }]} status="Ready" subtitle="The conversion result appears after the API responds." title="Conversion Preview" />;
    }

    return (
        <PreviewPanel
            rows={[
                { label: 'Input quantity', value: preview.input.quantity },
                { label: 'Converted quantity', value: preview.calculated.convertedQuantity },
                { label: 'Factor', value: preview.calculated.factor },
                { label: 'Precision / rounding', value: preview.calculated.precision },
                { label: 'Warnings', value: preview.warnings.length ? preview.warnings.join(', ') : 'None' },
                { label: 'Errors', value: preview.errors.length ? preview.errors.join(', ') : 'None' },
            ]}
            status="Backend API"
            subtitle="Converted quantity, factor, and precision are returned by the UOM conversion endpoint."
            title="Conversion Preview"
        />
    );
}

export function UomUnitStatusActions({ onChanged, unit }: { onChanged: (unit: UomUnit) => void; unit: UomUnit }) {
    const [isOpen, setIsOpen] = useState(false);
    const [error, setError] = useState('');
    const [isSaving, setIsSaving] = useState(false);

    async function confirmChange() {
        setError('');
        setIsSaving(true);
        try {
            const response = unit.isActive ? await uomApi.deactivateUnit(unit.id) : await uomApi.activateUnit(unit.id);
            onChanged(response.data);
            setIsOpen(false);
        } catch (caught) {
            setError(caught instanceof Error ? caught.message : 'Unable to update unit status.');
        } finally {
            setIsSaving(false);
        }
    }

    return (
        <div className="flex flex-wrap items-center justify-end gap-3">
            {error ? <span className="text-sm font-medium text-red-600">{error}</span> : null}
            <Button onClick={() => setIsOpen(true)} variant={unit.isActive ? 'danger' : 'secondary'}>
                {unit.isActive ? 'Deactivate Unit' : 'Activate Unit'}
            </Button>
            <ConfirmDialog
                message={`This will ${unit.isActive ? 'deactivate' : 'activate'} ${unit.code}. Existing references remain intact.`}
                onCancel={() => setIsOpen(false)}
                onConfirm={confirmChange}
                open={isOpen}
                title={isSaving ? 'Updating unit...' : 'Confirm unit status'}
            />
        </div>
    );
}

export function UomUnitForm({ mode, unit }: { mode: 'create' | 'edit'; unit?: UomUnit }) {
    const navigate = useNavigate();
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [formError, setFormError] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setErrors({});
        setFormError('');
        setIsSubmitting(true);

        const formData = new FormData(event.currentTarget);
        const input: UomUnitFormInput = {
            allowFractional: checked(formData, 'allow_fractional'),
            category: String(formData.get('category') ?? 'UNIT') as UomUnitType,
            code: String(formData.get('code') ?? ''),
            description: String(formData.get('description') ?? ''),
            isBase: checked(formData, 'is_base'),
            name: String(formData.get('name') ?? ''),
            precision: String(formData.get('precision') ?? '0'),
            status: String(formData.get('status') ?? 'active') as UomUnitStatus,
            symbol: String(formData.get('symbol') ?? ''),
            type: String(formData.get('type') ?? 'UNIT') as UomUnitType,
            usableForInventory: checked(formData, 'usable_inventory'),
            usableForPurchase: checked(formData, 'usable_purchase'),
            usableForRental: checked(formData, 'usable_rental'),
            usableForSales: checked(formData, 'usable_sales'),
            usableForService: checked(formData, 'usable_service'),
        };

        try {
            const response = mode === 'edit' && unit ? await uomApi.updateUnit(unit.id, input) : await uomApi.createUnit(input);
            navigate(`/uom/units/${response.data.id}`);
        } catch (error) {
            if (error instanceof ApiError) {
                setErrors(error.errors);
                setFormError(error.message);
            } else {
                setFormError('Unable to save UOM unit.');
            }
        } finally {
            setIsSubmitting(false);
        }
    }

    return (
        <form className="grid gap-5 xl:grid-cols-[1fr_340px]" onSubmit={handleSubmit}>
            <div className="space-y-5">
                {formError ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{formError}</div> : null}
                <FormSection title="Basic Information">
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2"><FieldLabel>Code</FieldLabel><Input defaultValue={unit?.code} name="code" /><FieldError message={errors.code?.[0]} /></div>
                        <div className="space-y-2"><FieldLabel>Name</FieldLabel><Input defaultValue={unit?.name} name="name" /><FieldError message={errors.name?.[0]} /></div>
                        <div className="space-y-2"><FieldLabel>Symbol</FieldLabel><Input defaultValue={unit?.symbol} name="symbol" /><FieldError message={errors.symbol?.[0]} /></div>
                        <div className="space-y-2"><FieldLabel>Category</FieldLabel><Select defaultValue={unit?.category ?? 'UNIT'} name="category" options={typeOptions} /><FieldError message={errors.category?.[0]} /></div>
                        <div className="space-y-2"><FieldLabel>Unit type</FieldLabel><Select defaultValue={unit?.type ?? 'UNIT'} name="type" options={typeOptions} /><FieldError message={errors.type?.[0]} /></div>
                        <div className="space-y-2"><FieldLabel>Status</FieldLabel><Select defaultValue={unit?.status ?? 'active'} name="status" options={[{ label: 'Active', value: 'active' }, { label: 'Inactive', value: 'inactive' }]} /></div>
                        <div className="space-y-2"><FieldLabel>Decimal precision</FieldLabel><Input defaultValue={unit?.precision ?? 0} min="0" name="precision" type="number" /><FieldError message={errors.decimal_precision?.[0]} /></div>
                        <div className="space-y-2 md:col-span-2"><FieldLabel>Description</FieldLabel><Textarea defaultValue={unit?.description} name="description" /></div>
                    </div>
                </FormSection>
                <FormSection title="Behavior">
                    <div className="grid gap-3 md:grid-cols-3">
                        <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={unit?.allowFractional} name="allow_fractional" />Fractional quantity</label>
                        <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={unit?.isBase} name="is_base" />Base unit</label>
                        <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={unit?.usableForPurchase ?? true} name="usable_purchase" />Purchase</label>
                        <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={unit?.usableForSales ?? true} name="usable_sales" />Sales</label>
                        <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={unit?.usableForInventory ?? true} name="usable_inventory" />Inventory</label>
                        <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={unit?.usableForService ?? true} name="usable_service" />Service</label>
                        <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={unit?.usableForRental} name="usable_rental" />Rental</label>
                    </div>
                </FormSection>
                <div className="flex justify-end gap-3"><Link to="/uom/units"><Button variant="secondary">Cancel</Button></Link><Button disabled={isSubmitting} type="submit" variant="blue">{mode === 'edit' ? 'Update Unit' : 'Create Unit'}</Button></div>
            </div>
            <PreviewPanel
                rows={[
                    { label: 'Unit category', value: unit?.type ?? 'Selected on save' },
                    { label: 'Precision', value: unit?.precision ?? 'Validated on save' },
                    { label: 'Conversion result', value: 'Available from /uom/convert' },
                    { label: 'Reference counts', value: mode === 'edit' ? 'Available on detail page' : 'Available after creation' },
                ]}
                status="Backend API"
                subtitle="This form persists UOM setup; conversion math is requested from the API."
                title="UOM Setup"
            />
        </form>
    );
}

export function UomConversionForm({ conversion, mode }: { conversion?: UomConversion; mode: 'create' | 'edit' }) {
    const navigate = useNavigate();
    const [categories, setCategories] = useState<UomCategory[]>([]);
    const [units, setUnits] = useState<UomUnit[]>([]);
    const [items, setItems] = useState<UomLookupOption[]>([]);
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [formError, setFormError] = useState('');
    const [isLoadingLookups, setIsLoadingLookups] = useState(true);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [preview, setPreview] = useState<UomConversionPreview | undefined>();

    useEffect(() => {
        let mounted = true;
        Promise.all([uomApi.listUnits(), uomApi.listItemOptions(), uomApi.listCategories()])
            .then(([unitResponse, itemResponse, categoryResponse]) => {
                if (mounted) {
                    setUnits(unitResponse.data);
                    setItems(itemResponse.data);
                    setCategories(categoryResponse.data);
                }
            })
            .catch((error: unknown) => setFormError(error instanceof Error ? error.message : 'Unable to load UOM lookups.'))
            .finally(() => { if (mounted) setIsLoadingLookups(false); });

        return () => { mounted = false; };
    }, []);

    function buildInput(form: HTMLFormElement): UomConversionFormInput {
        const formData = new FormData(form);
        const isItemSpecific = checked(formData, 'is_item_specific');

        return {
            category: String(formData.get('category') ?? '') as UomUnitType,
            effectiveFrom: String(formData.get('effective_from') ?? ''),
            effectiveTo: String(formData.get('effective_to') ?? ''),
            factor: String(formData.get('factor') ?? ''),
            fromUnitId: String(formData.get('from_unit_id') ?? ''),
            isActive: checked(formData, 'is_active'),
            isBidirectional: checked(formData, 'is_bidirectional'),
            isItemSpecific,
            itemId: isItemSpecific ? String(formData.get('item_id') ?? '') : undefined,
            notes: String(formData.get('notes') ?? ''),
            quantity: String(formData.get('quantity') ?? '1'),
            toUnitId: String(formData.get('to_unit_id') ?? ''),
        };
    }

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setErrors({});
        setFormError('');
        setIsSubmitting(true);
        const input = buildInput(event.currentTarget);

        try {
            await (mode === 'edit' && conversion ? uomApi.updateConversion(conversion.id, input) : uomApi.createConversion(input));
            navigate('/uom/conversions');
        } catch (error) {
            if (error instanceof ApiError) {
                setErrors(error.errors);
                setFormError(error.message);
            } else {
                setFormError('Unable to save UOM conversion.');
            }
        } finally {
            setIsSubmitting(false);
        }
    }

    async function handlePreview(event: MouseEvent<HTMLButtonElement>) {
        event.preventDefault();
        setFormError('');
        const form = event.currentTarget.closest('form');
        if (!form) {
            return;
        }

        try {
            const input = buildInput(form);
            const result = await uomApi.previewConversion({ fromUnitId: input.fromUnitId, itemId: input.itemId || undefined, quantity: input.quantity || '1', toUnitId: input.toUnitId });
            setPreview(result);
        } catch (error) {
            if (error instanceof ApiError) {
                setErrors(error.errors);
                setFormError(error.message);
            } else {
                setFormError('Unable to preview conversion.');
            }
        }
    }

    if (isLoadingLookups) {
        return <EmptyState description="Loading unit and item selectors..." title="Loading conversion form" />;
    }

    return (
        <form className="grid gap-5 xl:grid-cols-[1fr_340px]" onSubmit={handleSubmit}>
            <div className="space-y-5">
                {formError ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{formError}</div> : null}
                <FormSection title="Conversion Details">
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2"><FieldLabel>From unit</FieldLabel><Select defaultValue={conversion?.fromUnitId ?? ''} name="from_unit_id" options={[{ label: 'Select unit', value: '' }, ...units.map(optionForUnit)]} /><FieldError message={errors.from_uom_id?.[0]} /></div>
                        <div className="space-y-2"><FieldLabel>To unit</FieldLabel><Select defaultValue={conversion?.toUnitId ?? ''} name="to_unit_id" options={[{ label: 'Select unit', value: '' }, ...units.map(optionForUnit)]} /><FieldError message={errors.to_uom_id?.[0]} /></div>
                        <div className="space-y-2"><FieldLabel>Category</FieldLabel><Select defaultValue={conversion?.category ?? ''} name="category" options={[{ label: 'Use source unit category', value: '' }, ...categories.map((category) => ({ label: category.name, value: category.type }))]} /><FieldError message={errors.category?.[0]} /></div>
                        <div className="space-y-2"><FieldLabel>Conversion factor</FieldLabel><Input defaultValue={conversion?.factor} name="factor" /><FieldError message={errors.factor?.[0]} /></div>
                        <div className="space-y-2"><FieldLabel>Preview quantity</FieldLabel><Input defaultValue="1" min="0" name="quantity" type="number" /></div>
                        <div className="space-y-2"><FieldLabel>Optional item</FieldLabel><Select defaultValue={conversion?.itemId ?? ''} name="item_id" options={[{ label: 'General conversion', value: '' }, ...items.map((item) => ({ label: item.label, value: item.id }))]} /><FieldError message={errors.item_id?.[0]} /></div>
                        <div className="space-y-2"><FieldLabel>Effective from</FieldLabel><Input defaultValue={conversion?.effectiveFrom} name="effective_from" type="date" /></div>
                        <div className="space-y-2"><FieldLabel>Effective to</FieldLabel><Input defaultValue={conversion?.effectiveTo} name="effective_to" type="date" /><FieldError message={errors.effective_to?.[0]} /></div>
                        <div className="space-y-2 md:col-span-2"><FieldLabel>Notes</FieldLabel><Textarea defaultValue={conversion?.notes} name="notes" /></div>
                        <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={conversion?.direction !== 'one_way'} name="is_bidirectional" />Bidirectional</label>
                        <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={conversion?.isItemSpecific} name="is_item_specific" />Item-specific</label>
                        <label className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700"><Checkbox defaultChecked={conversion?.isActive ?? true} name="is_active" />Active</label>
                    </div>
                </FormSection>
                <div className="flex justify-end gap-3"><Link to="/uom/conversions"><Button variant="secondary">Cancel</Button></Link><Button disabled={isSubmitting} onClick={handlePreview} type="button" variant="secondary">Preview</Button><Button disabled={isSubmitting} type="submit" variant="blue">{mode === 'edit' ? 'Update Conversion' : 'Create Conversion'}</Button></div>
            </div>
            <UomConversionPreviewPanel preview={preview} />
        </form>
    );
}
