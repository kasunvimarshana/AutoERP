import { useEffect, useEffectEvent, useState } from 'react';
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
    const emitChange = useEffectEvent(onChange);
    const getSelectedValue = useEffectEvent(() => value);
    const itemId = item?.id ?? null;

    useEffect(() => {
        if (itemId === null) {
            queueMicrotask(() => {
                setOptions([]);
                setMessage('');
                if (getSelectedValue()) emitChange(null);
            });
            return;
        }
        const controller = new AbortController();
        queueMicrotask(() => {
            if (!controller.signal.aborted) setLoading(true);
        });
        listItemVariantsForLookup(Number(itemId), controller.signal)
            .then((rows) => {
                if (controller.signal.aborted) return;
                setOptions(rows);
                setMessage('');
                const selected = getSelectedValue();
                if (selected && !rows.some((row) => Number(row.id) === Number(selected.id))) {
                    emitChange(null);
                }
            })
            .catch((requestError) => {
                if (!controller.signal.aborted) setMessage(toApiError(requestError).message);
            })
            .finally(() => {
                if (!controller.signal.aborted) setLoading(false);
            });
        return () => controller.abort();
    }, [itemId]);

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
