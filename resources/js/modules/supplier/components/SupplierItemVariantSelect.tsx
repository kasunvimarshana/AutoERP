import { useEffect, useRef, useState } from 'react';
import { toApiError } from '@/shared/api/apiError';
import { Select } from '@/shared/components/Select';
import type { NamedResource } from '@/shared/types/common';
import { listItemVariantsForLookup } from '../supplierApi';

export function SupplierItemVariantSelect({ item, value, onChange, error }: {
    item: NamedResource | null;
    value: NamedResource | null;
    onChange: (variant: NamedResource | null) => void;
    error?: string;
}) {
    const [options, setOptions] = useState<NamedResource[]>([]);
    const [message, setMessage] = useState('');
    const [loading, setLoading] = useState(false);
    const onChangeRef = useRef(onChange);
    const valueRef = useRef(value);
    onChangeRef.current = onChange;
    valueRef.current = value;

    useEffect(() => {
        if (!item) {
            setOptions([]);
            setMessage('');
            if (valueRef.current) onChangeRef.current(null);
            return;
        }
        const controller = new AbortController();
        setLoading(true);
        listItemVariantsForLookup(Number(item.id), controller.signal)
            .then((rows) => {
                if (controller.signal.aborted) return;
                setOptions(rows);
                setMessage('');
                if (valueRef.current && !rows.some((row) => Number(row.id) === Number(valueRef.current?.id))) {
                    onChangeRef.current(null);
                }
            })
            .catch((requestError) => {
                if (!controller.signal.aborted) setMessage(toApiError(requestError).message);
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoading(false);
            });
        return () => controller.abort();
    }, [item?.id]);

    return <div>
        <Select
            label="Item variant"
            value={value?.id ?? ''}
            disabled={!item || loading}
            error={error}
            placeholder={loading ? 'Loading variants...' : 'No variant'}
            options={options.map((variant) => ({ value: variant.id, label: `${variant.code ?? ''} - ${variant.name}` }))}
            onChange={(event) => onChange(options.find((variant) => Number(variant.id) === Number(event.target.value)) ?? null)}
        />
        {message && <p className="mt-1 text-xs text-rose-600">{message}</p>}
    </div>;
}
