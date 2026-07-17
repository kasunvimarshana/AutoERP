import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { TestRouter } from "@/test/TestRouter";
import type { RentalAgreementDocumentSnapshot } from "../vehicleRentalTypes";
import { RentalAgreementPrintDocument } from "./RentalAgreementPrintDocument";

describe("RentalAgreementPrintDocument", () => {
    it("shows the immutable component Tax treatment", () => {
        render(
            <TestRouter>
                <RentalAgreementPrintDocument
                    backPath="/vehicle-rental/lessee-agreements/55"
                    snapshot={snapshot()}
                />
            </TestRouter>,
        );

        expect(screen.getByText("Customer base charge")).toBeInTheDocument();
        expect(screen.getByText("Taxable")).toBeInTheDocument();
        expect(screen.getByText("Due immediately")).toBeInTheDocument();
    });
});

function snapshot(): RentalAgreementDocumentSnapshot {
    return {
        version: 1,
        captured_at: "2026-07-17T08:00:00.000Z",
        agreement_number: "LE-55",
        agreement_kind: "customer_rental",
        agreement_date: "2026-07-01",
        executed_at: "2026-07-02T08:00:00.000Z",
        legal_context: "company",
        organization: { name: "AutoERP Rentals", code: "AUTO" },
        party: {
            type: "customer",
            id: 22,
            code: "CUS-22",
            name: "Lessee Customer",
        },
        period: {
            starts_at: "2026-07-02T08:00:00.000Z",
            ends_at: "2026-08-02T08:00:00.000Z",
        },
        commercial_terms: {
            rental_mode: "self_drive",
            billing_cycle: "monthly",
            billing_basis: "calendar_month",
            proration_rule: "exact_day_count",
            payment_term_days: 0,
            currency: { code: "LKR", name: "Sri Lankan Rupee" },
        },
        terms: [],
        rate_version: {
            version_number: 1,
            effective_from: "2026-07-02T08:00:00.000Z",
            effective_to: "2026-08-02T08:00:00.000Z",
            components: [
                {
                    component_code: "base_rental",
                    unit: "month",
                    rate: "100000.000000",
                    included_quantity: "0.000000",
                    multiplier: "1.000000",
                    is_taxable: true,
                },
            ],
        },
    } as RentalAgreementDocumentSnapshot;
}
