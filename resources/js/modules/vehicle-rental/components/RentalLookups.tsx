import { useCallback } from 'react';
import { listInvoices } from '@/modules/invoice/invoiceApi';
import { listPaymentMethods } from '@/modules/payment/paymentApi';
import { listTaxGroups } from '@/modules/tax/taxApi';
import type { VehicleSummary } from '@/modules/vehicle/vehicleTypes';
import { searchCurrencies } from '@/shared/api/referenceApi';
import { LookupSelect } from '@/shared/components/LookupSelect';
import type { NamedResource } from '@/shared/types/common';
import type { LookupBehaviorOptions, LookupLoadParams, LookupResult } from '@/shared/types/lookup';
import {
    listAvailableRentalVehicles,
    listRentalAgreements,
    listRentalAllocations,
    listVehicleFinanceAgreements,
} from '../vehicleRentalApi';
import type { RentalVehicle } from '../vehicleRentalTypes';

interface LookupProps<T extends NamedResource = NamedResource> extends LookupBehaviorOptions {
    value: T | null;
    onChange: (value: T | null) => void;
    error?: string;
    disabled?: boolean;
    required?: boolean;
    excludeId?: number | null;
}

interface AllocationLookupFilters {
    agreementId?: number | null;
    vehicleId?: number | null;
    agreementKind?: string | null;
    coversStartAt?: string | null;
    coversEndAt?: string | null;
    openOnly?: boolean;
}

interface FinanceLookupFilters {
    vehicleId?: number | null;
    coversStartAt?: string | null;
    coversEndAt?: string | null;
    activeOnly?: boolean;
}

type AvailableVehicleOption = VehicleSummary & NamedResource;

export function RentalCurrencyLookupSelect(props: LookupProps) {
    return <LookupSelect label="Currency" search={searchCurrencies} loadOnOpen minSearchLength={0} {...props} />;
}

export function RentalAgreementLookupSelect({ direction, ...props }: LookupProps & { direction?: 'inbound' | 'outbound' }) {
    const search = useCallback(
        (params: LookupLoadParams) => searchAgreements(params, direction),
        [direction],
    );

    return <LookupSelect label="Rental agreement" search={search} placeholder="Search agreement number or party..." {...props} />;
}

export function RentalAllocationLookupSelect({
    agreementId,
    vehicleId,
    agreementKind,
    coversStartAt,
    coversEndAt,
    openOnly,
    ...props
}: LookupProps & AllocationLookupFilters) {
    const search = useCallback(
        (params: LookupLoadParams) => searchAllocations(params, {
            agreementId,
            vehicleId,
            agreementKind,
            coversStartAt,
            coversEndAt,
            openOnly,
        }),
        [agreementId, vehicleId, agreementKind, coversStartAt, coversEndAt, openOnly],
    );

    return <LookupSelect label="Vehicle allocation" search={search} placeholder="Search allocation number or vehicle..." {...props} />;
}

export function RentalFinanceAgreementLookupSelect({
    vehicleId,
    coversStartAt,
    coversEndAt,
    activeOnly,
    ...props
}: LookupProps & FinanceLookupFilters) {
    const search = useCallback(
        (params: LookupLoadParams) => searchFinanceAgreements(params, {
            vehicleId,
            coversStartAt,
            coversEndAt,
            activeOnly,
        }),
        [vehicleId, coversStartAt, coversEndAt, activeOnly],
    );

    return <LookupSelect label="Finance agreement" search={search} placeholder="Search finance agreement..." {...props} />;
}

export function RentalAvailableVehicleLookupSelect({
    value,
    onChange,
    startAt,
    endAt,
    disabled,
    ...props
}: Omit<LookupProps<AvailableVehicleOption>, 'value' | 'onChange'> & {
    value: VehicleSummary | null;
    onChange: (value: VehicleSummary | null) => void;
    startAt?: string | null;
    endAt?: string | null;
}) {
    const search = useCallback(
        (params: LookupLoadParams) => searchAvailableVehicles(params, startAt, endAt),
        [startAt, endAt],
    );
    const selected = value ? { ...value, name: value.vehicle_number } : null;

    return (
        <LookupSelect<AvailableVehicleOption>
            label="Vehicle"
            search={search}
            value={selected}
            onChange={(resource) => onChange(resource)}
            placeholder="Search available vehicle..."
            disabled={disabled || !startAt || !endAt}
            {...props}
        />
    );
}

export function RentalPaymentMethodLookupSelect({ direction = 'inbound', ...props }: LookupProps & { direction?: 'inbound' | 'outbound' }) {
    const search = useCallback(
        (params: LookupLoadParams) => searchPaymentMethods(params, direction),
        [direction],
    );

    return <LookupSelect label="Payment method" search={search} loadOnOpen minSearchLength={0} {...props} />;
}

export function RentalInvoiceLookupSelect(props: LookupProps) {
    return <LookupSelect label="Invoice" search={searchInvoices} placeholder="Search invoice number or party..." {...props} />;
}

export function RentalTaxGroupLookupSelect(props: LookupProps) {
    return <LookupSelect label="Tax group" search={searchTaxGroups} loadOnOpen minSearchLength={0} {...props} />;
}

