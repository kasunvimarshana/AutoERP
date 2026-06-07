import { useEffect, useId, useMemo, useRef, useState } from 'react';
import { toApiError } from '@/shared/api/apiError';
import { Input } from '@/shared/components/Input';
import { useDebounce } from '@/shared/hooks/useDebounce';
import type { NamedResource } from '@/shared/types/common';

export function GenericLookupSelect<T extends NamedResource>({
    label,
    value,
    onChange,
    search,
    formatLabel,
    excludeId,
    error,
    placeholder = 'Search by code or name',
}: {
    label: string;
    value: T | null;
    onChange: (resource: T | null) => void;
    search: (query: string, signal: AbortSignal) => Promise<T[]>;
    formatLabel: (resource: T) => string;
    excludeId?: number | null;
    error?: string;
    placeholder?: string;
}) {
    const selectedLabel = useMemo(() => value ? formatLabel(value) : '', [formatLabel, value]);
    const [query, setQuery] = useState(selectedLabel);
    const [options, setOptions] = useState<T[]>([]);
    const [message, setMessage] = useState('');
    const [loading, setLoading] = useState(false);
    const [activeIndex, setActiveIndex] = useState(-1);
    const [searched, setSearched] = useState(false);
    const debounced = useDebounce(query);
    const listboxId = useId();
    const activeOptionId = activeIndex >= 0 ? `${listboxId}-option-${activeIndex}` : undefined;
    const requestRef = useRef<AbortController | null>(null);
    const validationRef = useRef<AbortController | null>(null);
    const cacheRef = useRef(new Map<string, T[]>());
    const validatedIdRef = useRef<number | null>(null);

    useEffect(() => {
        if (!value) {
            setQuery('');
            validatedIdRef.current = null;
            return;
        }
        if (excludeId && Number(value.id) === Number(excludeId)) {
            onChange(null);
            setQuery('');
            return;
        }
        setQuery(selectedLabel);
    }, [excludeId, onChange, selectedLabel, value]);

    useEffect(() => {
        if (!value || (excludeId && Number(value.id) === Number(excludeId))) return;
        const selectedId = Number(value.id);
        if (validatedIdRef.current === selectedId) return;
        const lookup = String(value.code || value.name).trim();
        if (!lookup) {
            onChange(null);
            return;
        }

        validationRef.current?.abort();
        const controller = new AbortController();
        validationRef.current = controller;
        const key = lookup.toLowerCase();
        const validate = (results: T[]) => {
            const match = results.find((entry) => Number(entry.id) === selectedId);
            if (!match) {
                validatedIdRef.current = null;
                onChange(null);
                setQuery('');
                setMessage(`${label} is no longer available.`);
                return;
            }
            validatedIdRef.current = selectedId;
            if (formatLabel(match) !== selectedLabel) onChange(match);
        };

        const cached = cacheRef.current.get(key);
        if (cached) {
            validate(cached);
            return;
        }
        search(lookup, controller.signal)
            .then((results) => {
                if (controller.signal.aborted) return;
                cacheRef.current.set(key, results);
                validate(results);
            })
            .catch((requestError: unknown) => {
                if (!controller.signal.aborted) setMessage(toApiError(requestError).message);
            });

        return () => controller.abort();
    }, [excludeId, formatLabel, label, onChange, search, selectedLabel, value]);

    useEffect(() => {
        const normalized = debounced.trim();
        if (normalized.length < 2 || normalized === selectedLabel) {
            requestRef.current?.abort();
            setOptions([]);
            setLoading(false);
            setActiveIndex(-1);
            setSearched(false);
            return;
        }

        const key = normalized.toLowerCase();
        const cached = cacheRef.current.get(key);
        if (cached) {
            setOptions(filter(cached, excludeId));
            setLoading(false);
            setActiveIndex(-1);
            setSearched(true);
            return;
        }

                requestRef.current?.abort();
        const controller = new AbortController();
        requestRef.current = controller;
        setLoading(true);
        setSearched(false);
        search(normalized, controller.signal)
            .then((results) => {
                if (controller.signal.aborted) return;
                cacheRef.current.set(key, results);
                setOptions(filter(results, excludeId));
                setActiveIndex(-1);
                setSearched(true);
                setMessage('');
            })
            .catch((requestError: unknown) => {
                if (controller.signal.aborted) return;
                setOptions([]);
                setActiveIndex(-1);
                setSearched(true);
                setMessage(toApiError(requestError).message);
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoading(false);
            });

        return () => controller.abort();
    }, [debounced, excludeId, search, selectedLabel]);

    useEffect(() => () => {
        requestRef.current?.abort();
        validationRef.current?.abort();
    }, []);

    return (
        <div className="relative">
            <Input
                label={label}
                value={query}
                placeholder={placeholder}
                error={error}
                role="combobox"
                autoComplete="off"
                aria-expanded={options.length > 0}
                aria-controls={listboxId}
                aria-activedescendant={activeOptionId}
                aria-autocomplete="list"
                onKeyDown={(event) => {
                    if (event.key === 'Escape') {
                        setOptions([]);
                        setActiveIndex(-1);
                    }
                    if (event.key === 'ArrowDown' && options.length > 0) {
                        event.preventDefault();
                        setActiveIndex((index) => Math.min(index + 1, options.length - 1));
                    }
                    if (event.key === 'ArrowUp' && options.length > 0) {
                        event.preventDefault();
                        setActiveIndex((index) => Math.max(index - 1, 0));
                    }
                    if (event.key === 'Enter' && activeIndex >= 0 && options[activeIndex]) {
                        event.preventDefault();
                        selectOption(options[activeIndex]);
                    }
                }}
                onChange={(event) => {
                    setQuery(event.target.value);
                    setMessage('');
                    setSearched(false);
                    setActiveIndex(-1);
                    if (value) onChange(null);
                }}
            />
            {loading && <p className="mt-1 text-xs text-slate-500" role="status">Searching...</p>}
            {message && <p className="mt-1 text-xs text-rose-600">{message}</p>}
            {!loading && searched && debounced.trim().length >= 2 && options.length === 0 && !message && (
                <p className="mt-1 text-xs text-slate-500" role="status">No matching {label.toLowerCase()} found.</p>
            )}
            {options.length > 0 && (
                <div id={listboxId} role="listbox" className="absolute z-30 mt-1 max-h-56 w-full overflow-auto rounded-lg border border-slate-200 bg-white p-1 shadow-lg">
                    {options.map((option, index) => (
                        <button
                            key={option.id}
                            id={`${listboxId}-option-${index}`}
                            type="button"
                            role="option"
                            aria-selected={Number(option.id) === Number(value?.id)}
                            className={`block w-full rounded-md px-3 py-2 text-left text-sm ${activeIndex === index ? 'bg-sky-50 text-sky-800' : 'hover:bg-sky-50'}`}
                            onMouseEnter={() => setActiveIndex(index)}
                            onClick={() => selectOption(option)}
                        >
                            {formatLabel(option)}
                        </button>
                    ))}
                </div>
            )}
        </div>
    );

    function selectOption(option: T) {
        validatedIdRef.current = Number(option.id);
        onChange(option);
        setQuery(formatLabel(option));
        setOptions([]);
        setActiveIndex(-1);
    }
}

function filter<T extends NamedResource>(options: T[], excludeId?: number | null): T[] {
    return options.filter((option) => !excludeId || Number(option.id) !== Number(excludeId));
}
