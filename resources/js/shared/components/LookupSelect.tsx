import { useCallback, type ReactNode } from 'react';
import type { NamedResource } from '@/shared/types/common';
import type { LookupBehaviorOptions, LookupLoader } from '@/shared/types/lookup';
import { GenericLookupSelect } from './GenericLookupSelect';

export interface LookupSelectProps<T extends NamedResource = NamedResource> extends LookupBehaviorOptions {
    label: string;
    value: T | null;
    onChange: (resource: T | null) => void;
    search: LookupLoader<T>;
    renderOption?: (resource: T, state: { active: boolean; selected: boolean }) => ReactNode;
    placeholder?: string;
    error?: string;
    excludeId?: number | null;
    excludeIds?: Array<number | string>;
    disabled?: boolean;
    required?: boolean;
    id?: string;
    recentResultsKey?: string;
}

export function LookupSelect<T extends NamedResource = NamedResource>({
    label,
    value,
    onChange,
    search,
    renderOption,
    placeholder = 'Search...',
    error,
    excludeId,
    excludeIds,
    disabled,
    required,
    id,
    recentResultsKey,
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
            renderOption={renderOption}
            placeholder={placeholder}
            error={error}
            excludeId={excludeId}
            excludeIds={excludeIds}
            disabled={disabled}
            required={required}
            recentResultsKey={recentResultsKey}
            minSearchLength={minSearchLength}
            loadOnOpen={loadOnOpen}
            perPage={perPage}
            debounceMs={debounceMs}
        />
    );
}
