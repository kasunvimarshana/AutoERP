import { useEffect, useState } from 'react';
import type { NamedResource } from '@/shared/types/common';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { useAbortableRequest } from '@/shared/hooks/useAbortableRequest';
import { toApiError } from '@/shared/api/apiError';
import { Input } from './Input';

export function LookupSelect({ label, value, onChange, search, placeholder = 'Search...', error }: {
    label: string;
    value: NamedResource | null;
    onChange: (resource: NamedResource | null) => void;
    search: (query: string, signal: AbortSignal) => Promise<NamedResource[]>;
    placeholder?: string;
    error?: string;
}) {
    const [query, setQuery] = useState(value?.name ?? '');
    const [options, setOptions] = useState<NamedResource[]>([]);
    const [message, setMessage] = useState('');
    const debounced = useDebounce(query);
    const { nextSignal } = useAbortableRequest();

    useEffect(() => {
        if (debounced.trim().length < 2 || debounced === value?.name) {
            setOptions([]);
            return;
        }
        const signal = nextSignal();
        search(debounced, signal)
            .then((results) => !signal.aborted && setOptions(results))
            .catch((requestError: unknown) => !signal.aborted && setMessage(toApiError(requestError).message));
    }, [debounced, nextSignal, search, value?.name]);

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
                <div className="absolute z-20 mt-1 max-h-56 w-full overflow-auto rounded-lg border border-slate-200 bg-white p-1 shadow-lg">
                    {options.map((option) => (
                        <button
                            type="button"
                            key={option.id}
                            className="block w-full rounded-md px-3 py-2 text-left text-sm hover:bg-sky-50"
                            onClick={() => {
                                onChange(option);
                                setQuery(option.name);
                                setOptions([]);
                            }}
                        >
                            <span className="font-medium">{option.name}</span>
                            {option.code && <span className="ml-2 text-slate-500">{option.code}</span>}
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}
