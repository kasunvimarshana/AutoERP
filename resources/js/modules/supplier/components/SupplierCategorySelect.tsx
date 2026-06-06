import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { searchSupplierCategories } from '../supplierApi';
import type { SupplierCategory } from '../supplierTypes';

const formatCategory = (category: SupplierCategory) => `${category.code} - ${category.name}`;

export function SupplierCategorySelect({ value, onChange, error }: {
    value: SupplierCategory | null;
    onChange: (category: SupplierCategory | null) => void;
    error?: string;
}) {
    return <GenericLookupSelect label="Category" value={value} onChange={onChange} search={searchSupplierCategories} formatLabel={formatCategory} error={error} />;
}
