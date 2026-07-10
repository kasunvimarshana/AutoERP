import { act, render, screen, waitFor } from "@testing-library/react";
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

type MockParty = NamedResource & {
    default_currency?: NamedResource | null;
};

const apiMocks = vi.hoisted(() => ({
    createRentalAgreement: vi.fn(),
    deleteRentalAgreement: vi.fn(),
    getRentalAgreement: vi.fn(),
    getRentalMetadata: vi.fn(),
    getRentalReservation: vi.fn(),
    listRentalUsageLogs: vi.fn(),
    listRentalAgreements: vi.fn(),
    transitionRentalAgreement: vi.fn(),
    updateRentalAgreement: vi.fn(),
    customerDefaultCurrency: { current: null as NamedResource | null },
    supplierDefaultCurrency: { current: null as NamedResource | null },
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
        value: MockParty | null;
        onChange: (value: MockParty | null) => void;
    }) => (
        <button
            type="button"
            onClick={() =>
                onChange({
                    id: 22,
                    code: "CUS-22",
                    name: "Lessee Customer",
                    default_currency: apiMocks.customerDefaultCurrency.current,
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
        value: MockParty | null;
        onChange: (value: MockParty | null) => void;
    }) => (
        <button
            type="button"
            onClick={() =>
                onChange({
                    id: 33,
                    code: "SUP-33",
                    name: "Lessor Supplier",
                    default_currency: apiMocks.supplierDefaultCurrency.current,
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

describe("RentalAgreement list route changes", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.getRentalMetadata.mockResolvedValue(rentalMetadata());
        apiMocks.customerDefaultCurrency.current = null;
        apiMocks.supplierDefaultCurrency.current = null;
        apiMocks.listRentalUsageLogs.mockResolvedValue(collection([]));
        apiMocks.listRentalAgreements.mockImplementation(
            ({
                agreement_kind,
                page = 1,
            }: {
                agreement_kind?: string;
                page?: number;
            }) => {
                const response = collection([
                        agreement(
                            agreement_kind ===
                                RENTAL_AGREEMENT_KIND.ownerSupply
                                ? "owner_supply"
                                : "customer_rental",
                        ),
                    ]);
                response.meta.current_page = page;
                response.meta.last_page = 2;
                response.meta.total = 2;

                return Promise.resolve(response);
            },
        );
    });

    it("reloads owner-supply records when navigating from lessee to lessor", async () => {
        const user = userEvent.setup();
        const router = createMemoryRouter(
            [
                {
                    path: "/vehicle-rental/lessee-agreements",
                    element: <RentalAgreementListPage mode="lessee" />,
                },
                {
                    path: "/vehicle-rental/lessor-agreements",
                    element: <RentalAgreementListPage mode="lessor" />,
                },
            ],
            {
                initialEntries: ["/vehicle-rental/lessee-agreements"],
            },
        );
        render(<RouterProvider router={router} />);

        expect(await screen.findAllByText("Lessee Customer")).not.toHaveLength(
            0,
        );
        await user.click(
            screen.getByRole("button", { name: "Go to page 2" }),
        );
        await waitFor(() =>
            expect(apiMocks.listRentalAgreements).toHaveBeenCalledWith(
                expect.objectContaining({
                    agreement_kind: RENTAL_AGREEMENT_KIND.customerRental,
                    page: 2,
                }),
                expect.any(AbortSignal),
            ),
        );

        await act(async () => {
            await router.navigate("/vehicle-rental/lessor-agreements");
        });

        await waitFor(() =>
            expect(apiMocks.listRentalAgreements).toHaveBeenCalledWith(
                expect.objectContaining({
                    agreement_kind: RENTAL_AGREEMENT_KIND.ownerSupply,
                    page: 1,
                }),
                expect.any(AbortSignal),
            ),
        );
        expect(await screen.findAllByText("Lessor Supplier")).not.toHaveLength(
            0,
        );
        expect(screen.queryAllByText("Lessee Customer")).toHaveLength(0);
    });
});

describe("RentalAgreement lessor flow", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.getRentalMetadata.mockResolvedValue(rentalMetadata());
        apiMocks.customerDefaultCurrency.current = null;
        apiMocks.supplierDefaultCurrency.current = null;
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
        await user.type(
            screen.getByLabelText(/Executed date/),
            "2026-07-06",
        );
        await user.type(screen.getByLabelText("Start"), "2026-07-06T08:00");
        await user.type(screen.getByLabelText("End"), "2026-08-06T08:00");
        await user.type(
            screen.getByLabelText("Clause 1 content"),
            "The lessor supplies the vehicle under the payable rates shown.",
        );
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
                    terms: [
                        expect.objectContaining({
                            sequence: 1,
                            content:
                                "The lessor supplies the vehicle under the payable rates shown.",
                        }),
                    ],
                }),
            ),
        );
        expect(
            screen.getByRole("heading", {
                name: "Lessor payable core rates",
            }),
        ).toBeInTheDocument();
    });
});

