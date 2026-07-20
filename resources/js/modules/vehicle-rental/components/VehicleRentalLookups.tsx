import { useCallback } from 'react';
import { searchCustomers } from '@/modules/customer/customerApi';
import { searchEmployees } from '@/modules/hr/hrApi';
import { searchSuppliers } from '@/modules/supplier/supplierApi';
import { searchVehicles } from '@/modules/vehicle/vehicleApi';
import { searchCurrencies } from '@/shared/api/referenceApi';
import { GenericLookupSelect } from '@/shared/components/GenericLookupSelect';
import type { NamedResource } from '@/shared/types/common';
import type { LookupLoadParams, LookupResult } from '@/shared/types/lookup';
import { localDateTimeToOffsetIso } from '../rentalDateTime';
import { listRentalAgreementLookup, listRentalAssignmentLookup } from '../vehicleRentalApi';
import type {
    RentalAgreement,
    RentalAgreementKind,
    RentalAssignment,
    RentalAssignmentSide,
    RentalBillingBasis,
    RentalReference,
} from '../vehicleRentalTypes';

export interface RentalLookupOption extends NamedResource {
    subtitle?: string | null;
    billingBasis?: RentalBillingBasis;
    startsOn?: string | null;
    endsOn?: string | null;
    defaultCurrency?: RentalReference | null;
    selfDrive?: boolean;
    assignmentStartsAt?: string | null;
    assignmentEndsAt?: string | null;
    handoverOdometer?: string | null;
    driver?: RentalReference | null;
    vehicle?: RentalReference | null;
    agreement?: RentalReference | null;
    ownerAgreement?: RentalReference | null;
    party?: RentalReference | null;
}

interface LookupProps {
    value: RentalReference | null;
    onChange: (value: RentalLookupOption | null) => void;
    error?: string;
    disabled?: boolean;
    required?: boolean;
}

const reference = (value: RentalReference | null): RentalLookupOption | null => value ? {
    id: value.id,
    code: value.code ?? undefined,
    name: value.name || value.code || `#${value.id}`,
} : null;

const mapResult = <T,>(
    result: LookupResult<T>,
    mapper: (value: T) => RentalLookupOption,
): LookupResult<RentalLookupOption> => ({
    ...result,
    data: result.data.map(mapper),
});

export function RentalCustomerLookup({ value, onChange, error, disabled, required }: LookupProps) {
    const search = useCallback(async (params: LookupLoadParams) => mapResult(
        await searchCustomers(params),
        (item) => ({
            id: item.id,
            code: item.code,
            name: item.name || item.display_name || item.code,
            defaultCurrency: namedReference(item.default_currency),
        }),
    ), []);

    return <ReferenceLookup label="Customer" value={value} onChange={onChange} search={search} error={error} disabled={disabled} required={required} />;
}

export function RentalSupplierLookup({ value, onChange, error, disabled, required }: LookupProps) {
    const search = useCallback(async (params: LookupLoadParams) => mapResult(
        await searchSuppliers(params),
        (item) => ({
            id: item.id,
            code: item.code,
            name: item.name || item.display_name || item.code,
            defaultCurrency: namedReference(item.default_currency),
        }),
    ), []);

    return <ReferenceLookup label="Owner / supplier" value={value} onChange={onChange} search={search} error={error} disabled={disabled} required={required} />;
}

export function RentalCurrencyLookup({ value, onChange, error, disabled, required }: LookupProps) {
    const search = useCallback(async (params: LookupLoadParams) => mapResult(
        await searchCurrencies(params),
        (item) => ({ id: item.id, code: item.code, name: item.name }),
    ), []);

    return <ReferenceLookup label="Currency" value={value} onChange={onChange} search={search} error={error} disabled={disabled} required={required} loadOnOpen />;
}

export function RentalVehicleLookup({ value, onChange, error, disabled, required }: LookupProps) {
    const search = useCallback(async (params: LookupLoadParams) => mapResult(
        await searchVehicles(params),
        (item) => ({
            id: item.id,
            code: item.vehicle_number,
            name: item.registration_number || item.vehicle_number,
            subtitle: [item.make?.name, item.model?.name, item.status].filter(Boolean).join(' • '),
        }),
    ), []);

    return <ReferenceLookup label="Vehicle" value={value} onChange={onChange} search={search} error={error} disabled={disabled} required={required} />;
}

export function RentalDriverLookup({ value, onChange, error, disabled, required }: LookupProps) {
    const search = useCallback(async (params: LookupLoadParams) => mapResult(
        await searchEmployees(params),
        (item) => ({ id: item.id, code: item.employee_number, name: item.display_name }),
    ), []);

    return <ReferenceLookup label="Driver" value={value} onChange={onChange} search={search} error={error} disabled={disabled} required={required} />;
}

