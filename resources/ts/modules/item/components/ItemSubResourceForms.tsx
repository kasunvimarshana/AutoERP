import { FormEvent, useState } from 'react';
import { ApiError } from '../../../services/api/apiErrors';
import { FieldError } from '../../../shared/components/forms/FieldError';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { Button } from '../../../shared/components/ui/Button';
import { Checkbox } from '../../../shared/components/ui/Checkbox';
import { ConfirmDialog } from '../../../shared/components/ui/ConfirmDialog';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { Textarea } from '../../../shared/components/ui/Textarea';
import { itemApi } from '../services/itemApi';
import type { Item, ItemAttribute, ItemCategory, ItemComboComponent, ItemIdentifier, ItemVariant, UomOption } from '../types/item.types';

type FormProps = {
    item?: Item;
    onSaved: () => void;
};

function field(errors: Record<string, string[]>, name: string) {
    return errors[name]?.[0];
}

function text(formData: FormData, name: string) {
    return String(formData.get(name) ?? '').trim();
}

function useFormState() {
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [message, setMessage] = useState('');
    const [saving, setSaving] = useState(false);

    function fail(error: unknown, fallback: string) {
        if (error instanceof ApiError) {
            setErrors(error.errors);
            setMessage(error.message);
            return;
        }

        setMessage(error instanceof Error ? error.message : fallback);
    }

    return { errors, fail, message, saving, setErrors, setMessage, setSaving };
}

export function DeleteItemSubResourceButton({ label, onDelete }: { label: string; onDelete: () => Promise<void> }) {
    const [open, setOpen] = useState(false);
    const [isDeleting, setIsDeleting] = useState(false);

    async function confirm() {
        setIsDeleting(true);
        try {
            await onDelete();
            setOpen(false);
        } finally {
            setIsDeleting(false);
        }
    }

    return (
        <>
            <Button disabled={isDeleting} onClick={() => setOpen(true)} variant="danger">{isDeleting ? 'Removing...' : 'Remove'}</Button>
            <ConfirmDialog message={`Remove ${label}? This only removes the Item setup record.`} onCancel={() => setOpen(false)} onConfirm={() => void confirm()} open={open} title="Confirm removal" />
        </>
    );
}

export function ItemCategoryCreateForm({ onSaved }: FormProps) {
    const state = useFormState();

    async function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        state.setErrors({});
        state.setMessage('');
        state.setSaving(true);
        const formData = new FormData(event.currentTarget);
        try {
            await itemApi.createCategory({
                code: text(formData, 'code'),
                description: text(formData, 'description'),
                isActive: true,
                name: text(formData, 'name'),
            });
            event.currentTarget.reset();
            onSaved();
        } catch (error) {
            state.fail(error, 'Unable to create category.');
        } finally {
            state.setSaving(false);
        }
    }

    return (
        <FormSection description="Create reusable Item categories through the backend Item API." title="Create Category">
            <form className="grid gap-3 md:grid-cols-4" onSubmit={submit}>
                {state.message ? <div className="rounded-lg border border-red-100 bg-red-50 px-3 py-2 text-sm text-red-700 md:col-span-4">{state.message}</div> : null}
                <div><Input name="code" placeholder="TOOLS" /><FieldError message={field(state.errors, 'code')} /></div>
                <div><Input name="name" placeholder="Tools" /><FieldError message={field(state.errors, 'name')} /></div>
                <div><Textarea name="description" placeholder="Category description" /></div>
                <label className="flex items-center gap-2 text-sm font-semibold text-slate-700"><Checkbox defaultChecked name="is_active" /> Active</label>
                <div className="md:col-span-4"><Button disabled={state.saving} type="submit" variant="blue">{state.saving ? 'Creating...' : 'Create Category'}</Button></div>
            </form>
        </FormSection>
    );
}

