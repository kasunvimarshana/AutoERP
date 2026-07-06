import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import type { ReactNode } from "react";
import { RouterProvider, createMemoryRouter } from "react-router-dom";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { TestRouter } from "@/test/TestRouter";
import type { NamedResource } from "@/shared/types/common";
import {
    RENTAL_AGREEMENT_KIND,
} from "../rentalAgreementPresentation";
import RentalAgreementCreatePage from "./RentalAgreementCreatePage";
import RentalAgreementDetailPage from "./RentalAgreementDetailPage";
import RentalAgreementListPage from "./RentalAgreementListPage";

const apiMocks = vi.hoisted(() => ({
    createRentalAgreement: vi.fn(),
    getRentalAgreement: vi.fn(),
    getRentalReservation: vi.fn(),
    listRentalUsageLogs: vi.fn(),
    listRentalAgreements: vi.fn(),
    transitionRentalAgreement: vi.fn(),
}));

vi.mock("../vehicleRentalApi", () => apiMocks);
vi.mock("../components/RentalPage", () => ({
    RentalPage: ({ children }: { children: ReactNode }) => <>{children}</>,
}));
vi.mock("@/modules/customer/components/CustomerLookupSelect", () => ({
    CustomerLookupSelect: ({
        value,
        onChange,
    }: {
        value: NamedResource | null;
        onChange: (value: NamedResource | null) => void;
    }) => (
        <button
            type="button"
            onClick={() =>
                onChange({
                    id: 22,
                    code: "CUS-22",
                    name: "Lessee Customer",
                })
            }
        >
            {value ? value.name : "Choose lessee customer"}
        </button>
    ),
}));
vi.mock("@/modules/supplier/components/SupplierLookupSelect", () => ({
    SupplierLookupSelect: ({
        value,
        onChange,
    }: {
        value: NamedResource | null;
        onChange: (value: NamedResource | null) => void;
    }) => (
        <button
            type="button"
            onClick={() =>
                onChange({
                    id: 33,
                    code: "SUP-33",
                    name: "Lessor Supplier",
                })
            }
        >
            {value ? value.name : "Choose lessor supplier"}
        </button>
    ),
}));
vi.mock("../components/RentalLookups", () => ({
    RentalCurrencyLookupSelect: ({
        value,
        onChange,
    }: {
        value: NamedResource | null;
        onChange: (value: NamedResource | null) => void;
    }) => (
        <button
            type="button"
            onClick={() => onChange({ id: 1, code: "LKR", name: "LKR" })}
        >
            {value ? value.name : "Choose currency"}
        </button>
    ),
}));

describe("RentalAgreement lessor flow", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.listRentalAgreements.mockResolvedValue(collection([]));
        apiMocks.listRentalUsageLogs.mockResolvedValue(collection([]));
        apiMocks.createRentalAgreement.mockResolvedValue({ id: 44 });
    });

    it("filters the lessor agreement list to supplier-side agreements", async () => {
        renderPage(
            <RentalAgreementListPage mode="lessor" />,
            "/vehicle-rental/lessor-agreements",
        );

        await waitFor(() =>
            expect(apiMocks.listRentalAgreements).toHaveBeenCalledWith(
                expect.objectContaining({
                    agreement_kind: RENTAL_AGREEMENT_KIND.ownerSupply,
                    per_page: 25,
                }),
                expect.any(AbortSignal),
            ),
        );

        expect(
            screen.getByRole("link", { name: "New lessor agreement" }),
        ).toHaveAttribute("href", "/vehicle-rental/lessor-agreements/create");
        expect(screen.queryByLabelText("Agreement kind")).not.toBeInTheDocument();
    });

    it("submits lessor creation as an owner supply agreement", async () => {
        const user = userEvent.setup();
        renderPage(
            <RentalAgreementCreatePage mode="lessor" />,
            "/vehicle-rental/lessor-agreements/create",
        );

        await user.click(
            screen.getByRole("button", { name: "Choose lessor supplier" }),
        );
        await user.click(screen.getByRole("button", { name: "Choose currency" }));
        await user.type(screen.getByLabelText("Agreement date"), "2026-07-06");
        await user.type(screen.getByLabelText("Start"), "2026-07-06T08:00");
        await user.type(screen.getByLabelText("End"), "2026-08-06T08:00");
        await user.click(
            screen.getByRole("button", { name: "Create lessor agreement" }),
        );

        await waitFor(() =>
            expect(apiMocks.createRentalAgreement).toHaveBeenCalledWith(
                expect.objectContaining({
                    agreement_kind: RENTAL_AGREEMENT_KIND.ownerSupply,
                    supplier_id: 33,
                    customer_id: null,
                    currency_id: 1,
                    deposit: undefined,
                }),
            ),
        );
    });
});

