import type { UseFormReturn } from 'react-hook-form';
import { Button } from '../../../components/ui/Button';
import { ActionBar } from '../../../components/forms/ActionBar';
import { Checkbox } from '../../../components/forms/Checkbox';
import { FormField } from '../../../components/forms/FormField';
import { FormGrid } from '../../../components/forms/FormGrid';
import { Input } from '../../../components/forms/Input';
import { Select } from '../../../components/forms/Select';
import type { ProductIdentifierFormInput, ProductIdentifierFormValues } from '../schemas';

type ProductIdentifierFormProps = {
    form: UseFormReturn<ProductIdentifierFormInput, unknown, ProductIdentifierFormValues>;
    onSubmit: (values: ProductIdentifierFormValues) => void | Promise<void>;
    isSubmitting: boolean;
    mode: 'create' | 'edit';
    onCancel?: () => void;
};

export function ProductIdentifierForm({ form, isSubmitting, mode, onCancel, onSubmit }: ProductIdentifierFormProps) {
    const {
        formState: { errors },
        handleSubmit,
        register,
    } = form;

    return (
        <form onSubmit={handleSubmit(onSubmit)}>
            <div className="space-y-5">
                <FormGrid>
                    <FormField error={errors.technology?.message} label="Technology" required>
                        <Select {...register('technology')} error={errors.technology?.message}>
                            <option value="barcode_1d">Barcode 1D</option>
                            <option value="barcode_2d">Barcode 2D</option>
                            <option value="qr_code">QR Code</option>
                            <option value="rfid_hf">RFID HF</option>
                            <option value="rfid_uhf">RFID UHF</option>
                            <option value="nfc">NFC</option>
                            <option value="gs1_epc">GS1 EPC</option>
                            <option value="custom">Custom</option>
                        </Select>
                    </FormField>

                    <FormField error={errors.format?.message} label="Format">
                        <Select {...register('format')} error={errors.format?.message}>
                            <option value="">Select format</option>
                            <option value="ean13">EAN-13</option>
                            <option value="ean8">EAN-8</option>
                            <option value="upc_a">UPC-A</option>
                            <option value="code128">Code 128</option>
                            <option value="code39">Code 39</option>
                            <option value="qr">QR</option>
                            <option value="datamatrix">Data Matrix</option>
                            <option value="gs1_128">GS1-128</option>
                            <option value="epc_sgtin">EPC SGTIN</option>
                            <option value="other">Other</option>
                        </Select>
                    </FormField>

                    <FormField error={errors.value?.message} label="Identifier Value" required>
                        <Input placeholder="9551234567890" {...register('value')} error={errors.value?.message} />
                    </FormField>

                    <FormField error={errors.gs1_company_prefix?.message} label="GS1 Company Prefix">
                        <Input placeholder="1234567" {...register('gs1_company_prefix')} error={errors.gs1_company_prefix?.message} />
                    </FormField>

                    <FormField error={errors.is_primary?.message} label="Primary Identifier">
                        <Checkbox
                            className="border-stone-200/80 bg-stone-50/70"
                            description="Use this as the main product identifier shown across the workspace."
                            label="Primary identifier"
                            {...register('is_primary')}
                        />
                    </FormField>

                    <FormField error={errors.is_active?.message} label="Status">
                        <Checkbox
                            className="border-stone-200/80 bg-stone-50/70"
                            description="Inactive identifiers are kept for traceability but hidden from active scans."
                            label="Identifier is active"
                            {...register('is_active')}
                        />
                    </FormField>
                </FormGrid>

                <ActionBar>
                    {onCancel ? (
                        <Button onClick={onCancel} type="button" variant="secondary">
                            Cancel
                        </Button>
                    ) : null}
                    <Button type="submit">{isSubmitting ? 'Saving...' : mode === 'create' ? 'Add Identifier' : 'Save Identifier'}</Button>
                </ActionBar>
            </div>
        </form>
    );
}
