import { FormEvent, useState } from 'react';
import { PageHeader } from '../../../shared/components/business/PageHeader';
import { FormSection } from '../../../shared/components/forms/FormSection';
import { Button } from '../../../shared/components/ui/Button';
import { Input } from '../../../shared/components/ui/Input';
import { Select } from '../../../shared/components/ui/Select';
import { UomConversionPreviewPanel } from '../components/UomComponents';
import { uomUnits } from '../mock/uomMock';
import { uomApi } from '../services/uomApi';
import type { UomConversionPreview } from '../types/uom.types';

export function UomConversionPreviewPage() {
    const [preview, setPreview] = useState<UomConversionPreview | undefined>();
    const [isLoading, setIsLoading] = useState(false);

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setIsLoading(true);
        const formData = new FormData(event.currentTarget);
        const response = await uomApi.previewConversion({
            fromUnitId: String(formData.get('from_unit_id') ?? ''),
            itemId: String(formData.get('item_id') ?? '') || undefined,
            quantity: String(formData.get('quantity') ?? '1'),
            toUnitId: String(formData.get('to_unit_id') ?? ''),
        });
        setPreview(response);
        setIsLoading(false);
    }

    return (
        <div className="space-y-6">
            <PageHeader eyebrow="UOM" subtitle="Request a backend/mock conversion preview. The frontend does not apply factors." title="Conversion Preview" />
            <form className="grid gap-5 xl:grid-cols-[1fr_340px]" onSubmit={handleSubmit}>
                <FormSection title="Preview Input" description="Quantity, from unit, to unit, and optional item are sent to the conversion service.">
                    <div className="grid gap-4 md:grid-cols-2">
                        <Select name="from_unit_id" options={uomUnits.map((unit) => ({ label: unit.code, value: unit.id }))} placeholder="From unit" />
                        <Select name="to_unit_id" options={uomUnits.map((unit) => ({ label: unit.code, value: unit.id }))} placeholder="To unit" />
                        <Input defaultValue="1" min="0" name="quantity" placeholder="Quantity" type="number" />
                        <Input name="item_id" placeholder="Optional item id" />
                    </div>
                    <div className="mt-5 flex justify-end"><Button disabled={isLoading} type="submit" variant="blue">{isLoading ? 'Previewing...' : 'Preview Conversion'}</Button></div>
                </FormSection>
                <UomConversionPreviewPanel preview={preview} />
            </form>
        </div>
    );
}
