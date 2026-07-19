import { humanize } from "@/shared/utils/object";
import { RENTAL_AGREEMENT_KIND } from "./rentalAgreementPresentation";

export const RENTAL_MODE = {
    withDriver: "with_driver",
    selfDrive: "self_drive",
    vehicleOnly: "vehicle_only",
} as const;

export const RENTAL_RATE_COMPONENT = {
    baseRental: "base_rental",
    excessKm: "excess_km",
    driverSalary: "driver_salary",
    normalOvertime: "normal_overtime",
    doubleOvertime: "double_overtime",
    tripleOvertime: "triple_overtime",
    nightOut: "night_out",
} as const;

const DRIVER_RATE_COMPONENTS = new Set<string>([
    RENTAL_RATE_COMPONENT.driverSalary,
    RENTAL_RATE_COMPONENT.normalOvertime,
    RENTAL_RATE_COMPONENT.doubleOvertime,
    RENTAL_RATE_COMPONENT.tripleOvertime,
    RENTAL_RATE_COMPONENT.nightOut,
]);

const DEFAULT_VISIBLE_RATE_COMPONENTS = new Set<string>([
    RENTAL_RATE_COMPONENT.baseRental,
    RENTAL_RATE_COMPONENT.excessKm,
]);

const RATE_LABELS: Record<string, { lessee: string; lessor: string }> = {
    [RENTAL_RATE_COMPONENT.baseRental]: {
        lessee: "Customer base charge",
        lessor: "Owner base payment",
    },
    [RENTAL_RATE_COMPONENT.excessKm]: {
        lessee: "Customer excess KM charge",
        lessor: "Owner excess KM payment",
    },
    [RENTAL_RATE_COMPONENT.driverSalary]: {
        lessee: "Customer driver charge",
        lessor: "Owner driver reimbursement",
    },
    [RENTAL_RATE_COMPONENT.normalOvertime]: {
        lessee: "Customer normal overtime charge",
        lessor: "Owner normal overtime reimbursement",
    },
    [RENTAL_RATE_COMPONENT.doubleOvertime]: {
        lessee: "Customer double overtime charge",
        lessor: "Owner double overtime reimbursement",
    },
    [RENTAL_RATE_COMPONENT.tripleOvertime]: {
        lessee: "Customer triple overtime charge",
        lessor: "Owner triple overtime reimbursement",
    },
    [RENTAL_RATE_COMPONENT.nightOut]: {
        lessee: "Customer night-out charge",
        lessor: "Owner night-out reimbursement",
    },
};

export function isLesseeAgreementKind(kind: string): boolean {
    return kind === RENTAL_AGREEMENT_KIND.customerRental;
}

export function rentalAgreementPartyInputLabel(kind: string): string {
    return isLesseeAgreementKind(kind)
        ? "Customer / Lessee"
        : "Vehicle owner / Lessor";
}

export function rentalAgreementRatePanelTitle(kind: string): string {
    return isLesseeAgreementKind(kind)
        ? "Lessee billable core rates"
        : "Lessor payable core rates";
}

export function rentalAgreementRateLabel(kind: string, componentCode: string): string {
    const labels = RATE_LABELS[componentCode];
    if (!labels) return humanize(componentCode);

    return isLesseeAgreementKind(kind) ? labels.lessee : labels.lessor;
}

export function isDriverRateComponent(componentCode: string): boolean {
    return DRIVER_RATE_COMPONENTS.has(componentCode);
}

export function rateComponentAppliesToMode(
    componentCode: string,
    rentalMode: string,
): boolean {
    return !isDriverRateComponent(componentCode)
        || rentalMode === RENTAL_MODE.withDriver;
}

export function isDefaultVisibleRateComponent(componentCode: string): boolean {
    return DEFAULT_VISIBLE_RATE_COMPONENTS.has(componentCode);
}

export function rentalPeriodStartHint(kind: string): string {
    return isLesseeAgreementKind(kind)
        ? "When the customer rental contract starts. Vehicle assignment is handled separately."
        : "When the vehicle-owner supply contract starts. The supplied vehicle is allocated separately.";
}

export function rentalPeriodEndHint(kind: string): string {
    return isLesseeAgreementKind(kind)
        ? "When the customer rental contract ends."
        : "When the vehicle-owner supply contract ends.";
}

export function paymentTermsLabel(kind: string): string {
    return isLesseeAgreementKind(kind)
        ? "Customer payment term days"
        : "Owner payment term days";
}
