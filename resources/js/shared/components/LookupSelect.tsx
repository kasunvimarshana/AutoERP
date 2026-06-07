import { useCallback } from 'react';
import type { NamedResource } from '@/shared/types/common';
import { GenericLookupSelect } from './GenericLookupSelect';

export function LookupSelect({ label, value, onChange, search, placeholder = 'Search...', error }: {
    label: string;
    value: NamedResource | null;
    onChange: (resource: NamedResource | null) => void;
    search: (query: string, signal: AbortSignal) => Promise<NamedResource[]>;
    placeholder?: string;
    error?: string;
}) {
    const formatLabel = useCallback((resource: NamedResource) =>
        resource.code ? `${resource.code} - ${resource.name}` : resource.name, []);

    return <GenericLookupSelect label={label} value={value} onChange={onChange} search={search} formatLabel={formatLabel} placeholder={placeholder} error={error} />;
}
