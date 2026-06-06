import { searchItemCategories } from '../itemApi';
import type { NamedResource } from '@/shared/types/common';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';

const formatCategory = (category: NamedResource) => `${category.code} - ${category.name}`;
const searchCategories = (query: string, signal: AbortSignal): Promise<NamedResource[]> => searchItemCategories(query, signal);

export function ItemCategorySelect({ value, onChange, error }: {
    value: NamedResource | null;
    onChange: (category: NamedResource | null) => void;
    error?: string;
}) {
    return <GenericLookupSelect label="Category" value={value} onChange={onChange} search={searchCategories} formatLabel={formatCategory} error={error} />;
}
