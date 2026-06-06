import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { searchSuppliers } from '../supplierApi';
import type { SupplierSummary } from '../supplierTypes';

const formatSupplier = (supplier: SupplierSummary) => `${supplier.code} - ${supplier.name}`;
const searchActive = (query: string, signal: AbortSignal) => searchSuppliers(query, signal);

export function SupplierLookupSelect({ value, onChange, error }: {
    value: SupplierSummary | null;
    onChange: (supplier: SupplierSummary | null) => void;
    error?: string;
}) {
    return <GenericLookupSelect label="Supplier" value={value} onChange={onChange} search={searchActive} formatLabel={formatSupplier} error={error} />;
}