describe("RentalAgreement lessee flow", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.getRentalMetadata.mockResolvedValue(rentalMetadata());
        apiMocks.customerDefaultCurrency.current = null;
        apiMocks.supplierDefaultCurrency.current = null;
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
        await user.type(
            screen.getByLabelText(/Executed date/),
            "2026-07-06",
        );
        await user.type(screen.getByLabelText("Start"), "2026-07-06T08:00");
        await user.type(screen.getByLabelText("End"), "2026-08-06T08:00");
        await user.type(
            screen.getByLabelText("Clause 1 content"),
            "The lessee hires the vehicle under the billable rates shown.",
        );
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
                    terms: [
                        expect.objectContaining({
                            sequence: 1,
                            content:
                                "The lessee hires the vehicle under the billable rates shown.",
                        }),
                    ],
                }),
            ),
        );
        expect(
            screen.getByRole("heading", {
                name: "Lessee billable core rates",
            }),
        ).toBeInTheDocument();
    });

    it("uses party currency defaults and saves optional draft execution terms", async () => {
        const user = userEvent.setup();
        apiMocks.getRentalMetadata.mockResolvedValue(
            rentalMetadata({ id: 1, code: "LKR", name: "Tenant Currency" }),
        );
        apiMocks.customerDefaultCurrency.current = {
            id: 2,
            code: "USD",
            name: "Customer Currency",
        };
        renderPage(
            <RentalAgreementCreatePage mode="lessee" />,
            "/vehicle-rental/lessee-agreements/create",
        );

        await user.click(
            screen.getByRole("button", { name: "Choose lessee customer" }),
        );
        await user.type(screen.getByLabelText("Agreement date"), "2026-07-06");
        await user.type(screen.getByLabelText("Start"), "2026-07-06T08:00");
        await user.type(screen.getByLabelText("End"), "2026-08-06T08:00");
        await user.click(
            screen.getByRole("button", { name: "Create lessee agreement" }),
        );

        await waitFor(() =>
            expect(apiMocks.createRentalAgreement).toHaveBeenCalledWith(
                expect.objectContaining({
                    currency_id: 2,
                    executed_at: null,
                    terms: [],
                }),
            ),
        );
    });
});

describe("RentalAgreement running chart data", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.getRentalMetadata.mockResolvedValue(rentalMetadata());
        apiMocks.customerDefaultCurrency.current = null;
        apiMocks.supplierDefaultCurrency.current = null;
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

    it("renders the activated agreement from its immutable document snapshot", async () => {
        apiMocks.getRentalAgreement.mockResolvedValue({
            ...agreement("customer_rental"),
            document_snapshot: documentSnapshot(),
        });

        renderRoute(
            "/vehicle-rental/lessee-agreements/:id",
            <RentalAgreementDetailPage mode="lessee" />,
            "/vehicle-rental/lessee-agreements/55?print=1",
        );

        expect(
            await screen.findByRole("heading", { name: "LE-55" }),
        ).toBeInTheDocument();
        expect(screen.getByText("Lessee Customer Snapshot")).toBeInTheDocument();
        expect(screen.getByText("Accepted lessee clause.")).toBeInTheDocument();
        expect(
            screen.getByRole("button", { name: "Print agreement" }),
        ).toBeInTheDocument();
        expect(apiMocks.listRentalUsageLogs).not.toHaveBeenCalled();
    });
});

