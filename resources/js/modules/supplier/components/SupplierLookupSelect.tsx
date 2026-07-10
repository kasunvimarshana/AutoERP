import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { searchSuppliers } from '../supplierApi';
import type { SupplierSummary } from '../supplierTypes';

const formatSupplier = (supplier: SupplierSummary) =>
    [supplier.code, supplier.name].filter(Boolean).join(' - ');

export function SupplierLookupSelect({ value, onChange, error, disabled, required }: {
    value: SupplierSummary | null;
    onChange: (supplier: SupplierSummary | null) => void;
    error?: string;
    disabled?: boolean;
    required?: boolean;
}) {
    return <GenericLookupSelect label="Supplier" value={value} onChange={onChange} search={searchSuppliers} formatLabel={formatSupplier} error={error} disabled={disabled} required={required} />;
}