describe("RentalAgreement lessee flow", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.listRentalAgreements.mockResolvedValue(collection([]));
        apiMocks.listRentalUsageLogs.mockResolvedValue(collection([]));
        apiMocks.createRentalAgreement.mockResolvedValue({ id: 55 });
    });

    it("filters the lessee agreement list to customer-side agreements", async () => {
        renderPage(
            <RentalAgreementListPage mode="lessee" />,
            "/vehicle-rental/lessee-agreements",
        );

        await waitFor(() =>
            expect(apiMocks.listRentalAgreements).toHaveBeenCalledWith(
                expect.objectContaining({
                    agreement_kind: RENTAL_AGREEMENT_KIND.customerRental,
                    per_page: 25,
                }),
                expect.any(AbortSignal),
            ),
        );

        expect(
            screen.getByRole("link", { name: "New lessee agreement" }),
        ).toHaveAttribute("href", "/vehicle-rental/lessee-agreements/create");
        expect(screen.queryByLabelText("Agreement kind")).not.toBeInTheDocument();
    });

    it("submits lessee creation as a customer rental agreement", async () => {
        const user = userEvent.setup();
        renderPage(
            <RentalAgreementCreatePage mode="lessee" />,
            "/vehicle-rental/lessee-agreements/create",
        );

        await user.click(
            screen.getByRole("button", { name: "Choose lessee customer" }),
        );
        await user.click(screen.getByRole("button", { name: "Choose currency" }));
        await user.type(screen.getByLabelText("Agreement date"), "2026-07-06");
        await user.type(screen.getByLabelText("Start"), "2026-07-06T08:00");
        await user.type(screen.getByLabelText("End"), "2026-08-06T08:00");
        await user.clear(screen.getByLabelText("Security deposit"));
        await user.type(screen.getByLabelText("Security deposit"), "1000");
        await user.click(
            screen.getByRole("button", { name: "Create lessee agreement" }),
        );

        await waitFor(() =>
            expect(apiMocks.createRentalAgreement).toHaveBeenCalledWith(
                expect.objectContaining({
                    agreement_kind: RENTAL_AGREEMENT_KIND.customerRental,
                    customer_id: 22,
                    supplier_id: null,
                    currency_id: 1,
                    deposit: expect.objectContaining({
                        required_amount: "1000",
                        currency_id: 1,
                        is_refundable: true,
                    }),
                }),
            ),
        );
    });
});

describe("RentalAgreement running chart data", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.listRentalUsageLogs.mockResolvedValue(collection([
            usageLog("revenue"),
        ]));
    });

    it("loads lessee running chart revenue data on the lessee agreement detail", async () => {
        apiMocks.getRentalAgreement.mockResolvedValue(agreement("customer_rental"));

        renderRoute(
            "/vehicle-rental/lessee-agreements/:id",
            <RentalAgreementDetailPage mode="lessee" />,
            "/vehicle-rental/lessee-agreements/55",
        );

        await screen.findByText("Lessee running chart");
        await waitFor(() =>
            expect(apiMocks.listRentalUsageLogs).toHaveBeenCalledWith(
                expect.objectContaining({
                    agreement_id: 55,
                    financial_side: "revenue",
                    per_page: 25,
                }),
                expect.any(AbortSignal),
            ),
        );
        expect(await screen.findAllByText("RUL-1")).not.toHaveLength(0);
        expect(await screen.findAllByText("LESSEE-ALLOC")).not.toHaveLength(0);
    });

    it("loads lessor running chart cost data on the lessor agreement detail", async () => {
        apiMocks.getRentalAgreement.mockResolvedValue(agreement("owner_supply"));
        apiMocks.listRentalUsageLogs.mockResolvedValue(collection([
            usageLog("cost"),
        ]));

        renderRoute(
            "/vehicle-rental/lessor-agreements/:id",
            <RentalAgreementDetailPage mode="lessor" />,
            "/vehicle-rental/lessor-agreements/55",
        );

        await screen.findByText("Lessor running chart");
        await waitFor(() =>
            expect(apiMocks.listRentalUsageLogs).toHaveBeenCalledWith(
                expect.objectContaining({
                    agreement_id: 55,
                    financial_side: "cost",
                    per_page: 25,
                }),
                expect.any(AbortSignal),
            ),
        );
        expect(await screen.findAllByText("RUL-1")).not.toHaveLength(0);
        expect(await screen.findAllByText("LESSOR-ALLOC")).not.toHaveLength(0);
    });

    it("blocks actions when the route mode does not match the agreement kind", async () => {
        apiMocks.getRentalAgreement.mockResolvedValue(agreement("owner_supply"));

        renderRoute(
            "/vehicle-rental/lessee-agreements/:id",
            <RentalAgreementDetailPage mode="lessee" />,
            "/vehicle-rental/lessee-agreements/55",
        );

        expect(await screen.findByRole("alert")).toHaveTextContent(
            "This record is not a lessee agreement.",
        );
        expect(
            screen.getByRole("link", { name: "Open lessor agreement" }),
        ).toHaveAttribute("href", "/vehicle-rental/lessor-agreements/55");
        expect(
            screen.queryByRole("link", { name: "Allocations" }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole("button", { name: "Terminate" }),
        ).not.toBeInTheDocument();
        expect(screen.queryByText("Lessor running chart")).not.toBeInTheDocument();
        expect(apiMocks.listRentalUsageLogs).not.toHaveBeenCalled();
    });
});

