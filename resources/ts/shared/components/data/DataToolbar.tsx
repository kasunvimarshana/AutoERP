import { useEffect, useMemo, useState, type ReactNode } from 'react';
import { Badge } from '../ui/Badge';
import { Button } from '../ui/Button';
import { Checkbox } from '../ui/Checkbox';
import { Input } from '../ui/Input';
import { Select } from '../ui/Select';

export type DataToolbarFilterType =
    | 'boolean'
    | 'date'
    | 'date-range'
    | 'entity'
    | 'multi-select'
    | 'number'
    | 'select'
    | 'status'
    | 'text';

export type DataToolbarFilterOption = {
    label: string;
    value: string;
    description?: string;
};

export type DataToolbarFilterConfig = {
    id: string;
    label: string;
    type: DataToolbarFilterType;
    options?: DataToolbarFilterOption[];
    placeholder?: string;
    disabled?: boolean;
    disabledReason?: string;
};

export type DataToolbarFilterValue = boolean | string | string[] | undefined;
export type DataToolbarFilterValues = Record<string, DataToolbarFilterValue>;

export type DataToolbarSavedView = {
    id: string;
    name: string;
};

export type DataToolbarChip = {
    id: string;
    label: string;
    value: string;
};

type DataToolbarProps = {
    actions?: ReactNode;
    activeFilterChips?: DataToolbarChip[];
    disabled?: boolean;
    filterValues?: DataToolbarFilterValues;
    filters?: DataToolbarFilterConfig[];
    isLoading?: boolean;
    onFilterChange?: (filterId: string, value: DataToolbarFilterValue) => void;
    onRemoveFilter?: (filterId: string) => void;
    onResetFilters?: () => void;
    onSaveView?: () => void;
    onSavedViewChange?: (viewId: string) => void;
    onSearchChange?: (value: string) => void;
    savedViews?: DataToolbarSavedView[];
    savedViewsDisabledReason?: string;
    searchPlaceholder?: string;
    searchValue?: string;
    secondaryActions?: ReactNode;
    selectedSavedView?: string;
};

const debounceMs = 300;