describe("RentalAgreement draft CRUD actions", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.getRentalMetadata.mockResolvedValue(rentalMetadata());
        apiMocks.customerDefaultCurrency.current = null;
        apiMocks.supplierDefaultCurrency.current = null;
        apiMocks.listRentalUsageLogs.mockResolvedValue(collection([]));
        apiMocks.updateRentalAgreement.mockResolvedValue({ id: 55 });
        apiMocks.deleteRentalAgreement.mockResolvedValue({});
    });

    it("updates a loaded draft agreement without rate or deposit payloads", async () => {
        const user = userEvent.setup();
        apiMocks.getRentalAgreement.mockResolvedValue({
            ...agreement("customer_rental"),
            status: "draft",
            executed_at: null,
            terms: [],
        });

        renderRoute(
            "/vehicle-rental/lessee-agreements/:id/edit",
            <RentalAgreementCreatePage mode="lessee" />,
            "/vehicle-rental/lessee-agreements/55/edit",
        );

        expect(
            await screen.findByRole("heading", {
                name: "Edit lessee agreement",
            }),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole("heading", {
                name: "Lessee billable core rates",
            }),
        ).not.toBeInTheDocument();

        await user.click(
            screen.getByRole("button", { name: "Update lessee agreement" }),
        );

        await waitFor(() =>
            expect(apiMocks.updateRentalAgreement).toHaveBeenCalledWith(
                55,
                1,
                expect.not.objectContaining({
                    rate_version: expect.anything(),
                    deposit: expect.anything(),
                }),
            ),
        );
        expect(apiMocks.updateRentalAgreement).toHaveBeenCalledWith(
            55,
            1,
            expect.objectContaining({
                currency_id: 1,
                executed_at: null,
                terms: [],
            }),
        );
    });

    it("deletes a draft agreement with the loaded row version", async () => {
        const user = userEvent.setup();
        apiMocks.getRentalAgreement.mockResolvedValue({
            ...agreement("owner_supply"),
            status: "draft",
        });

        renderRoute(
            "/vehicle-rental/lessor-agreements/:id",
            <RentalAgreementDetailPage mode="lessor" />,
            "/vehicle-rental/lessor-agreements/55",
        );

        await user.click(await screen.findByRole("button", { name: "Delete" }));
        await user.click(screen.getByRole("button", { name: "Delete draft" }));

        await waitFor(() =>
            expect(apiMocks.deleteRentalAgreement).toHaveBeenCalledWith(55, 1),
        );
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
    const router = createMemoryRouter(
        [
            { path, element },
            {
                path: "/vehicle-rental/lessee-agreements/:id",
                element: <div>Lessee detail</div>,
            },
            {
                path: "/vehicle-rental/lessor-agreements/:id",
                element: <div>Lessor detail</div>,
            },
            {
                path: "/vehicle-rental/lessee-agreements",
                element: <div>Lessee list</div>,
            },
            {
                path: "/vehicle-rental/lessor-agreements",
                element: <div>Lessor list</div>,
            },
        ],
        {
            initialEntries: [initialEntry],
        },
    );

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
        executed_at: "2026-07-06T09:00:00.000Z",
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
        terms: [
            {
                id: 501,
                row_version: 1,
                sequence: 1,
                term_code: "GENERAL",
                title: "General terms",
                content: "The accepted agreement terms.",
                is_printable: true,
                is_active: true,
            },
        ],
        document_snapshot: null,
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

function documentSnapshot() {
    return {
        version: 1,
        captured_at: "2026-07-06T09:01:00.000Z",
        agreement_number: "LE-55",
        agreement_kind: "customer_rental",
        agreement_date: "2026-07-06",
        executed_at: "2026-07-06T09:00:00.000Z",
        legal_context: "company",
        organization: { name: "AutoERP Rentals", code: "AUTOERP" },
        party: {
            type: "customer",
            id: 22,
            code: "CUS-22",
            name: "Lessee Customer Snapshot",
        },
        period: {
            starts_at: "2026-07-06T08:00:00.000Z",
            ends_at: "2026-08-06T08:00:00.000Z",
        },
        commercial_terms: {
            rental_mode: "with_driver",
            billing_cycle: "monthly",
            billing_basis: "calendar_month",
            proration_rule: "exact_day_count",
            payment_term_days: 30,
            currency: { code: "LKR", name: "Sri Lankan Rupee", symbol: "Rs" },
            remarks: null,
        },
        terms: [
            {
                sequence: 1,
                term_code: "GENERAL",
                title: "General terms",
                content: "Accepted lessee clause.",
            },
        ],
        rate_version: {
            version_number: 1,
            effective_from: "2026-07-06T08:00:00.000Z",
            effective_to: null,
            components: [
                {
                    component_code: "base_rental",
                    unit: "month",
                    rate: "1250.000000",
                    included_quantity: "0.000000",
                    multiplier: "1.000000",
                },
            ],
        },
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

function rentalMetadata(defaultCurrency: NamedResource | null = null) {
    return {
        default_currency: defaultCurrency,
        agreement_kinds: ["customer_rental", "owner_supply"],
        agreement_statuses: [
            "draft",
            "active",
            "suspended",
            "completed",
            "terminated",
            "cancelled",
        ],
        allocation_statuses: [],
        rental_modes: ["with_driver", "self_drive", "vehicle_only"],
        billing_cycles: ["monthly"],
        billing_bases: ["calendar_month"],
        proration_rules: ["exact_day_count"],
        excess_km_methods: ["period"],
        vehicle_source_types: [],
        custody_event_types: [],
        usage_event_types: [],
        usage_event_rate_components: {},
        usage_event_applicabilities: [],
        expense_types: [],
        expense_allocation_types: [],
        financial_sides: ["revenue", "cost"],
        rate_component_codes: ["base_rental"],
        rate_units: ["month"],
    };
}
