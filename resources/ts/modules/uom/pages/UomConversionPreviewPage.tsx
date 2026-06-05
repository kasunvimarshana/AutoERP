import { FormEvent, useEffect, useState } from 'react';
import { ApiError } from '../../../services/api/apiErrors';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { FieldError } from '../../../shared/components/forms/FieldError';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { Button } from '../../../shared/components/ui/Button';
import { EmptyState } from '../../../shared/components/ui/EmptyState';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { UomConversionPreviewPanel } from '../components/UomComponents';
import { uomApi } from '../services/uomApi';
import type { UomConversionPreview, UomLookupOption, UomUnit } from '../types/uom.types';

export function UomConversionPreviewPage() {
    const [units, setUnits] = useState<UomUnit[]>([]);
    const [items, setItems] = useState<UomLookupOption[]>([]);
    const [preview, setPreview] = useState<UomConversionPreview | undefined>();
    const [errors, setErrors] = useState<Record<string, string[]>>({});
    const [formError, setFormError] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [isSubmitting, setIsSubmitting] = useState(false);

    useEffect(() => {
        let mounted = true;
        Promise.all([uomApi.listUnits(), uomApi.listItemOptions()])
            .then(([unitResponse, itemResponse]) => {
                if (mounted) {
                    setUnits(unitResponse.data);
                    setItems(itemResponse.data);
                }
            })
            .catch((error: unknown) => setFormError(error instanceof Error ? error.message : 'Unable to load conversion preview lookups.'))
            .finally(() => { if (mounted) setIsLoading(false); });

        return () => { mounted = false; };
    }, []);

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setErrors({});
        setFormError('');
        setIsSubmitting(true);
        const formData = new FormData(event.currentTarget);

        try {
            const response = await uomApi.previewConversion({
                fromUnitId: String(formData.get('from_unit_id') ?? ''),
                itemId: String(formData.get('item_id') ?? '') || undefined,
                quantity: String(formData.get('quantity') ?? '1'),
                toUnitId: String(formData.get('to_unit_id') ?? ''),
            });
            setPreview(response);
        } catch (error) {
            if (error instanceof ApiError) {
                setErrors(error.errors);
                setFormError(error.message);
            } else {
                setFormError('Unable to preview conversion.');
            }
        } finally {
            setIsSubmitting(false);
        }
    }

    if (isLoading) {
        return <EmptyState description="Loading unit selectors..." title="Loading conversion preview" />;
    }

    return (
        <div className="space-y-6">
            <PageHeader eyebrow="UOM" subtitle="Request a backend conversion preview." title="Conversion Preview" />
            {formError ? <div className="rounded-lg border border-red-100 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{formError}</div> : null}
            <form className="grid gap-5 xl:grid-cols-[1fr_340px]" onSubmit={handleSubmit}>
                <FormSection title="Preview Input">
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2"><Select name="from_unit_id" options={[{ label: 'From unit', value: '' }, ...units.map((unit) => ({ label: `${unit.code} - ${unit.name}`, value: unit.id }))]} /><FieldError message={errors.from_uom_id?.[0]} /></div>
                        <div className="space-y-2"><Select name="to_unit_id" options={[{ label: 'To unit', value: '' }, ...units.map((unit) => ({ label: `${unit.code} - ${unit.name}`, value: unit.id }))]} /><FieldError message={errors.to_uom_id?.[0]} /></div>
                        <div className="space-y-2"><Input defaultValue="1" min="0" name="quantity" type="number" /><FieldError message={errors.quantity?.[0]} /></div>
                        <Select name="item_id" options={[{ label: 'Optional item', value: '' }, ...items.map((item) => ({ label: item.label, value: item.id }))]} />
                    </div>
                    <div className="mt-5 flex justify-end"><Button disabled={isSubmitting} type="submit" variant="blue">{isSubmitting ? 'Previewing...' : 'Preview Conversion'}</Button></div>
                </FormSection>
                <UomConversionPreviewPanel preview={preview} />
            </form>
        </div>
    );
}
