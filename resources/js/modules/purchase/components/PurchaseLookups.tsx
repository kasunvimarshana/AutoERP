import { LookupSelect } from '@/shared/components/LookupSelect';
import { lookupApi } from '@/shared/api/lookupApi';
import { listUoms, searchCurrencies, searchWarehouseLocations, searchWarehouses } from '@/shared/api/referenceApi';
import type { NamedResource } from '@/shared/types/common';

interface LookupProps {
    value: NamedResource | null;
    onChange: (value: NamedResource | null) => void;
    error?: string;
}

export function SupplierLookupSelect(props: LookupProps) {
    return <LookupSelect label="Supplier" search={lookupApi.suppliers} placeholder="Search suppliers..." {...props} />;
}

export function ItemLookupSelect(props: LookupProps) {
    return <LookupSelect label="Item" search={lookupApi.items} placeholder="Search items..." {...props} />;
}

export function UomLookupSelect(props: LookupProps) {
    return <LookupSelect label="UOM" search={listUoms} placeholder="Search UOMs..." {...props} />;
}

export function WarehouseLookupSelect(props: LookupProps) {
    return <LookupSelect label="Warehouse" search={searchWarehouses} placeholder="Search warehouses..." {...props} />;
}

export function WarehouseLocationLookupSelect(props: LookupProps) {
    return <LookupSelect label="Location" search={searchWarehouseLocations} placeholder="Search locations..." {...props} />;
}

export function CurrencyLookupSelect(props: LookupProps) {
    return <LookupSelect label="Currency" search={searchCurrencies} placeholder="Search currency code..." {...props} />;
}
