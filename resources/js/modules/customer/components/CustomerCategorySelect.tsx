import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { searchCustomerCategories } from '../customerApi';
import type { CustomerCategory } from '../customerTypes';

const formatCategory = (category: CustomerCategory) => `${category.code} - ${category.name}`;

export function CustomerCategorySelect({ value, onChange, error }: {
    value: CustomerCategory | null;
    onChange: (category: CustomerCategory | null) => void;
    error?: string;
}) {
    return <GenericLookupSelect label="Category" value={value} onChange={onChange} search={searchCustomerCategories} formatLabel={formatCategory} error={error} />;
}
