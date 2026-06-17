import { useCallback } from 'react';
import type { NamedResource } from '@/shared/types/common';
import type { LookupBehaviorOptions, LookupLoader } from '@/shared/types/lookup';
import { GenericLookupSelect } from './GenericLookupSelect';

export interface LookupSelectProps<T extends NamedResource = NamedResource> extends LookupBehaviorOptions {
    label: string;
    value: T | null;
    onChange: (resource: T | null) => void;
    search: LookupLoader<T>;
    placeholder?: string;
    error?: string;
    excludeId?: number | null;
    disabled?: boolean;
    required?: boolean;
    id?: string;
}

export function LookupSelect<T extends NamedResource = NamedResource>({
    label,
    value,
    onChange,
    search,
    placeholder = 'Search...',
    error,
    excludeId,
    disabled,
    required,
    id,
    minSearchLength,
    loadOnOpen,
    perPage,
    debounceMs,
}: LookupSelectProps<T>) {
    const formatLabel = useCallback((resource: NamedResource) =>
        resource.code ? `${resource.code} - ${resource.name}` : resource.name, []);

    return (
        <GenericLookupSelect
            id={id}
            label={label}
            value={value}
            onChange={onChange}
            search={search}
            formatLabel={formatLabel}
            placeholder={placeholder}
            error={error}
            excludeId={excludeId}
            disabled={disabled}
            required={required}
            minSearchLength={minSearchLength}
            loadOnOpen={loadOnOpen}
            perPage={perPage}
            debounceMs={debounceMs}
        />
    );
}
