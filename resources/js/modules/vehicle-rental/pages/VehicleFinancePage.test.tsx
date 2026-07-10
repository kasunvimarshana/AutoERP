import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import type { ReactNode } from "react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { TestRouter } from "@/test/TestRouter";
import type { SupplierSummary } from "@/modules/supplier/supplierTypes";
import type { VehicleSummary } from "@/modules/vehicle/vehicleTypes";
import type { NamedResource } from "@/shared/types/common";
import VehicleFinancePage from "./VehicleFinancePage";

const apiMocks = vi.hoisted(() => ({
    activateVehicleFinanceAgreement: vi.fn(),
    createVehicleFinanceAgreement: vi.fn(),
    createVehicleFinancePayable: vi.fn(),
    getRentalMetadata: vi.fn(),
    listVehicleFinanceAgreements: vi.fn(),
    supplierDefaultCurrency: { current: null as NamedResource | null },
}));

vi.mock("@/modules/auth/AuthProvider", () => ({
    useAuth: () => ({}),
}));
vi.mock("@/modules/auth/accessControl", () => ({
    hasPermission: () => true,
}));
vi.mock("../vehicleRentalApi", () => apiMocks);
vi.mock("../components/RentalPage", () => ({
    RentalPage: ({ children }: { children: ReactNode }) => <>{children}</>,
}));
vi.mock("@/modules/supplier/components/SupplierLookupSelect", () => ({
    SupplierLookupSelect: ({
        value,
        onChange,
    }: {
        value: SupplierSummary | null;
        onChange: (value: SupplierSummary | null) => void;
    }) => (
        <button type="button" onClick={() => onChange(supplier())}>
            {value?.name ?? "Choose supplier"}
        </button>
    ),
}));
vi.mock("@/modules/vehicle/components/VehicleLookupSelect", () => ({
    VehicleLookupSelect: ({
        value,
        onChange,
    }: {
        value: VehicleSummary | null;
        onChange: (value: VehicleSummary | null) => void;
    }) => (
        <button type="button" onClick={() => onChange(vehicle())}>
            {value?.vehicle_number ?? "Choose vehicle"}
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
        <button type="button" onClick={() => onChange(manualCurrency())}>
            {value?.name ?? "Choose currency"}
        </button>
    ),
}));

describe("VehicleFinancePage currency defaults", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.supplierDefaultCurrency.current = null;
        apiMocks.getRentalMetadata.mockResolvedValue(
            rentalMetadata({ id: 1, code: "LKR", name: "Tenant Currency" }),
        );
        apiMocks.listVehicleFinanceAgreements.mockResolvedValue(collection([]));
        apiMocks.createVehicleFinanceAgreement.mockResolvedValue({ id: 77 });
    });

    it("uses the tenant default currency without manual selection", async () => {
        const user = userEvent.setup();
        renderPage();

        await user.click(screen.getByRole("button", { name: "Choose supplier" }));
        await user.click(screen.getByRole("button", { name: "Choose vehicle" }));
        await screen.findByRole("button", { name: "Tenant Currency" });
        await fillRequiredFields(user);
        await user.click(screen.getByRole("button", { name: "Create finance agreement" }));

        await waitFor(() =>
            expect(apiMocks.createVehicleFinanceAgreement).toHaveBeenCalledWith(
                expect.objectContaining({
                    supplier_id: 33,
                    vehicle_id: 12,
                    currency_id: 1,
                }),
            ),
        );
    });

    it("uses the selected supplier currency default while untouched", async () => {
        const user = userEvent.setup();
        apiMocks.supplierDefaultCurrency.current = {
            id: 2,
            code: "USD",
            name: "Supplier Currency",
        };
        renderPage();

        await user.click(screen.getByRole("button", { name: "Choose supplier" }));
        await user.click(screen.getByRole("button", { name: "Choose vehicle" }));
        await screen.findByRole("button", { name: "Supplier Currency" });
        await fillRequiredFields(user);
        await user.click(screen.getByRole("button", { name: "Create finance agreement" }));

        await waitFor(() =>
            expect(apiMocks.createVehicleFinanceAgreement).toHaveBeenCalledWith(
                expect.objectContaining({
                    currency_id: 2,
                }),
            ),
        );
    });

    it("preserves a manual currency selection after supplier defaults", async () => {
        const user = userEvent.setup();
        apiMocks.supplierDefaultCurrency.current = {
            id: 2,
            code: "USD",
            name: "Supplier Currency",
        };
        renderPage();

        await user.click(screen.getByRole("button", { name: "Choose supplier" }));
        await user.click(screen.getByRole("button", { name: "Choose vehicle" }));
        await user.click(await screen.findByRole("button", { name: "Supplier Currency" }));
        await fillRequiredFields(user);
        await user.click(screen.getByRole("button", { name: "Create finance agreement" }));

        await waitFor(() =>
            expect(apiMocks.createVehicleFinanceAgreement).toHaveBeenCalledWith(
                expect.objectContaining({
                    currency_id: 3,
                }),
            ),
        );
    });
});

function renderPage() {
    return render(
        <TestRouter>
            <VehicleFinancePage />
        </TestRouter>,
    );
}

async function fillRequiredFields(user: ReturnType<typeof userEvent.setup>) {
    await user.type(screen.getByLabelText("Starts at"), "2026-07-06");
    await user.type(screen.getByLabelText("Matures at"), "2027-07-06");
    await user.type(screen.getByLabelText("Principal"), "100000");
}

function supplier(): SupplierSummary {
    return {
        id: 33,
        row_version: 1,
        supplier_number: "SUP-33",
        code: "SUP-33",
        name: "Finance Supplier",
        display_name: null,
        supplier_type: "company",
        status: "active",
        default_currency: apiMocks.supplierDefaultCurrency.current,
        is_credit_allowed: true,
        is_advance_allowed: false,
    };
}

function vehicle(): VehicleSummary {
    return {
        id: 12,
        row_version: 1,
        vehicle_number: "CAR-1000",
        registration_number: "CAR-1000",
        status: "active",
        odometer_reading: "0.000000",
    };
}

function manualCurrency(): NamedResource {
    return { id: 3, code: "GBP", name: "Manual Currency" };
}

function rentalMetadata(defaultCurrency: NamedResource | null) {
    return {
        default_currency: defaultCurrency,
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
            per_page: 50,
            to: data.length,
            total: data.length,
        },
    };
}