export function ItemAttributeCreateForm({ onSaved }: FormProps) {
    const state = useFormState();

    async function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        state.setErrors({});
        state.setMessage('');
        state.setSaving(true);
        const formData = new FormData(event.currentTarget);
        try {
            await itemApi.createAttribute({
                isRequired: formData.get('is_required') === 'on',
                name: text(formData, 'name'),
                type: text(formData, 'type'),
            });
            event.currentTarget.reset();
            onSaved();
        } catch (error) {
            state.fail(error, 'Unable to create attribute.');
        } finally {
            state.setSaving(false);
        }
    }

    return (
        <FormSection description="Create reusable attributes for item variants and setup metadata." title="Create Attribute">
            <form className="grid gap-3 md:grid-cols-4" onSubmit={submit}>
                {state.message ? <div className="rounded-lg border border-red-100 bg-red-50 px-3 py-2 text-sm text-red-700 md:col-span-4">{state.message}</div> : null}
                <div><Input name="name" placeholder="Size" /><FieldError message={field(state.errors, 'name')} /></div>
                <Select defaultValue="SELECT" name="type" options={[{ label: 'Select', value: 'SELECT' }, { label: 'Text', value: 'TEXT' }, { label: 'Number', value: 'NUMBER' }, { label: 'Boolean', value: 'BOOLEAN' }]} />
                <label className="flex items-center gap-2 text-sm font-semibold text-slate-700"><Checkbox name="is_required" /> Required</label>
                <Button disabled={state.saving} type="submit" variant="blue">{state.saving ? 'Creating...' : 'Create Attribute'}</Button>
            </form>
        </FormSection>
    );
}

export function ItemVariantCreateForm({ item, onSaved }: FormProps) {
    const state = useFormState();

    if (!item) return null;
    const currentItem = item;

    async function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        state.setErrors({});
        state.setMessage('');
        state.setSaving(true);
        const formData = new FormData(event.currentTarget);
        try {
            await itemApi.createVariant({
                isActive: formData.get('is_active') === 'on',
                isDefault: formData.get('is_default') === 'on',
                itemId: currentItem.id,
                name: text(formData, 'name'),
                sku: text(formData, 'sku'),
            });
            event.currentTarget.reset();
            onSaved();
        } catch (error) {
            state.fail(error, 'Unable to create variant.');
        } finally {
            state.setSaving(false);
        }
    }

    return (
        <FormSection description="Add SKU-level item variants saved by the Item API." title="Add Variant">
            <form className="grid gap-3 md:grid-cols-5" onSubmit={submit}>
                {state.message ? <div className="rounded-lg border border-red-100 bg-red-50 px-3 py-2 text-sm text-red-700 md:col-span-5">{state.message}</div> : null}
                <div><Input name="sku" placeholder={`${currentItem.code}-VAR`} /><FieldError message={field(state.errors, 'sku')} /></div>
                <div><Input name="name" placeholder="Variant name" /><FieldError message={field(state.errors, 'name')} /></div>
                <label className="flex items-center gap-2 text-sm font-semibold text-slate-700"><Checkbox name="is_default" /> Default</label>
                <label className="flex items-center gap-2 text-sm font-semibold text-slate-700"><Checkbox defaultChecked name="is_active" /> Active</label>
                <Button disabled={state.saving} type="submit" variant="blue">{state.saving ? 'Adding...' : 'Add Variant'}</Button>
            </form>
        </FormSection>
    );
}

