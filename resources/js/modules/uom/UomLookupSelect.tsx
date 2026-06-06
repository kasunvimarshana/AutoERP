import { useEffect, useMemo, useRef, useState } from 'react';
import { toApiError } from '@/shared/api/apiError';
import { Input } from '@/shared/components/Input';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { searchUoms } from './uomApi';
import type { UomSummary } from './uomTypes';

export function UomLookupSelect({ label, value, onChange, excludeId, error }: {
    label: string;
    value: UomSummary | null;
    onChange: (uom: UomSummary | null) => void;
    excludeId?: number | null;
    error?: string;
}) {
    const [query, setQuery] = useState(value ? uomLabel(value) : '');
    const [options, setOptions] = useState<UomSummary[]>([]);
    const [message, setMessage] = useState('');
    const debounced = useDebounce(query);
    const abortRef = useRef<AbortController | null>(null);
    const validateAbortRef = useRef<AbortController | null>(null);
    const cacheRef = useRef(new Map<string, UomSummary[]>());
    const validatedSelectionRef = useRef<number | null>(null);
    const selectedLabel = useMemo(() => value ? uomLabel(value) : '', [value]);

    useEffect(() => {
        if (!value) {
            setQuery('');
            return;
        }
        if (!value.code || !value.name) {
            onChange(null);
            setQuery('');
            return;
        }
        if (excludeId && Number(value.id) === excludeId) {
            onChange(null);
            setQuery('');
            return;
        }
        setQuery(selectedLabel);
    }, [excludeId, onChange, selectedLabel, value]);

    useEffect(() => {
        if (!value?.code || !value.name) return;
        const selectedId = Number(value.id);
        if (excludeId && selectedId === Number(excludeId)) return;
        if (validatedSelectionRef.current === selectedId) return;

        validateAbortRef.current?.abort();
        const controller = new AbortController();
        validateAbortRef.current = controller;
        const cacheKey = value.code.trim().toLowerCase();
        const cached = cacheRef.current.get(cacheKey);

        const validateResults = (results: UomSummary[]) => {
            const match = results.find((option) => Number(option.id) === selectedId);
            if (!match) {
                validatedSelectionRef.current = null;
                onChange(null);
                setQuery('');
                setMessage(`${label} is no longer available in active UOM lookup.`);
                return;
            }

            validatedSelectionRef.current = selectedId;
            setQuery(uomLabel(match));
            if (uomLabel(match) !== selectedLabel) {
                onChange(match);
            }
        };

        if (cached) {
            validateResults(cached);
            return;
        }

        searchUoms(value.code, controller.signal)
            .then((results) => {
                if (controller.signal.aborted) return;
                cacheRef.current.set(cacheKey, results);
                validateResults(results);
            })
            .catch((requestError: unknown) => {
                if (controller.signal.aborted) return;
                setMessage(toApiError(requestError).message);
            });

        return () => controller.abort();
    }, [excludeId, label, onChange, selectedLabel, value]);

    useEffect(() => {
        const normalized = debounced.trim();
        if (normalized.length < 2 || normalized === selectedLabel) {
            setOptions([]);
            return;
        }

        const cacheKey = normalized.toLowerCase();
        const cached = cacheRef.current.get(cacheKey);
        if (cached) {
            setOptions(filterOptions(cached, excludeId));
            return;
        }

        abortRef.current?.abort();
        const controller = new AbortController();
        abortRef.current = controller;
        searchUoms(normalized, controller.signal)
            .then((results) => {
                if (controller.signal.aborted) return;
                cacheRef.current.set(cacheKey, results);
                setOptions(filterOptions(results, excludeId));
                setMessage('');
            })
            .catch((requestError: unknown) => {
                if (controller.signal.aborted) return;
                setMessage(toApiError(requestError).message);
                setOptions([]);
            });

        return () => controller.abort();
    }, [debounced, excludeId, selectedLabel]);

    useEffect(() => () => {
        abortRef.current?.abort();
        validateAbortRef.current?.abort();
    }, []);

    return (
        <div className="relative">
            <Input
                label={label}
                value={query}
                placeholder="Search UOM code, name, or symbol"
                error={error}
                onChange={(event) => {
                    setQuery(event.target.value);
                    setMessage('');
                    if (value) onChange(null);
                }}
            />
            {message && <p className="mt-1 text-xs text-rose-600">{message}</p>}
            {options.length > 0 && (
                <div className="absolute z-20 mt-1 max-h-56 w-full overflow-auto rounded-lg border border-slate-200 bg-white p-1 shadow-lg">
                    {options.map((option) => (
                        <button
                            key={option.id}
                            type="button"
                            className="block w-full rounded-md px-3 py-2 text-left text-sm hover:bg-sky-50"
                            onClick={() => {
                                onChange(option);
                                setQuery(uomLabel(option));
                                setOptions([]);
                            }}
                        >
                            {uomLabel(option)}
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}

export function uomLabel(uom: UomSummary): string {
    return `${uom.code} - ${uom.name} (${uom.symbol ?? uom.code})`;
}

function filterOptions(options: UomSummary[], excludeId?: number | null): UomSummary[] {
    return options.filter((option) => !excludeId || Number(option.id) !== excludeId);
}
