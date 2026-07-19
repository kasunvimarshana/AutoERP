export const RENTAL_AGREEMENT_KIND = {
    customerRental: "customer_rental",
    ownerSupply: "owner_supply",
} as const;

export type RentalAgreementKindValue =
    (typeof RENTAL_AGREEMENT_KIND)[keyof typeof RENTAL_AGREEMENT_KIND];

export type RentalAgreementPageMode = "standard" | "lessee" | "lessor";
export type RentalAgreementFinancialSide = "revenue" | "cost";

export const RENTAL_AGREEMENT_KIND_OPTIONS = [
    {
        value: RENTAL_AGREEMENT_KIND.customerRental,
        label: "Lessee agreement",
    },
    {
        value: RENTAL_AGREEMENT_KIND.ownerSupply,
        label: "Lessor agreement",
    },
] as const;

const AGREEMENT_KIND_LABELS: Record<RentalAgreementKindValue, string> = {
    [RENTAL_AGREEMENT_KIND.customerRental]: "Lessee agreement",
    [RENTAL_AGREEMENT_KIND.ownerSupply]: "Lessor agreement",
};

export function isRentalAgreementKind(
    value: string | null | undefined,
): value is RentalAgreementKindValue {
    return Object.values(RENTAL_AGREEMENT_KIND).includes(
        value as RentalAgreementKindValue,
    );
}

export function rentalAgreementKindLabel(kind: string): string {
    return isRentalAgreementKind(kind)
        ? AGREEMENT_KIND_LABELS[kind]
        : kind.replaceAll("_", " ");
}

export function rentalAgreementPartyLabel(kind: string): string {
    return kind === RENTAL_AGREEMENT_KIND.ownerSupply ? "Lessor" : "Lessee";
}

export function rentalAgreementFinancialSide(
    kind: string,
): RentalAgreementFinancialSide {
    return kind === RENTAL_AGREEMENT_KIND.ownerSupply ? "cost" : "revenue";
}

export function defaultAgreementKindForMode(
    mode: RentalAgreementPageMode,
): RentalAgreementKindValue | "" {
    if (mode === "lessee") {
        return RENTAL_AGREEMENT_KIND.customerRental;
    }

    return mode === "lessor" ? RENTAL_AGREEMENT_KIND.ownerSupply : "";
}

export function agreementDetailPath(
    mode: RentalAgreementPageMode,
    agreementId: number,
): string {
    if (mode === "lessee") {
        return `/vehicle-rental/lessee-agreements/${agreementId}`;
    }

    if (mode === "lessor") {
        return `/vehicle-rental/lessor-agreements/${agreementId}`;
    }

    return `/vehicle-rental/agreements/${agreementId}`;
}

export function agreementEditPath(
    mode: RentalAgreementPageMode,
    agreementId: number,
): string {
    if (mode === "lessee") {
        return `/vehicle-rental/lessee-agreements/${agreementId}/edit`;
    }

    if (mode === "lessor") {
        return `/vehicle-rental/lessor-agreements/${agreementId}/edit`;
    }

    return `/vehicle-rental/agreements/${agreementId}/edit`;
}

export function agreementListPath(mode: RentalAgreementPageMode): string {
    if (mode === "lessee") {
        return "/vehicle-rental/lessee-agreements";
    }

    if (mode === "lessor") {
        return "/vehicle-rental/lessor-agreements";
    }

    return "/vehicle-rental/agreements";
}