export function DataToolbar({
    actions,
    activeFilterChips,
    disabled = false,
    filterValues = {},
    filters = [],
    isLoading = false,
    onFilterChange,
    onRemoveFilter,
    onResetFilters,
    onSaveView,
    onSavedViewChange,
    onSearchChange,
    savedViews = [],
    savedViewsDisabledReason = 'Saved views are not configured for this list yet.',
    searchPlaceholder = 'Search records...',
    searchValue = '',
    secondaryActions,
    selectedSavedView = '',
}: DataToolbarProps) {
    const [searchDraft, setSearchDraft] = useState(searchValue);
    const [filtersOpen, setFiltersOpen] = useState(false);

    useEffect(() => {
        setSearchDraft(searchValue);
    }, [searchValue]);

    useEffect(() => {
        const timer = window.setTimeout(() => {
            if (searchDraft !== searchValue) {
                onSearchChange?.(searchDraft);
            }
        }, debounceMs);

        return () => window.clearTimeout(timer);
    }, [onSearchChange, searchDraft, searchValue]);

    const chips = useMemo(
        () => activeFilterChips ?? buildFilterChips(filters, filterValues),
        [activeFilterChips, filterValues, filters],
    );
    const activeFilterCount = chips.length;
    const hasSavedViews = savedViews.length > 0 && Boolean(onSavedViewChange);

    function clearSearch(): void {
        setSearchDraft('');
        onSearchChange?.('');
    }

    function resetFilters(): void {
        onResetFilters?.();
        setFiltersOpen(false);
    }

    return (
        <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <div className="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                <div className="flex min-w-0 flex-1 flex-col gap-3 md:flex-row md:items-center">
                    <div className="relative w-full md:max-w-md">
                        <label className="sr-only" htmlFor="data-toolbar-search">Search</label>
                        <Input
                            disabled={disabled}
                            id="data-toolbar-search"
                            onChange={(event) => setSearchDraft(event.target.value)}
                            placeholder={searchPlaceholder}
                            type="search"
                            value={searchDraft}
                        />
                        {searchDraft ? (
                            <button
                                className="absolute right-2 top-1/2 rounded px-2 py-1 text-xs font-semibold text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                                disabled={disabled}
                                onClick={clearSearch}
                                type="button"
                            >
                                Clear
                            </button>
                        ) : null}
                    </div>

                    {filters.length > 0 ? (
                        <div className="relative">
                            <Button
                                aria-expanded={filtersOpen}
                                className="w-full md:w-auto"
                                disabled={disabled}
                                onClick={() => setFiltersOpen((open) => !open)}
                                variant="secondary"
                            >
                                Filters{activeFilterCount > 0 ? ` (${activeFilterCount})` : ''}
                            </Button>
                            {filtersOpen ? (
                                <div className="absolute left-0 z-30 mt-2 w-[min(92vw,48rem)] rounded-lg border border-slate-200 bg-white p-4 shadow-xl">
                                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                        {filters.map((filter) => (
                                            <FilterControl
                                                disabled={disabled || filter.disabled}
                                                filter={filter}
                                                key={filter.id}
                                                onChange={(value) => onFilterChange?.(filter.id, value)}
                                                value={filterValues[filter.id]}
                                            />
                                        ))}
                                    </div>
                                    <div className="mt-4 flex flex-col gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
                                        <p className="text-xs text-slate-500">
                                            {activeFilterCount > 0 ? `${activeFilterCount} active filter${activeFilterCount === 1 ? '' : 's'}` : 'No active filters'}
                                        </p>
                                        <div className="flex gap-2">
                                            <Button disabled={disabled || activeFilterCount === 0} onClick={resetFilters} variant="secondary">
                                                Reset
                                            </Button>
                                            <Button onClick={() => setFiltersOpen(false)} variant="primary">
                                                Done
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            ) : null}
                        </div>
                    ) : null}

                    <SavedViewsControl
                        disabled={disabled}
                        disabledReason={savedViewsDisabledReason}
                        hasSavedViews={hasSavedViews}
                        onSaveView={onSaveView}
                        onSavedViewChange={onSavedViewChange}
                        savedViews={savedViews}
                        selectedSavedView={selectedSavedView}
                    />
                </div>

                <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
                    {isLoading ? <span className="text-sm text-slate-500">Loading...</span> : null}
                    {secondaryActions}
                    {actions}
                </div>
            </div>

            {chips.length > 0 ? (
                <div className="mt-3 flex flex-wrap gap-2 border-t border-slate-100 pt-3">
                    {chips.map((chip) => (
                        <Badge className="gap-2" key={chip.id}>
                            <span>{chip.label}: {chip.value}</span>
                            <button
                                aria-label={`Remove ${chip.label} filter`}
                                className="rounded-full px-1 text-slate-500 transition hover:bg-slate-200 hover:text-slate-900"
                                disabled={disabled}
                                onClick={() => {
                                    if (onRemoveFilter) {
                                        onRemoveFilter(chip.id);
                                        return;
                                    }

                                    onFilterChange?.(chip.id, undefined);
                                }}
                                type="button"
                            >
                                x
                            </button>
                        </Badge>
                    ))}
                </div>
            ) : null}
        </div>
    );
}

function SavedViewsControl({
    disabled,
    disabledReason,
    hasSavedViews,
    onSaveView,
    onSavedViewChange,
    savedViews,
    selectedSavedView,
}: {
    disabled: boolean;
    disabledReason: string;
    hasSavedViews: boolean;
    onSaveView?: () => void;
    onSavedViewChange?: (viewId: string) => void;
    savedViews: DataToolbarSavedView[];
    selectedSavedView: string;
}) {
    if (hasSavedViews) {
        return (
            <div className="flex gap-2">
                <label className="sr-only" htmlFor="data-toolbar-saved-view">Saved view</label>
                <Select
                    className="min-w-44"
                    disabled={disabled}
                    id="data-toolbar-saved-view"
                    onChange={(event) => onSavedViewChange?.(event.target.value)}
                    value={selectedSavedView}
                >
                    <option value="">Saved views</option>
                    {savedViews.map((view) => <option key={view.id} value={view.id}>{view.name}</option>)}
                </Select>
                {onSaveView ? <Button disabled={disabled} onClick={onSaveView} variant="secondary">Save</Button> : null}
            </div>
        );
    }

    return (
        <Button className="w-full md:w-auto" disabled title={disabledReason} variant="secondary">
            Saved views unavailable
        </Button>
    );
}