async function searchAgreements(
    { search, page, perPage, signal }: LookupLoadParams,
    direction?: 'inbound' | 'outbound',
): Promise<LookupResult<NamedResource>> {
    const agreementKind = direction === 'inbound'
        ? 'customer_rental'
        : direction === 'outbound'
            ? 'owner_supply'
            : undefined;
    const response = await listRentalAgreements({
        search,
        page,
        per_page: perPage,
        agreement_kind: agreementKind,
    }, signal);

    return {
        data: response.data.map((agreement) => ({
            id: agreement.id,
            code: agreement.agreement_number,
            name: [
                agreement.agreement_number,
                agreement.customer?.name ?? agreement.supplier?.name,
                agreement.status,
            ].filter(Boolean).join(' - '),
        })),
        links: response.links,
        meta: response.meta,
    };
}

async function searchAllocations(
    { search, page, perPage, signal }: LookupLoadParams,
    filters: AllocationLookupFilters = {},
): Promise<LookupResult<NamedResource>> {
    const response = await listRentalAllocations({
        search,
        page,
        per_page: perPage,
        agreement_id: filters.agreementId ?? undefined,
        vehicle_id: filters.vehicleId ?? undefined,
        agreement_kind: filters.agreementKind ?? undefined,
        covers_start_at: filters.coversStartAt ?? undefined,
        covers_end_at: filters.coversEndAt ?? undefined,
        open_only: filters.openOnly,
    }, signal);

    return {
        data: response.data.map((allocation) => ({
            id: allocation.id,
            code: allocation.allocation_number,
            name: [
                allocation.allocation_number,
                allocation.vehicle?.registration_number ?? allocation.vehicle?.name,
                allocation.status,
            ].filter(Boolean).join(' - '),
        })),
        links: response.links,
        meta: response.meta,
    };
}

async function searchFinanceAgreements(
    { search, page, perPage, signal }: LookupLoadParams,
    filters: FinanceLookupFilters = {},
): Promise<LookupResult<NamedResource>> {
    const response = await listVehicleFinanceAgreements({
        search,
        page,
        per_page: perPage,
        vehicle_id: filters.vehicleId ?? undefined,
        status: filters.activeOnly ? 'active' : undefined,
        covers_start_at: filters.coversStartAt ?? undefined,
        covers_end_at: filters.coversEndAt ?? undefined,
    }, signal);

    return {
        data: response.data.map((agreement) => ({
            id: agreement.id,
            code: agreement.agreement_number,
            name: [
                agreement.agreement_number,
                agreement.vehicle?.registration_number ?? agreement.vehicle?.name,
                agreement.supplier?.name,
                agreement.status,
            ].filter(Boolean).join(' - '),
        })),
        links: response.links,
        meta: response.meta,
    };
}

async function searchAvailableVehicles(
    { search, page, perPage, signal }: LookupLoadParams,
    startAt?: string | null,
    endAt?: string | null,
): Promise<LookupResult<AvailableVehicleOption>> {
    if (!startAt || !endAt) {
        return emptyLookupResult();
    }

    const response = await listAvailableRentalVehicles({
        search,
        page,
        per_page: perPage,
        start_at: startAt,
        end_at: endAt,
    }, signal);

    return {
        data: response.data.map((vehicle) => availableVehicleOption(vehicle)),
        links: response.links,
        meta: response.meta,
    };
}

function availableVehicleOption(vehicle: RentalVehicle): AvailableVehicleOption {
    return {
        ...(vehicle as unknown as VehicleSummary),
        name: vehicle.vehicle_number ?? vehicle.registration_number ?? `Vehicle ${vehicle.id}`,
    };
}

function emptyLookupResult<T extends NamedResource>(): LookupResult<T> {
    return {
        data: [],
        links: {},
        meta: {
            current_page: 1,
            from: null,
            last_page: 1,
            per_page: 25,
            to: null,
            total: 0,
        },
    };
}

async function searchPaymentMethods(
    { search, page, perPage, signal }: LookupLoadParams,
    direction: 'inbound' | 'outbound',
): Promise<LookupResult<NamedResource>> {
    const response = await listPaymentMethods({
        search,
        page,
        per_page: perPage,
        direction,
        is_active: true,
    }, signal);

    return {
        data: response.data.map((method) => ({
            id: method.id,
            code: method.code ?? '',
            name: `${method.name} - ${method.method_type.replaceAll('_', ' ')}`,
        })),
        links: response.links,
        meta: response.meta,
    };
}

async function searchInvoices(
    { search, page, perPage, signal }: LookupLoadParams,
): Promise<LookupResult<NamedResource>> {
    const response = await listInvoices({ search, page, per_page: perPage }, signal);

    return {
        data: response.data.map((invoice) => ({
            id: invoice.id,
            code: invoice.invoice_number ?? '',
            name: [invoice.invoice_number ?? `Invoice ${invoice.id}`, invoice.party?.name, invoice.status]
                .filter(Boolean)
                .join(' - '),
        })),
        links: response.links,
        meta: response.meta,
    };
}

async function searchTaxGroups(
    { search, page, perPage, signal }: LookupLoadParams,
): Promise<LookupResult<NamedResource>> {
    const response = await listTaxGroups({ search, page, per_page: perPage }, signal);

    return {
        data: response.data.map((group) => ({
            id: group.id,
            code: group.code ?? '',
            name: group.name,
        })),
        links: response.links,
        meta: response.meta,
    };
}
