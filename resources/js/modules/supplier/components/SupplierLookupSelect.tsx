import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { searchSuppliers } from '../supplierApi';
import type { SupplierSummary } from '../supplierTypes';

const DEFAULT_LABEL = 'Supplier';

const formatSupplier = (supplier: SupplierSummary) =>
    [supplier.code, supplier.name].filter(Boolean).join(' - ');

export function SupplierLookupSelect({
    value,
    onChange,
    error,
    disabled,
    required,
    label = DEFAULT_LABEL,
}: {
    value: SupplierSummary | null;
    onChange: (supplier: SupplierSummary | null) => void;
    error?: string;
    disabled?: boolean;
    required?: boolean;
    label?: string;
}) {
    return (
        <GenericLookupSelect
            label={label}
            value={value}
            onChange={onChange}
            search={searchSuppliers}
            formatLabel={formatSupplier}
            error={error}
            disabled={disabled}
            required={required}
        />
    );
}