function renderPage(page: ReactNode, path: string) {
    return render(
        <TestRouter initialEntries={[path]}>
            {page}
        </TestRouter>,
    );
}

function renderRoute(path: string, element: ReactNode, initialEntry: string) {
    const router = createMemoryRouter([{ path, element }], {
        initialEntries: [initialEntry],
    });

    return render(<RouterProvider router={router} />);
}

function agreement(kind: "customer_rental" | "owner_supply") {
    return {
        id: 55,
        row_version: 1,
        agreement_number: kind === "customer_rental" ? "LE-55" : "LO-55",
        agreement_kind: kind,
        customer:
            kind === "customer_rental"
                ? { id: 22, name: "Lessee Customer" }
                : null,
        supplier:
            kind === "owner_supply"
                ? { id: 33, name: "Lessor Supplier" }
                : null,
        agreement_date: "2026-07-06",
        starts_at: "2026-07-06T08:00:00.000Z",
        ends_at: "2026-08-06T08:00:00.000Z",
        legal_context: "company",
        rental_mode: "with_driver",
        billing_cycle: "monthly",
        billing_basis: "calendar_month",
        proration_rule: "exact_day_count",
        billing_timezone: "UTC",
        payment_term_days: 30,
        currency: { id: 1, code: "LKR", name: "LKR" },
        status: "active",
        remarks: null,
        active_rate_version: null,
        rate_versions: [],
        allocations: [],
        deposit_requirement: null,
    };
}

function usageLog(side: "revenue" | "cost") {
    return {
        id: 77,
        row_version: 1,
        usage_number: "RUL-1",
        allocation: { id: 88, code: "PHYSICAL-ALLOC", name: "PHYSICAL-ALLOC" },
        vehicle: { id: 12, name: "CAR-1000", registration_number: "CAR-1000" },
        driver_assignment: null,
        driver: null,
        usage_date: "2026-07-07",
        started_at: "2026-07-07T08:00:00.000Z",
        ended_at: "2026-07-07T17:00:00.000Z",
        start_odometer: "1000.000000",
        end_odometer: "1125.000000",
        distance_km: "125.000000",
        net_operational_distance_km: "120.000000",
        garage_distance_km: "5.000000",
        internal_distance_km: "0.000000",
        working_minutes: 540,
        normal_overtime_minutes: 0,
        double_overtime_minutes: 0,
        triple_overtime_minutes: 0,
        night_out_count: "0.000000",
        status: "approved",
        events: [],
        contexts: [
            {
                id: 90,
                financial_side: side,
                agreement: { id: 55, name: side === "revenue" ? "LE-55" : "LO-55" },
                allocation: {
                    id: side === "revenue" ? 88 : 99,
                    name: side === "revenue" ? "LESSEE-ALLOC" : "LESSOR-ALLOC",
                },
                rate_version: null,
                customer: side === "revenue" ? { id: 22, name: "Lessee Customer" } : null,
                supplier: side === "cost" ? { id: 33, name: "Lessor Supplier" } : null,
                usage_fact: {
                    id: 91,
                    row_version: 1,
                    financial_side: side,
                    started_at: "2026-07-07T08:00:00.000Z",
                    ended_at: "2026-07-07T17:00:00.000Z",
                    start_odometer: "1000.000000",
                    end_odometer: "1125.000000",
                    commercial_distance_km: "120.000000",
                    working_minutes: 540,
                    normal_overtime_minutes: 0,
                    double_overtime_minutes: 0,
                    triple_overtime_minutes: 0,
                    night_out_count: "0.000000",
                    status: "approved",
                },
            },
        ],
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
            to: data.length,
            total: data.length,
        },
    };
}
