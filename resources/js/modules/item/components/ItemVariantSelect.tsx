import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import type { LookupLoadParams, LookupResult } from '@/shared/types/lookup';
import { listItemVariants } from '../itemApi';
import type { ItemVariant } from '../itemTypes';

const formatVariant = (variant: ItemVariant) => `${variant.code} - ${variant.name}`;

export function ItemVariantSelect({ itemId, value, onChange, error }: {
    itemId: number;
    value: ItemVariant | null;
    onChange: (variant: ItemVariant | null) => void;
    error?: string;
}) {
    const search = async ({ search: term, page, perPage, signal }: LookupLoadParams): Promise<LookupResult<ItemVariant>> => {
        const result = await listItemVariants(itemId, { search: term, page, per_page: perPage, is_active: 1 }, signal);
        return { data: result.data, meta: result.meta };
    };

    return <GenericLookupSelect
        label="Variant (optional)"
        value={value}
        onChange={onChange}
        search={search}
        formatLabel={formatVariant}
        error={error}
        placeholder="All variants or search a variant"
        loadOnOpen
        minSearchLength={0}
    />;
}
