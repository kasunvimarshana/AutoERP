import { useCallback, useEffect, useId, useMemo, useRef, useState, type KeyboardEvent, type ReactNode } from 'react';
import { toApiError } from '@/shared/api/apiError';
import { useLookupCacheStore } from '@/shared/state/lookupCacheStore';
import { Input } from '@/shared/components/Input';
import { useDebounce } from '@/shared/hooks/useDebounce';
import type { NamedResource } from '@/shared/types/common';
import type { LookupBehaviorOptions, LookupLoader } from '@/shared/types/lookup';
import type { PaginationMeta } from '@/shared/types/pagination';

const DEFAULT_MIN_SEARCH_LENGTH = 2;
const DEFAULT_PER_PAGE = 20;
const DEFAULT_DEBOUNCE_MS = 350;

interface GenericLookupSelectProps<T extends NamedResource> extends LookupBehaviorOptions {
    label: string;
    value: T | null;
    onChange: (resource: T | null) => void | boolean;
    search: LookupLoader<T>;
    formatLabel: (resource: T) => string;
    renderOption?: (resource: T, state: { active: boolean; selected: boolean }) => ReactNode;
    renderEmptyState?: (state: { searchText: string }) => ReactNode;
    excludeId?: number | null;
    excludeIds?: Array<number | string>;
    error?: string;
    placeholder?: string;
    disabled?: boolean;
    required?: boolean;
    id?: string;
    recentResultsKey?: string;
    dropdownPlacement?: 'top' | 'bottom';
}