export function ItemComboComponentCreateForm({ item, onSaved }: FormProps) {
    const [items, setItems] = useState<Item[]>([]);
    const [uoms, setUoms] = useState<UomOption[]>([]);
    const [lookupLoading, setLookupLoading] = useState('');
    const state = useFormState();

    async function loadItems() {
        if (items.length > 0) return;
        setLookupLoading('items');
        try {
            const itemRows = await itemApi.lookupItems({ perPage: 25, status: 'active' });
            setItems(itemRows.data);
        } catch (error) {
            state.setMessage(error instanceof Error ? error.message : 'Unable to load component items.');
        } finally {
            setLookupLoading('');
        }
    }

    async function loadUoms() {
        if (uoms.length > 0) return;
        setLookupLoading('uoms');
        try {
            const uomRows = await itemApi.listUoms();
            setUoms(uomRows.data);
        } catch (error) {
            state.setMessage(error instanceof Error ? error.message : 'Unable to load UOM options.');
        } finally {
            setLookupLoading('');
        }
    }

    if (!item) return null;
    const currentItem = item;

    async function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        state.setErrors({});
        state.setMessage('');
        state.setSaving(true);
        const formData = new FormData(event.currentTarget);
        try {
            await itemApi.createComboComponent({
                comboItemId: currentItem.id,
                componentItemId: text(formData, 'component_item_id'),
                quantity: text(formData, 'quantity'),
                uomId: text(formData, 'uom_id'),
            });
            event.currentTarget.reset();
            onSaved();
        } catch (error) {
            state.fail(error, 'Unable to add combo component.');
        } finally {
            state.setSaving(false);
        }
    }

    return (
        <FormSection description="Add persisted combo components. Circular references and invalid UOMs are rejected by the backend." title="Add Combo Component">
            <form className="grid gap-3 md:grid-cols-4" onSubmit={submit}>
                {state.message ? <div className="rounded-lg border border-red-100 bg-red-50 px-3 py-2 text-sm text-red-700 md:col-span-4">{state.message}</div> : null}
                <Select name="component_item_id" onFocus={() => void loadItems()} onMouseDown={() => void loadItems()} options={items.filter((row) => row.id !== currentItem.id).map((row) => ({ label: `${row.code} - ${row.name}`, value: row.id }))} placeholder={lookupLoading === 'items' ? 'Loading items...' : 'Component item'} />
                <Input defaultValue="1" min="0.0001" name="quantity" step="0.0001" type="number" />
                <Select name="uom_id" onFocus={() => void loadUoms()} onMouseDown={() => void loadUoms()} options={uoms.map((uom) => ({ label: uom.label, value: uom.id }))} placeholder={lookupLoading === 'uoms' ? 'Loading UOMs...' : 'UOM'} />
                <Button disabled={state.saving} type="submit" variant="blue">{state.saving ? 'Adding...' : 'Add Component'}</Button>
            </form>
        </FormSection>
    );
}

export function ItemIdentifierCreateForm({ item, onSaved }: FormProps) {
    const state = useFormState();

    if (!item) return null;
    const currentItem = item;

    async function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        state.setErrors({});
        state.setMessage('');
        state.setSaving(true);
        const formData = new FormData(event.currentTarget);
        try {
            await itemApi.createIdentifier({
                format: text(formData, 'format'),
                isActive: formData.get('is_active') === 'on',
                isPrimary: formData.get('is_primary') === 'on',
                itemId: currentItem.id,
                technology: text(formData, 'technology'),
                value: text(formData, 'value'),
            });
            event.currentTarget.reset();
            onSaved();
        } catch (error) {
            state.fail(error, 'Unable to create identifier.');
        } finally {
            state.setSaving(false);
        }
    }

    return (
        <FormSection description="Add real barcode, QR, RFID, or internal item identifiers." title="Add Identifier">
            <form className="grid gap-3 md:grid-cols-5" onSubmit={submit}>
                {state.message ? <div className="rounded-lg border border-red-100 bg-red-50 px-3 py-2 text-sm text-red-700 md:col-span-5">{state.message}</div> : null}
                <Input name="value" placeholder="Identifier value" />
                <Select defaultValue="barcode_1d" name="technology" options={[{ label: 'Barcode', value: 'barcode_1d' }, { label: 'QR Code', value: 'qr_code' }, { label: 'RFID', value: 'rfid_uhf' }, { label: 'Internal', value: 'internal' }]} />
                <Input name="format" placeholder="code128, ean13, qr" />
                <label className="flex items-center gap-2 text-sm font-semibold text-slate-700"><Checkbox name="is_primary" /> Primary</label>
                <Button disabled={state.saving} type="submit" variant="blue">{state.saving ? 'Adding...' : 'Add Identifier'}</Button>
            </form>
        </FormSection>
    );
}

export type ItemSubResourceRows = {
    attributes?: ItemAttribute[];
    categories?: ItemCategory[];
    comboComponents?: ItemComboComponent[];
    identifiers?: ItemIdentifier[];
    variants?: ItemVariant[];
};
