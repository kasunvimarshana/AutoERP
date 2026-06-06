import { useEffect, useMemo, useRef, useState } from 'react';
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
    const debounced = useDebounce(query);
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
            setOptions([]);
            return;
        }

        const key = normalized.toLowerCase();
        const cached = cacheRef.current.get(key);
        if (cached) {
            setOptions(filter(cached, excludeId));
            return;
        }

        requestRef.current?.abort();
        const controller = new AbortController();
        requestRef.current = controller;
        search(normalized, controller.signal)
            .then((results) => {
                if (controller.signal.aborted) return;
                cacheRef.current.set(key, results);
                setOptions(filter(results, excludeId));
                setMessage('');
            })
            .catch((requestError: unknown) => {
                if (controller.signal.aborted) return;
                setOptions([]);
                setMessage(toApiError(requestError).message);
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
                onChange={(event) => {
                    setQuery(event.target.value);
                    setMessage('');
                    if (value) onChange(null);
                }}
            />
            {message && <p className="mt-1 text-xs text-rose-600">{message}</p>}
            {options.length > 0 && (
                <div className="absolute z-30 mt-1 max-h-56 w-full overflow-auto rounded-lg border border-slate-200 bg-white p-1 shadow-lg">
                    {options.map((option) => (
                        <button
                            key={option.id}
                            type="button"
                            className="block w-full rounded-md px-3 py-2 text-left text-sm hover:bg-sky-50"
                            onClick={() => {
                                validatedIdRef.current = Number(option.id);
                                onChange(option);
                                setQuery(formatLabel(option));
                                setOptions([]);
                            }}
                        >
                            {formatLabel(option)}
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}

function filter<T extends NamedResource>(options: T[], excludeId?: number | null): T[] {
    return options.filter((option) => !excludeId || Number(option.id) !== Number(excludeId));
}
