import { useCallback, useEffect, useId, useMemo, useRef, useState, type KeyboardEvent, type ReactNode } from 'react';
import { toApiError } from '@/shared/api/apiError';
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
    onChange: (resource: T | null) => void;
    search: LookupLoader<T>;
    formatLabel: (resource: T) => string;
    excludeId?: number | null;
    error?: string;
    placeholder?: string;
    disabled?: boolean;
    required?: boolean;
    id?: string;
}

export function GenericLookupSelect<T extends NamedResource>({
    label,
    value,
    onChange,
    search,
    formatLabel,
    excludeId,
    error,
    placeholder = 'Search by code or name',
    disabled = false,
    required = false,
    id,
    minSearchLength = DEFAULT_MIN_SEARCH_LENGTH,
    loadOnOpen = false,
    perPage = DEFAULT_PER_PAGE,
    debounceMs = DEFAULT_DEBOUNCE_MS,
}: GenericLookupSelectProps<T>) {
    const generatedId = useId();
    const inputId = id ?? `${generatedId}-input`;
    const listboxId = `${generatedId}-listbox`;
    const rootRef = useRef<HTMLDivElement>(null);
    const requestRef = useRef<AbortController | null>(null);
    const requestSeqRef = useRef(0);
    const inFlightKeyRef = useRef<string | null>(null);
    const skipNextValueSyncRef = useRef(false);

    const selectedLabel = useMemo(() => value ? formatLabel(value) : '', [formatLabel, value]);
    const [inputValue, setInputValue] = useState(selectedLabel);
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

    const searchText = hasUserInput ? inputValue.trim() : '';
    const debouncedSearchText = useDebounce(searchText, debounceMs);
    const activeOptionId = activeIndex >= 0 && options[activeIndex]
        ? `${listboxId}-option-${options[activeIndex].id}`
        : undefined;
    const canLoadCurrentSearch = canLoad(searchText, minSearchLength, loadOnOpen);
    const minimumMessage = open && !canLoadCurrentSearch
        ? charactersRequiredMessage(minSearchLength - searchText.length)
        : '';
    const waitingForDebounce = open
        && canLoadCurrentSearch
        && searchText.length > 0
        && searchText !== debouncedSearchText;
    const hasMore = Boolean(meta && meta.current_page < meta.last_page);

    const abortRequest = useCallback((resetLoading = true) => {
        requestRef.current?.abort();
        requestRef.current = null;
        inFlightKeyRef.current = null;
        requestSeqRef.current += 1;
        if (resetLoading) {
            setLoading(false);
            setLoadingMore(false);
        }
    }, []);

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

    const loadPage = useCallback((term: string, page: number, mode: 'replace' | 'append') => {
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
                const result = await search({ search: term, page, perPage, signal: controller.signal });
                if (controller.signal.aborted || requestSeqRef.current !== requestSeq) return;

                const filtered = filterExcluded(result.data, excludeId);
                setOptions((current) => mode === 'append' ? dedupeById([...current, ...filtered]) : filtered);
                setMeta(result.meta);
                setLoadedSearch(term);
                setHasLoaded(true);
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
    }, [excludeId, perPage, search]);

    useEffect(() => {
        if (skipNextValueSyncRef.current && value === null) {
            skipNextValueSyncRef.current = false;
            return;
        }

        setInputValue(selectedLabel);
        setHasUserInput(false);
        setActiveIndex(-1);
    }, [selectedLabel, value]);

    useEffect(() => {
        if (value && excludeId != null && Number(value.id) === Number(excludeId)) {
            onChange(null);
            setInputValue('');
            setHasUserInput(false);
            clearResults();
        }
    }, [clearResults, excludeId, onChange, value]);

    useEffect(() => {
        abortRequest();
        clearResults();
    }, [abortRequest, clearResults, excludeId, search]);

    useEffect(() => {
        if (!open || disabled) return;

        const handlePointerDown = (event: PointerEvent) => {
            if (!rootRef.current?.contains(event.target as Node)) closeDropdown();
        };

        document.addEventListener('pointerdown', handlePointerDown);
        return () => document.removeEventListener('pointerdown', handlePointerDown);
    }, [closeDropdown, disabled, open]);

    useEffect(() => {
        if (disabled) {
            closeDropdown();
            abortRequest();
        }
    }, [abortRequest, closeDropdown, disabled]);

    useEffect(() => () => abortRequest(false), [abortRequest]);

    useEffect(() => {
        if (!open || disabled) return;

        if (!canLoadCurrentSearch) {
            abortRequest();
            clearResults();
            return;
        }

        if (searchText.length > 0 && searchText !== debouncedSearchText) return;

        loadPage(searchText, 1, 'replace');
    }, [
        abortRequest,
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
                id={inputId}
                label={label}
                value={inputValue}
                placeholder={placeholder}
                error={error}
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
                onKeyDown={handleKeyDown}
                onChange={(event) => {
                    setInputValue(event.target.value);
                    setHasUserInput(true);
                    openDropdown();
                    abortRequest();
                    clearResults();

                    if (value) {
                        skipNextValueSyncRef.current = true;
                        onChange(null);
                    }
                }}
            />

            {open && (
                <div
                    id={listboxId}
                    role="listbox"
                    aria-label={`${label} options`}
                    className="absolute z-30 mt-1 max-h-64 w-full overflow-auto rounded-lg border border-slate-200 bg-white p-1 shadow-lg"
                >
                    {minimumMessage && <LookupMessage>{minimumMessage}</LookupMessage>}
                    {(loading || waitingForDebounce) && options.length === 0 && (
                        <LookupMessage role="status">Searching...</LookupMessage>
                    )}
                    {searchError && <LookupMessage tone="error">{searchError}</LookupMessage>}
                    {!loading && !waitingForDebounce && hasLoaded && options.length === 0 && !searchError && (
                        <LookupMessage>No matching {label.toLowerCase()} found.</LookupMessage>
                    )}

                    {options.map((option, index) => (
                        <button
                            key={option.id}
                            id={`${listboxId}-option-${option.id}`}
                            type="button"
                            role="option"
                            aria-selected={Number(option.id) === Number(value?.id)}
                            className={`block w-full rounded-md px-3 py-2 text-left text-sm ${activeIndex === index ? 'bg-sky-50 text-sky-800' : 'hover:bg-sky-50'}`}
                            onMouseDown={(event) => event.preventDefault()}
                            onMouseEnter={() => setActiveIndex(index)}
                            onClick={() => selectOption(option)}
                        >
                            {formatLabel(option)}
                        </button>
                    ))}

                    {options.length > 0 && hasMore && !searchError && (
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
            closeDropdown();
            return;
        }

        if (event.key === 'Tab') {
            closeDropdown();
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            if (!open) {
                openDropdown();
                return;
            }
            if (options.length > 0) {
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
            if (options.length > 0) {
                setActiveIndex((index) => index > 0 ? index - 1 : options.length - 1);
            }
            return;
        }

        if (event.key === 'Enter' && open && activeIndex >= 0 && options[activeIndex]) {
            event.preventDefault();
            selectOption(options[activeIndex]);
        }
    }

    function selectOption(option: T) {
        onChange(option);
        setInputValue(formatLabel(option));
        setHasUserInput(false);
        closeDropdown();
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

function filterExcluded<T extends NamedResource>(options: T[], excludeId?: number | null): T[] {
    return options.filter((option) => excludeId == null || Number(option.id) !== Number(excludeId));
}

function dedupeById<T extends NamedResource>(options: T[]): T[] {
    const seen = new Set<number>();
    return options.filter((option) => {
        const id = Number(option.id);
        if (seen.has(id)) return false;
        seen.add(id);
        return true;
    });
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
