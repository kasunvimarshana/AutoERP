import { searchItemBrands } from '../itemApi';
import type { NamedResource } from '@/shared/types/common';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';

const formatBrand = (brand: NamedResource) => `${brand.code} - ${brand.name}`;
const searchBrands = (query: string, signal: AbortSignal): Promise<NamedResource[]> => searchItemBrands(query, signal);

export function ItemBrandSelect({ value, onChange, error }: {
    value: NamedResource | null;
    onChange: (brand: NamedResource | null) => void;
    error?: string;
}) {
    return <GenericLookupSelect label="Brand" value={value} onChange={onChange} search={searchBrands} formatLabel={formatBrand} error={error} />;
}