export function GenericLookupSelect<T extends NamedResource>({
    label,
    value,
    onChange,
    search,
    formatLabel,
    renderOption,
    renderEmptyState,
    excludeId,
    excludeIds,
    error,
    placeholder = 'Search by code or name',
    disabled = false,
    required = false,
    id,
    recentResultsKey,
    dropdownPlacement = 'bottom',
    minSearchLength = DEFAULT_MIN_SEARCH_LENGTH,
    loadOnOpen = false,
    perPage = DEFAULT_PER_PAGE,
    debounceMs = DEFAULT_DEBOUNCE_MS,
}: GenericLookupSelectProps<T>) {
    const generatedId = useId();
    const inputId = id ?? `${generatedId}-input`;
    const listboxId = `${generatedId}-listbox`;
    const rootRef = useRef<HTMLDivElement>(null);
    const inputRef = useRef<HTMLInputElement>(null);
    const listboxRef = useRef<HTMLDivElement>(null);
    const requestRef = useRef<AbortController | null>(null);
    const searchRef = useRef(search);
    const requestSeqRef = useRef(0);
    const inFlightKeyRef = useRef<string | null>(null);
    const recentResultsEntry = useLookupCacheStore((state) =>
        recentResultsKey ? state.recentEntries[recentResultsKey] : undefined,
    );
    const recentResultsRef = useRef(recentResultsEntry);
    const setRecentEntry = useLookupCacheStore((state) => state.setRecentEntry);
    const excludedKey = normalizeExcludedIds(excludeId, excludeIds).join('|');
    const excludedIds = useMemo(() => new Set(excludedKey ? excludedKey.split('|') : []), [excludedKey]);

    const selectedLabel = useMemo(() => value ? formatLabel(value) : '', [formatLabel, value]);
    const [inputValue, setInputValue] = useState('');
    const [hasUserInput, setHasUserInput] = useState(false);
    const [open, setOpen] = useState(false);
    const [options, setOptions] = useState<T[]>([]);
    const [meta, setMeta] = useState<PaginationMeta | undefined>();
    const [loadedSearch, setLoadedSearch] = useState('');
    const [loading, setLoading] = useState(false);
    const [loadingMore, setLoadingMore] = useState(false);
    const [hasLoaded, setHasLoaded] = useState(false);
    const [searchError, setSearchError] = useState('');
    const [loadMoreError, setLoadMoreError] = useState('');
    const [activeIndex, setActiveIndex] = useState(-1);
    const [selectionError, setSelectionError] = useState('');

    const searchText = hasUserInput ? inputValue.trim() : '';
    const debouncedSearchText = useDebounce(searchText, debounceMs);
    const visibleOptions = useMemo(() => filterExcluded(options, excludedIds), [excludedIds, options]);
    const normalizedActiveIndex = activeIndex >= 0 && activeIndex < visibleOptions.length ? activeIndex : -1;
    const activeOptionId = normalizedActiveIndex >= 0
        ? `${listboxId}-option-${visibleOptions[normalizedActiveIndex].id}`
        : undefined;
    const canLoadCurrentSearch = canLoad(searchText, minSearchLength, loadOnOpen);
    const minimumMessage = open && !canLoadCurrentSearch
        && !(recentResultsEntry && searchText === '')
        ? charactersRequiredMessage(minSearchLength - searchText.length)
        : '';
    const waitingForDebounce = open
        && canLoadCurrentSearch
        && searchText.length > 0
        && searchText !== debouncedSearchText;
    const hasMore = Boolean(meta && meta.current_page < meta.last_page);

    useEffect(() => {
        searchRef.current = search;
    }, [search]);

    useEffect(() => {
        recentResultsRef.current = recentResultsEntry;
    }, [recentResultsEntry]);

    const cancelRequest = useCallback(() => {
        requestRef.current?.abort();
        requestRef.current = null;
        inFlightKeyRef.current = null;
        requestSeqRef.current += 1;
    }, []);

    const abortRequest = useCallback((resetLoading = true) => {
        cancelRequest();
        if (resetLoading) {
            setLoading(false);
            setLoadingMore(false);
        }
    }, [cancelRequest]);

    const clearResults = useCallback(() => {
        setOptions([]);
        setMeta(undefined);
        setLoadedSearch('');
        setHasLoaded(false);
        setSearchError('');
        setLoadMoreError('');
        setActiveIndex(-1);
    }, []);

    const closeDropdown = useCallback(() => {
        setOpen(false);
        setActiveIndex(-1);
    }, []);

    const openDropdown = useCallback(() => {
        if (!disabled) setOpen(true);
    }, [disabled]);

    const loadPage = useCallback(async (term: string, page: number, mode: 'replace' | 'append') => {
        await Promise.resolve();
        const requestKey = `${term}\n${page}\n${perPage}`;
        if (inFlightKeyRef.current === requestKey) return;

        requestRef.current?.abort();
        const controller = new AbortController();
        const requestSeq = requestSeqRef.current + 1;
        requestRef.current = controller;
        requestSeqRef.current = requestSeq;
        inFlightKeyRef.current = requestKey;

        if (mode === 'replace') {
            setOptions([]);
            setMeta(undefined);
            setLoadedSearch(term);
            setHasLoaded(false);
            setSearchError('');
            setLoadMoreError('');
            setActiveIndex(-1);
            setLoading(true);
        } else {
            setLoadMoreError('');
            setLoadingMore(true);
        }

        void (async () => {
            try {
                const result = await searchRef.current({ search: term, page, perPage, signal: controller.signal });
                if (controller.signal.aborted || requestSeqRef.current !== requestSeq) return;

                const nextOptions = dedupeById(result.data);
                setOptions((current) => mode === 'append' ? dedupeById([...current, ...nextOptions]) : nextOptions);
                setMeta(result.meta);
                setLoadedSearch(term);
                setHasLoaded(true);
                if (mode === 'replace' && recentResultsKey && term !== '') {
                    setRecentEntry(recentResultsKey, nextOptions);
                }
                if (mode === 'replace') setActiveIndex(-1);
            } catch (requestError: unknown) {
                if (controller.signal.aborted || requestSeqRef.current !== requestSeq) return;

                const message = toApiError(requestError).message;
                if (mode === 'append') {
                    setLoadMoreError(message);
                } else {
                    setOptions([]);
                    setMeta(undefined);
                    setHasLoaded(true);
                    setSearchError(message);
                    setActiveIndex(-1);
                }
            } finally {
                if (!controller.signal.aborted && requestSeqRef.current === requestSeq) {
                    setLoading(false);
                    setLoadingMore(false);
                    requestRef.current = null;
                    inFlightKeyRef.current = null;
                }
            }
        })();
    }, [perPage, recentResultsKey, setRecentEntry]);

    useEffect(() => {
        if (!open || disabled) return;

        const handlePointerDown = (event: PointerEvent) => {
            if (!rootRef.current?.contains(event.target as Node)) closeDropdown();
        };

        document.addEventListener('pointerdown', handlePointerDown);
        return () => document.removeEventListener('pointerdown', handlePointerDown);
    }, [closeDropdown, disabled, open]);

    useEffect(() => {
        if (disabled) cancelRequest();
    }, [cancelRequest, disabled]);

    useEffect(() => cancelRequest, [cancelRequest]);

    useEffect(() => {
        const message = required && value === null ? requiredSelectionMessage(label) : '';
        inputRef.current?.setCustomValidity(message);
    }, [label, required, value]);

    useEffect(() => {
        if (!open || activeIndex < 0) return;
        const optionId = `${listboxId}-option-${options[activeIndex]?.id ?? ''}`;
        const activeOption = document.getElementById(optionId);
        if (
            activeOption
            && listboxRef.current?.contains(activeOption)
            && typeof activeOption.scrollIntoView === 'function'
        ) {
            activeOption.scrollIntoView({ block: 'nearest' });
        }
    }, [activeIndex, listboxId, open, options]);

    useEffect(() => {
        if (!open || disabled) return;

        if (!canLoadCurrentSearch) {
            cancelRequest();
            if (recentResultsRef.current && searchText === '') {
                queueMicrotask(() => {
                    setOptions(recentResultsRef.current?.options as T[]);
                    setMeta(undefined);
                    setLoadedSearch('');
                    setHasLoaded(true);
                    setSearchError('');
                    setLoadMoreError('');
                    setActiveIndex(-1);
                });
                return;
            }
            queueMicrotask(clearResults);
            return;
        }

        if (searchText.length > 0 && searchText !== debouncedSearchText) return;

        void Promise.resolve().then(() => loadPage(searchText, 1, 'replace'));
    }, [
        cancelRequest,
        canLoadCurrentSearch,
        clearResults,
        debouncedSearchText,
        disabled,
        loadPage,
        open,
        searchText,
    ]);

    return (
        <div ref={rootRef} className="relative">
            <Input
                ref={inputRef}
                id={inputId}
                label={label}
                value={hasUserInput ? inputValue : selectedLabel}
                placeholder={placeholder}
                error={error ?? (value === null ? selectionError : '')}
                disabled={disabled}
                required={required}
                role="combobox"
                autoComplete="off"
                aria-expanded={open}
                aria-haspopup="listbox"
                aria-controls={open ? listboxId : undefined}
                aria-activedescendant={open ? activeOptionId : undefined}
                aria-autocomplete="list"
                className={disabled ? 'cursor-not-allowed bg-slate-50 text-slate-500' : ''}
                onFocus={openDropdown}
                onClick={openDropdown}
                onBlur={(event) => {
                    const nextTarget = event.relatedTarget;
                    if (nextTarget instanceof Node && rootRef.current?.contains(nextTarget)) return;
                    reconcileUnselectedText();
                    closeDropdown();
                }}
                onInvalid={(event) => {
                    if (required && value === null) {
                        event.preventDefault();
                        setSelectionError(requiredSelectionMessage(label));
                        openDropdown();
                    }
                }}
                onKeyDown={handleKeyDown}
                onChange={(event) => {
                    setInputValue(event.target.value);
                    setHasUserInput(true);
                    setSelectionError('');
                    openDropdown();
                    abortRequest();
                    clearResults();

                    if (value && onChange(null) === false) {
                        setInputValue('');
                        setHasUserInput(false);
                        closeDropdown();
                    }
                }}
            />

            {open && (
                <div
                    ref={listboxRef}
                    id={listboxId}
                    role="listbox"
                    aria-label={`${label} options`}
                    className={`absolute z-30 max-h-64 w-full overflow-auto rounded-lg border border-slate-200 bg-white p-1 shadow-lg ${dropdownPlacement === 'top' ? 'bottom-full mb-1' : 'mt-1'}`}
                >
                    {minimumMessage && <LookupMessage>{minimumMessage}</LookupMessage>}
                    {(loading || waitingForDebounce) && visibleOptions.length === 0 && (
                        <LookupMessage role="status">Searching...</LookupMessage>
                    )}
                    {searchError && (
                        <div className="px-3 py-2 text-sm text-rose-600">
                            <p>{searchError}</p>
                            <button
                                type="button"
                                className="mt-1 font-medium text-sky-700 hover:underline"
                                onMouseDown={(event) => event.preventDefault()}
                                onClick={() => loadPage(searchText, 1, 'replace')}
                            >
                                Retry
                            </button>
                        </div>
                    )}
                    {!loading && !waitingForDebounce && hasLoaded && visibleOptions.length === 0 && !searchError && (
                        renderEmptyState?.({ searchText }) ?? <LookupMessage>No matching {label.toLowerCase()} found.</LookupMessage>
                    )}

                    {visibleOptions.map((option, index) => {
                        const selected = Number(option.id) === Number(value?.id);
                        const active = normalizedActiveIndex === index;

                        return (
                        <button
                            key={option.id}
                            id={`${listboxId}-option-${option.id}`}
                            type="button"
                            role="option"
                            aria-selected={selected}
                            className={`block w-full rounded-md px-3 py-2 text-left text-sm ${active ? 'bg-sky-50 text-sky-800' : 'hover:bg-sky-50'}`}
                            onMouseDown={(event) => event.preventDefault()}
                            onMouseEnter={() => setActiveIndex(index)}
                            onClick={() => selectOption(option)}
                        >
                            {renderOption ? renderOption(option, { active, selected }) : formatLabel(option)}
                        </button>
                        );
                    })}

                    {visibleOptions.length > 0 && hasMore && !searchError && (
                        <div className="mt-1 border-t border-slate-100 pt-1">
                            <button
                                type="button"
                                className="block w-full rounded-md px-3 py-2 text-left text-sm font-medium text-sky-700 hover:bg-sky-50 disabled:cursor-not-allowed disabled:text-slate-400"
                                disabled={loading || loadingMore}
                                onMouseDown={(event) => event.preventDefault()}
                                onClick={() => loadPage(loadedSearch, (meta?.current_page ?? 1) + 1, 'append')}
                            >
                                {loadingMore ? 'Loading more...' : 'Load more'}
                            </button>
                            {loadMoreError && <LookupMessage tone="error">{loadMoreError}</LookupMessage>}
                        </div>
                    )}
                </div>
            )}
        </div>
    );

    function handleKeyDown(event: KeyboardEvent<HTMLInputElement>) {
        if (event.key === 'Escape') {
            setInputValue('');
            setHasUserInput(false);
            setSelectionError('');
            closeDropdown();
            return;
        }

        if (event.key === 'Tab') {
            reconcileUnselectedText();
            closeDropdown();
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            if (!open) {
                openDropdown();
                return;
            }
            if (visibleOptions.length > 0) {
                setActiveIndex((index) => index < options.length - 1 ? index + 1 : 0);
            }
            return;
        }

        if (event.key === 'ArrowUp') {
            event.preventDefault();
            if (!open) {
                openDropdown();
                return;
            }
            if (visibleOptions.length > 0) {
                setActiveIndex((index) => index > 0 ? index - 1 : options.length - 1);
            }
            return;
        }

        if (event.key === 'Enter' && open && normalizedActiveIndex >= 0 && visibleOptions[normalizedActiveIndex]) {
            event.preventDefault();
            selectOption(visibleOptions[normalizedActiveIndex]);
        }
    }

    function selectOption(option: T) {
        if (onChange(option) === false) return;
        setInputValue('');
        setHasUserInput(false);
        setSelectionError('');
        closeDropdown();
    }

    function reconcileUnselectedText() {
        if (value !== null) {
            setInputValue('');
            setHasUserInput(false);
            return;
        }

        setInputValue('');
        setHasUserInput(false);
        if (required) setSelectionError(requiredSelectionMessage(label));
    }
}

