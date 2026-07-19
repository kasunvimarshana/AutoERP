import { render, screen } from "@testing-library/react";
import type { ReactNode } from "react";
import { RouterProvider, createMemoryRouter } from "react-router-dom";
import { beforeEach, describe, expect, it, vi } from "vitest";
import RentalAgreementDetailPage from "./RentalAgreementDetailPage";

const apiMocks = vi.hoisted(() => ({
    deleteRentalAgreement: vi.fn(),
    getRentalAgreement: vi.fn(),
    listRentalUsageLogs: vi.fn(),
    transitionRentalAgreement: vi.fn(),
}));

vi.mock("../vehicleRentalApi", () => apiMocks);
vi.mock("../components/RentalPage", () => ({
    RentalPage: ({ children }: { children: ReactNode }) => <>{children}</>,
}));

const EMPTY_COLLECTION = {
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

function draftLesseeAgreement() {
    return {
        id: 55,
        row_version: 1,
        agreement_number: "LE-55",
        agreement_kind: "customer_rental",
        customer: { id: 22, name: "Lessee Customer" },
        supplier: null,
        agreement_date: "2026-07-18",
        executed_at: "2026-07-18T09:00:00.000Z",
        starts_at: "2026-07-18T08:00:00.000Z",
        ends_at: "2026-08-18T08:00:00.000Z",
        legal_context: "company",
        rental_mode: "with_driver",
        billing_cycle: "monthly",
        billing_basis: "calendar_month",
        proration_rule: "exact_day_count",
        billing_timezone: "UTC",
        payment_term_days: 30,
        currency: { id: 1, code: "LKR", name: "LKR" },
        status: "draft",
        remarks: null,
        terms: [],
        document_snapshot: null,
        active_rate_version: null,
        rate_versions: [
            {
                id: 201,
                row_version: 1,
                version_number: 1,
                effective_from: "2026-07-18T08:00:00.000Z",
                effective_to: null,
                driver_mode: "with_driver",
                billing_cycle: "monthly",
                billing_basis: "calendar_month",
                proration_rule: "exact_day_count",
                excess_km_method: "period",
                included_km: "0.000000",
                included_hours: "0.000000",
                weekday_included_minutes: 0,
                saturday_included_minutes: 0,
                holiday_included_minutes: 0,
                status: "draft",
                components: [
                    {
                        id: 301,
                        component_code: "base_rental",
                        rate: "100000.000000",
                        unit: "month",
                        is_taxable: true,
                    },
                ],
            },
        ],
        allocations: [],
        deposit_requirement: null,
    };
}

describe("RentalAgreementDetailPage draft allocation action", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.getRentalAgreement.mockResolvedValue(draftLesseeAgreement());
        apiMocks.listRentalUsageLogs.mockResolvedValue(EMPTY_COLLECTION);
    });

    it("allows a draft lessee agreement to create its planned vehicle allocation", async () => {
        const router = createMemoryRouter(
            [
                {
                    path: "/vehicle-rental/lessee-agreements/:id",
                    element: <RentalAgreementDetailPage mode="lessee" />,
                },
                {
                    path: "/vehicle-rental/allocations",
                    element: <div>Vehicle allocations</div>,
                },
            ],
            {
                initialEntries: ["/vehicle-rental/lessee-agreements/55"],
            },
        );

        render(<RouterProvider router={router} />);

        const allocationActions = await screen.findAllByRole("link", {
            name: "Assign vehicle",
        });
        expect(allocationActions.length).toBeGreaterThan(0);
        for (const action of allocationActions) {
            expect(action).toHaveAttribute(
                "href",
                "/vehicle-rental/allocations?agreement_id=55",
            );
        }
        expect(
            screen.getByText(
                "No vehicle allocation yet. Create a planned allocation for this agreement.",
            ),
        ).toBeInTheDocument();
    });
});
