import type { ReactNode } from "react";
import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { createMemoryRouter, RouterProvider } from "react-router-dom";
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

describe("Rental agreement activation review", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.listRentalUsageLogs.mockResolvedValue(collection([]));
        apiMocks.transitionRentalAgreement.mockResolvedValue({});
    });

    it("shows the complete Draft commercial terms before activation", async () => {
        apiMocks.getRentalAgreement.mockResolvedValue(draftAgreement());
        renderDetail();

        expect(
            await screen.findByRole("heading", {
                name: "Draft commercial terms",
            }),
        ).toBeInTheDocument();
        expect(screen.getByText("Customer base charge")).toBeInTheDocument();
        expect(screen.getByText("Taxable")).toBeInTheDocument();
        expect(
            screen.getByText("Ready for final activation review."),
        ).toBeInTheDocument();
        expect(
            screen.getByRole("button", { name: "Review and activate" }),
        ).toBeEnabled();
    });

    it("confirms the immutable review before activation", async () => {
        const user = userEvent.setup();
        apiMocks.getRentalAgreement.mockResolvedValue(draftAgreement());
        renderDetail();

        await user.click(
            await screen.findByRole("button", {
                name: "Review and activate",
            }),
        );
        await user.click(
            screen.getByRole("button", { name: "Activate agreement" }),
        );

        await waitFor(() =>
            expect(apiMocks.transitionRentalAgreement).toHaveBeenCalledWith(
                55,
                4,
                "active",
            ),
        );
    });

    it("explains blockers instead of allowing incomplete activation", async () => {
        apiMocks.getRentalAgreement.mockResolvedValue({
            ...draftAgreement(),
            executed_at: null,
        });
        renderDetail();

        expect(
            await screen.findByText("Record the signed/executed date."),
        ).toBeInTheDocument();
        expect(
            screen.getByRole("button", { name: "Review and activate" }),
        ).toBeDisabled();
    });
});

function renderDetail() {
    const router = createMemoryRouter(
        [
            {
                path: "/vehicle-rental/lessee-agreements/:id",
                element: <RentalAgreementDetailPage mode="lessee" />,
            },
        ],
        { initialEntries: ["/vehicle-rental/lessee-agreements/55"] },
    );

    return render(<RouterProvider router={router} />);
}

function draftAgreement() {
    const rateVersion = {
        id: 8,
        row_version: 2,
        version_number: 1,
        effective_from: "2026-07-01T08:00:00.000Z",
        effective_to: "2026-08-01T08:00:00.000Z",
        driver_mode: "self_drive",
        billing_cycle: "monthly",
        billing_basis: "calendar_month",
        proration_rule: "exact_day_count",
        excess_km_method: "period",
        included_km: "3000.000000",
        status: "draft",
        components: [
            {
                id: 9,
                row_version: 1,
                component_code: "base_rental",
                unit: "month",
                rate: "150000.000000",
                multiplier: "1.000000",
                calculation_order: 1,
                is_taxable: true,
            },
        ],
    };

    return {
        id: 55,
        row_version: 4,
        agreement_number: "LE-55",
        agreement_kind: "customer_rental",
        customer: { id: 22, code: "CUS-22", name: "Lessee Customer" },
        supplier: null,
        agreement_date: "2026-07-01",
        executed_at: "2026-07-01T08:00:00.000Z",
        starts_at: "2026-07-01T08:00:00.000Z",
        ends_at: "2026-08-01T08:00:00.000Z",
        legal_context: "company",
        rental_mode: "self_drive",
        billing_cycle: "monthly",
        billing_basis: "calendar_month",
        proration_rule: "exact_day_count",
        payment_term_days: 30,
        currency: { id: 1, code: "LKR", name: "Sri Lankan Rupee" },
        status: "draft",
        remarks: null,
        terms: [],
        rate_versions: [rateVersion],
        active_rate_version: null,
        allocations: [],
        deposit_requirement: null,
        document_snapshot: null,
    };
}

function collection<T>(data: T[]) {
    return {
        data,
        links: {},
        meta: {
            current_page: 1,
            from: data.length ? 1 : null,
            last_page: 1,
            per_page: 25,
            to: data.length || null,
            total: data.length,
        },
    };
}