function canLoad(searchText: string, minSearchLength: number, loadOnOpen: boolean): boolean {
    if (searchText.length >= minSearchLength) return true;
    return searchText.length === 0 && (loadOnOpen || minSearchLength === 0);
}

function charactersRequiredMessage(required: number): string {
    const safeRequired = Math.max(0, required);
    return safeRequired === 1
        ? 'Enter 1 more character to search.'
        : `Enter ${safeRequired} more characters to search.`;
}

function normalizeExcludedIds(excludeId?: number | null, excludeIds: Array<number | string> = []): string[] {
    const ids = [
        ...(excludeId == null ? [] : [excludeId]),
        ...excludeIds,
    ];
    const normalized = ids
        .map((id) => String(id).trim())
        .filter((id) => id !== '');

    return Array.from(new Set(normalized)).sort();
}

function filterExcluded<T extends NamedResource>(options: T[], excludedIds: Set<string>): T[] {
    return options.filter((option) => !excludedIds.has(normalizeLookupId(option.id)));
}

function dedupeById<T extends NamedResource>(options: T[]): T[] {
    const seen = new Set<string>();
    return options.filter((option) => {
        const id = normalizeLookupId(option.id);
        if (seen.has(id)) return false;
        seen.add(id);
        return true;
    });
}

function normalizeLookupId(id: number | string): string {
    return String(id).trim();
}

function LookupMessage({
    children,
    role,
    tone = 'muted',
}: {
    children: ReactNode;
    role?: 'status';
    tone?: 'muted' | 'error';
}) {
    return (
        <div role={role} className={`px-3 py-2 text-sm ${tone === 'error' ? 'text-rose-600' : 'text-slate-500'}`}>
            {children}
        </div>
    );
}

function requiredSelectionMessage(label: string): string {
    const normalizedLabel = label.replace(/\s*\*+\s*$/, '').trim().toLowerCase();
    return `Select a valid ${normalizedLabel} from the list.`;
}
