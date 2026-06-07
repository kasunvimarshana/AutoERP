import { useCallback } from 'react';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import { searchUoms } from './uomApi';
import type { UomSummary } from './uomTypes';

export function UomLookupSelect({ label, value, onChange, excludeId, error }: {
    label: string;
    value: UomSummary | null;
    onChange: (uom: UomSummary | null) => void;
    excludeId?: number | null;
    error?: string;
}) {
    const search = useCallback((query: string, signal: AbortSignal) => searchUoms(query, signal), []);
    return <GenericLookupSelect label={label} value={value} onChange={onChange} excludeId={excludeId} error={error} search={search} formatLabel={uomLabel} placeholder="Search UOM code, name, or symbol" />;
}

export function uomLabel(uom: UomSummary): string {
    return `${uom.code} - ${uom.name} (${uom.symbol ?? uom.code})`;
}
