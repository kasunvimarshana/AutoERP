/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { useCallback } from 'react';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import type { LookupLoadParams } from '@/shared/types/lookup';
import { searchUoms } from './uomApi';
import type { UomSummary } from './uomTypes';

export function UomLookupSelect({ label, value, onChange, excludeId, error, hint }: {
    label: string;
    value: UomSummary | null;
    onChange: (uom: UomSummary | null) => void;
    excludeId?: number | null;
    error?: string;
    hint?: string;
}) {
    const search = useCallback((params: LookupLoadParams) => searchUoms(params), []);
    return <GenericLookupSelect label={label} value={value} onChange={onChange} excludeId={excludeId} error={error} hint={hint} search={search} formatLabel={uomLabel} placeholder="Search UOM code, name, or symbol" loadOnOpen minSearchLength={0} />;
}

export function uomLabel(uom: UomSummary): string {
    return `${uom.code} - ${uom.name} (${uom.symbol ?? uom.code})`;
}