function FilterControl({
    disabled,
    filter,
    onChange,
    value,
}: {
    disabled?: boolean;
    filter: DataToolbarFilterConfig;
    onChange: (value: DataToolbarFilterValue) => void;
    value: DataToolbarFilterValue;
}) {
    const inputId = `data-toolbar-filter-${filter.id}`;

    return (
        <label className="space-y-2 text-sm" htmlFor={inputId}>
            <span className="font-semibold text-slate-700">{filter.label}</span>
            {renderFilterInput(inputId, filter, value, onChange, disabled)}
            {filter.disabled && filter.disabledReason ? <span className="block text-xs text-slate-500">{filter.disabledReason}</span> : null}
        </label>
    );
}

function renderFilterInput(
    inputId: string,
    filter: DataToolbarFilterConfig,
    value: DataToolbarFilterValue,
    onChange: (value: DataToolbarFilterValue) => void,
    disabled?: boolean,
) {
    if (filter.type === 'boolean') {
        return (
            <span className="flex h-11 items-center gap-2 rounded-lg border border-slate-200 bg-slate-50/60 px-3">
                <Checkbox
                    checked={value === true}
                    disabled={disabled}
                    id={inputId}
                    onChange={(event) => onChange(event.target.checked ? true : undefined)}
                />
                <span className="text-slate-600">{filter.placeholder ?? filter.label}</span>
            </span>
        );
    }

    if (filter.type === 'select' || filter.type === 'status' || filter.type === 'entity') {
        return (
            <Select
                disabled={disabled}
                id={inputId}
                onChange={(event) => onChange(event.target.value || undefined)}
                value={stringValue(value)}
            >
                <option value="">{filter.placeholder ?? `Any ${filter.label.toLowerCase()}`}</option>
                {filter.options?.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
            </Select>
        );
    }

    if (filter.type === 'multi-select') {
        const values = Array.isArray(value) ? value : [];
        return (
            <select
                className="min-h-28 w-full rounded-lg border border-slate-200 bg-slate-50/60 px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:bg-white focus:ring-4 focus:ring-slate-100"
                disabled={disabled}
                id={inputId}
                multiple
                onChange={(event) => onChange(Array.from(event.target.selectedOptions).map((option) => option.value))}
                value={values}
            >
                {filter.options?.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
            </select>
        );
    }

    if (filter.type === 'date-range') {
        const [from = '', to = ''] = Array.isArray(value) ? value : [];
        return (
            <div className="grid grid-cols-2 gap-2">
                <Input disabled={disabled} id={`${inputId}-from`} onChange={(event) => onChange([event.target.value, to].filter(Boolean))} type="date" value={from} />
                <Input disabled={disabled} id={`${inputId}-to`} onChange={(event) => onChange([from, event.target.value].filter(Boolean))} type="date" value={to} />
            </div>
        );
    }

    return (
        <Input
            disabled={disabled}
            id={inputId}
            onChange={(event) => onChange(event.target.value || undefined)}
            placeholder={filter.placeholder}
            type={filter.type === 'date' ? 'date' : filter.type === 'number' ? 'number' : 'text'}
            value={stringValue(value)}
        />
    );
}

function buildFilterChips(filters: DataToolbarFilterConfig[], filterValues: DataToolbarFilterValues): DataToolbarChip[] {
    return filters.flatMap((filter) => {
        const value = filterValues[filter.id];

        if (value === undefined || value === '' || (Array.isArray(value) && value.length === 0) || value === false) {
            return [];
        }

        if (value === true) {
            return [{ id: filter.id, label: filter.label, value: 'Yes' }];
        }

        if (Array.isArray(value)) {
            return [{
                id: filter.id,
                label: filter.label,
                value: value.map((entry) => optionLabel(filter, entry)).join(', '),
            }];
        }

        return [{ id: filter.id, label: filter.label, value: optionLabel(filter, value) }];
    });
}

function optionLabel(filter: DataToolbarFilterConfig, value: string): string {
    return filter.options?.find((option) => option.value === value)?.label ?? value;
}

function stringValue(value: DataToolbarFilterValue): string {
    return typeof value === 'string' ? value : '';
}
