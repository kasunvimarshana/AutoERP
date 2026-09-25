import type { NamedResource } from '@/shared/types/common';

export enum AgreementKind { Customer = 'customer', Owner = 'owner' }
export enum AgreementStatus { Draft = 'draft', Active = 'active', Closed = 'closed' }
export enum RentalBasis { Daily = 'daily', Monthly = 'monthly' }
export enum DriverMode { SelfDrive = 'self_drive', WithDriver = 'with_driver' }
export enum AgreementAction { Activate = 'activate', Close = 'close' }
export const PAGE_SIZE = 25;
export const AGREEMENT_API = '/api/v1/vehicle-rental';
export const agreementPermissions = {
    [AgreementKind.Customer]: { view: 'vehicle-rental.customer-agreements.view', manage: 'vehicle-rental.customer-agreements.manage' },
    [AgreementKind.Owner]: { view: 'vehicle-rental.owner-agreements.view', manage: 'vehicle-rental.owner-agreements.manage' },
} as const;
export const TERM_LABELS = {
    base_rate: 'Base rental rate', included_km: 'Included distance (km)', excess_km_rate: 'Excess distance rate per km',
    non_ac_rate: 'Non-AC rate', front_ac_rate: 'Front AC rate', dual_ac_rate: 'Dual AC rate', driver_rate: 'Agreed driver amount',
    normal_ot_rate: 'Normal overtime hourly rate', double_ot_rate: 'Double overtime hourly rate', triple_ot_rate: 'Triple overtime hourly rate',
    night_out_rate: 'Night-out rate', deposit_requirement: 'Agreed security deposit',
} as const;
export type TermKey = keyof typeof TERM_LABELS;
export const CUSTOMER_ONLY_TERM_KEYS = new Set<TermKey>(['deposit_requirement']);
const OWNER_TERM_LABEL_OVERRIDES: Partial<Record<TermKey, string>> = {
    base_rate: 'Owner base rental payable',
    excess_km_rate: 'Owner excess distance payable per km',
    non_ac_rate: 'Owner Non-AC rate',
    front_ac_rate: 'Owner Front AC rate',
    dual_ac_rate: 'Owner Dual AC rate',
    driver_rate: 'Owner driver reimbursement amount',
    normal_ot_rate: 'Owner normal overtime reimbursement hourly rate',
    double_ot_rate: 'Owner double overtime reimbursement hourly rate',
    triple_ot_rate: 'Owner triple overtime reimbursement hourly rate',
    night_out_rate: 'Owner night-out reimbursement rate',
};
export const visibleTermKeys = (kind: AgreementKind): TermKey[] =>
    (Object.keys(TERM_LABELS) as TermKey[]).filter(key => kind === AgreementKind.Customer || !CUSTOMER_ONLY_TERM_KEYS.has(key));
export const termLabel = (kind: AgreementKind, key: TermKey): string =>
    kind === AgreementKind.Owner ? OWNER_TERM_LABEL_OVERRIDES[key] ?? TERM_LABELS[key] : TERM_LABELS[key];
export interface Agreement {
    id: number; reference: string; row_version: number; status: AgreementStatus; basis: RentalBasis; driver_mode: DriverMode;
    supersedes_agreement?: { id: number; reference: string } | null;
    party: NamedResource; currency: NamedResource; vehicle?: { id: number; vehicle_number: string; registration_number: string | null };
    agreed_on: string; executing_on: string | null; starts_on: string; ends_on: string | null; terms: Record<TermKey, string | null>; notes: string | null;
    activated_at: string | null; closed_at: string | null; closed_on: string | null;
}
export interface AgreementPayload {
    reference: string; party_id: number; currency_id: number; vehicle_id?: number; agreed_on: string; executing_on: string | null; starts_on: string; ends_on: string | null;
    basis: RentalBasis | ''; driver_mode: DriverMode | ''; terms: Partial<Record<TermKey, string | null>>; notes: string | null; expected_version?: number;
}
export interface AgreementSuccessorPayload {
    reference: string; agreed_on: string; executing_on: string | null; starts_on: string; ends_on: string | null; reason: string;
}
export const agreementPath = (kind: AgreementKind) => `/vehicle-rental/${kind}/agreements`;
