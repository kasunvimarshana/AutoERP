import { useCallback } from 'react';
import { lookupApi } from '@/shared/api/lookupApi';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import type { NamedResource } from '@/shared/types/common';
import type { RentalPartyType } from '../vehicleRentalTypes';

export function RentalPartySelect({ partyType, value, onChange, error }: {
    partyType: RentalPartyType;
    value: NamedResource | null;
    onChange: (value: NamedResource | null) => void;
    error?: string;
}) {
    const search = useCallback((query: string, signal: AbortSignal) => (
        partyType === 'customer' ? lookupApi.customers(query, signal) : lookupApi.suppliers(query, signal)
    ), [partyType]);
    return (
        <GenericLookupSelect
            label={partyType === 'customer' ? 'Customer' : partyType === 'owner' ? 'Owner / supplier' : 'Supplier'}
            value={value}
            onChange={onChange}
            search={search}
            formatLabel={(item) => `${item.code ?? ''} ${item.name}`.trim()}
            error={error}
        />
    );
}
