import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import type { ReactNode } from "react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { TestRouter } from "@/test/TestRouter";
import type { CustomerSummary } from "@/modules/customer/customerTypes";
import type { VehicleSummary } from "@/modules/vehicle/vehicleTypes";
import type { NamedResource } from "@/shared/types/common";
import RentalReservationCreatePage from "./RentalReservationCreatePage";

const apiMocks = vi.hoisted(() => ({
    createRentalReservation: vi.fn(),
    getRentalMetadata: vi.fn(),
    customerDefaultCurrency: { current: null as NamedResource | null },
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
        value: CustomerSummary | null;
        onChange: (value: CustomerSummary | null) => void;
    }) => (
        <button type="button" onClick={() => onChange(customer())}>
            {value?.name ?? "Choose customer"}
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

describe("RentalReservationCreatePage currency defaults", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        apiMocks.customerDefaultCurrency.current = null;
        apiMocks.getRentalMetadata.mockResolvedValue(
            rentalMetadata({ id: 1, code: "LKR", name: "Tenant Currency" }),
        );
        apiMocks.createRentalReservation.mockResolvedValue({ id: 44 });
    });

    it("uses the tenant default currency without manual selection", async () => {
        const user = userEvent.setup();
        renderPage();

        await user.click(screen.getByRole("button", { name: "Choose customer" }));
        await screen.findByRole("button", { name: "Tenant Currency" });
        await waitFor(() =>
            expect(screen.getByLabelText("Reservation source")).toHaveValue(
                "walk_in",
            ),
        );
        await fillRequiredDates(user);
        await user.click(screen.getByRole("button", { name: "Create reservation" }));

        await waitFor(() =>
            expect(apiMocks.createRentalReservation).toHaveBeenCalledWith(
                expect.objectContaining({
                    customer_id: 22,
                    currency_id: 1,
                    rental_mode: "with_driver",
                    billing_cycle: "monthly",
                    source: "walk_in",
                }),
            ),
        );
    });

    it("preserves editable metadata defaults when the user changes them", async () => {
        const user = userEvent.setup();
        renderPage();

        await user.click(screen.getByRole("button", { name: "Choose customer" }));
        await screen.findByRole("button", { name: "Tenant Currency" });
        await waitFor(() =>
            expect(screen.getByLabelText("Reservation source")).toHaveValue(
                "walk_in",
            ),
        );
        await user.selectOptions(screen.getByLabelText("Rental mode"), "self_drive");
        await user.selectOptions(
            screen.getByLabelText("Reservation source"),
            "referral",
        );
        await fillRequiredDates(user);
        await user.click(screen.getByRole("button", { name: "Create reservation" }));

        await waitFor(() =>
            expect(apiMocks.createRentalReservation).toHaveBeenCalledWith(
                expect.objectContaining({
                    rental_mode: "self_drive",
                    billing_cycle: "monthly",
                    source: "referral",
                }),
            ),
        );
    });

    it("uses the selected customer currency default while untouched", async () => {
        const user = userEvent.setup();
        apiMocks.customerDefaultCurrency.current = {
            id: 2,
            code: "USD",
            name: "Customer Currency",
        };
        renderPage();

        await user.click(screen.getByRole("button", { name: "Choose customer" }));
        await screen.findByRole("button", { name: "Customer Currency" });
        await fillRequiredDates(user);
        await user.click(screen.getByRole("button", { name: "Create reservation" }));

        await waitFor(() =>
            expect(apiMocks.createRentalReservation).toHaveBeenCalledWith(
                expect.objectContaining({
                    currency_id: 2,
                }),
            ),
        );
    });

    it("preserves a manual currency selection after customer defaults", async () => {
        const user = userEvent.setup();
        apiMocks.customerDefaultCurrency.current = {
            id: 2,
            code: "USD",
            name: "Customer Currency",
        };
        renderPage();

        await user.click(screen.getByRole("button", { name: "Choose customer" }));
        await user.click(await screen.findByRole("button", { name: "Customer Currency" }));
        await fillRequiredDates(user);
        await user.click(screen.getByRole("button", { name: "Create reservation" }));

        await waitFor(() =>
            expect(apiMocks.createRentalReservation).toHaveBeenCalledWith(
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
            <RentalReservationCreatePage />
        </TestRouter>,
    );
}

async function fillRequiredDates(user: ReturnType<typeof userEvent.setup>) {
    await user.type(screen.getByLabelText("Start"), "2026-07-06T08:00");
    await user.type(screen.getByLabelText("Expected end"), "2026-08-06T08:00");
}

function customer(): CustomerSummary {
    return {
        id: 22,
        row_version: 1,
        customer_number: "CUS-22",
        code: "CUS-22",
        name: "Lessee Customer",
        display_name: null,
        customer_type: "company",
        status: "active",
        default_currency: apiMocks.customerDefaultCurrency.current,
        credit_allowed: true,
        advance_allowed: false,
        is_tax_exempt: false,
        marketing_consent: false,
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
        defaults: {
            rental_mode: "with_driver",
            billing_cycle: "monthly",
            reservation_source: "walk_in",
        },
        rental_modes: ["with_driver", "self_drive", "vehicle_only"],
        billing_cycles: ["hourly", "daily", "weekly", "monthly", "per_hire"],
        reservation_sources: ["walk_in", "referral"],
    };
}