export function RentalAgreementLookup({
    value,
    onChange,
    kind,
    purpose,
    error,
    disabled,
    required,
}: LookupProps & { kind?: RentalAgreementKind; purpose?: 'assignment' | 'calculation' }) {
    const lookupPurpose = purpose ?? (kind ? 'assignment' : 'calculation');
    const search = useCallback(async (params: LookupLoadParams) => {
        const result = await listRentalAgreementLookup(lookupPurpose, {
            search: params.search || undefined,
            kind,
            agreement_status: 'active',
            page: params.page,
            per_page: params.perPage,
        }, params.signal);

        return mapResult(result, agreementOption);
    }, [kind, lookupPurpose]);

    return <ReferenceLookup label={kind === 'owner' ? 'Owner agreement' : kind === 'customer' ? 'Customer agreement' : 'Agreement'} value={value} onChange={onChange} search={search} error={error} disabled={disabled} required={required} loadOnOpen />;
}

export function RentalAssignmentLookup({
    value,
    onChange,
    side,
    purpose,
    vehicleId,
    startsAt,
    endsAt,
    error,
    disabled,
    required,
    label = 'Assignment',
}: LookupProps & {
    side?: RentalAssignmentSide;
    label?: string;
    purpose?: 'assignment-source' | 'running-chart';
    vehicleId?: number | null;
    startsAt?: string;
    endsAt?: string;
}) {
    const lookupPurpose = purpose ?? (side === 'owner_supply' ? 'assignment-source' : 'running-chart');
    const isAssignmentSource = lookupPurpose === 'assignment-source';
    const lookupContextKey = isAssignmentSource
        ? [lookupPurpose, side ?? '', vehicleId ?? '', startsAt ?? '', endsAt ?? ''].join(':')
        : [lookupPurpose, side ?? ''].join(':');
    const search = useCallback(async (params: LookupLoadParams) => {
        const result = await listRentalAssignmentLookup(lookupPurpose, {
            search: params.search || undefined,
            assignment_side: side,
            vehicle_id: isAssignmentSource ? vehicleId ?? undefined : undefined,
            date_from: isAssignmentSource && startsAt ? localDateTimeToOffsetIso(startsAt) : undefined,
            date_to: isAssignmentSource && endsAt ? localDateTimeToOffsetIso(endsAt) : undefined,
            page: params.page,
            per_page: params.perPage,
        }, params.signal);

        return mapResult(result, rentalAssignmentOption);
    }, [endsAt, isAssignmentSource, lookupPurpose, side, startsAt, vehicleId]);

    return <ReferenceLookup key={lookupContextKey} label={label} value={value} onChange={onChange} search={search} error={error} disabled={disabled} required={required} loadOnOpen />;
}

function ReferenceLookup({
    label,
    value,
    onChange,
    search,
    error,
    disabled,
    required,
    loadOnOpen = false,
}: LookupProps & {
    label: string;
    search: (params: LookupLoadParams) => Promise<LookupResult<RentalLookupOption>>;
    loadOnOpen?: boolean;
}) {
    return (
        <GenericLookupSelect
            label={label}
            value={reference(value)}
            onChange={onChange}
            search={search}
            formatLabel={(item) => [item.code, item.name].filter(Boolean).join(' - ')}
            error={error}
            disabled={disabled}
            required={required}
            loadOnOpen={loadOnOpen}
            minSearchLength={loadOnOpen ? 0 : 2}
        />
    );
}

function agreementOption(agreement: RentalAgreement): RentalLookupOption {
    const party = agreement.customer ?? agreement.supplier;
    return {
        id: agreement.id,
        code: agreement.agreement_number,
        name: party?.name || party?.code || agreement.agreement_number,
        subtitle: agreement.kind,
        billingBasis: agreement.billing_basis,
        startsOn: agreement.starts_on,
        endsOn: agreement.ends_on,
        defaultCurrency: agreement.currency ?? null,
    };
}

export function rentalAssignmentOption(assignment: RentalAssignment): RentalLookupOption {
    const agreement = assignment.agreement?.code || assignment.agreement?.name || `Agreement #${assignment.agreement?.id ?? ''}`;
    const vehicle = assignment.vehicle?.name || assignment.vehicle?.code || `Vehicle #${assignment.vehicle?.id ?? ''}`;
    return {
        id: assignment.id,
        code: agreement,
        name: vehicle,
        subtitle: assignment.side === 'customer_use' ? 'Customer vehicle' : 'Owner-supplied vehicle',
        selfDrive: assignment.self_drive,
        assignmentStartsAt: assignment.starts_at,
        assignmentEndsAt: assignment.ends_at,
        handoverOdometer: assignment.handover_odometer,
        driver: assignment.driver ?? null,
        vehicle: assignment.vehicle ?? null,
        agreement: assignment.agreement ?? null,
        party: assignment.agreement?.party ?? null,
        ownerAgreement: assignment.source_assignment?.agreement ?? null,
    };
}

function namedReference(value?: NamedResource | null): RentalReference | null {
    if (!value) return null;
    return { id: value.id, code: value.code ?? null, name: value.name ?? null };
}
