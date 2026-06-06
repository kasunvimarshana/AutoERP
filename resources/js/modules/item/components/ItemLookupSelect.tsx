import { searchItems } from '../itemApi';
import type { ItemSummary } from '../itemTypes';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';

const formatItem = (item: ItemSummary) => `${item.code} - ${item.name}`;
const searchActiveItems = (query: string, signal: AbortSignal) => searchItems(query, signal);

export function ItemLookupSelect({ value, onChange, excludeId, error, label = 'Item' }: {
    value: ItemSummary | null;
    onChange: (item: ItemSummary | null) => void;
    excludeId?: number | null;
    error?: string;
    label?: string;
}) {
    return <GenericLookupSelect label={label} value={value} onChange={onChange} search={searchActiveItems} formatLabel={formatItem} excludeId={excludeId} error={error} />;
}
