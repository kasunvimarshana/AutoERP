import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { describe, expect, it, vi } from "vitest";
import { RENTAL_AGREEMENT_KIND } from "../rentalAgreementPresentation";
import { RENTAL_MODE } from "../rentalAgreementUi";
import {
    RentalAgreementRateBuilder,
    type RentalRateComponentDefinition,
} from "./RentalAgreementRateBuilder";

const DEFINITIONS: RentalRateComponentDefinition[] = [
    {
        code: "base_rental",
        unit: "month",
        supported_units: ["month"],
        group: "core",
        required: true,
    },
    {
        code: "excess_km",
        unit: "kilometre",
        supported_units: ["kilometre"],
        group: "core",
        required: false,
    },
    {
        code: "driver_salary",
        unit: "month",
        supported_units: ["month", "day"],
        group: "core",
        required: false,
    },
    {
        code: "parking",
        unit: "count",
        supported_units: ["count"],
        group: "event",
        required: false,
    },
];

function renderBuilder({
    rentalMode = RENTAL_MODE.selfDrive,
    enabledOptionalCodes = new Set<string>(),
}: {
    rentalMode?: string;
    enabledOptionalCodes?: Set<string>;
} = {}) {
    const onEnableOptional = vi.fn();

    render(
        <RentalAgreementRateBuilder
            agreementKind={RENTAL_AGREEMENT_KIND.customerRental}
            rentalMode={rentalMode}
            definitions={DEFINITIONS}
            rates={{ base_rental: "1000", excess_km: "0" }}
            units={{}}
            taxTreatments={{ base_rental: "taxable" }}
            enabledOptionalCodes={enabledOptionalCodes}
            currencyLabel="LKR"
            editable
            onEnableOptional={onEnableOptional}
            onDisableOptional={vi.fn()}
            onRateChange={vi.fn()}
            onUnitChange={vi.fn()}
            onTaxTreatmentChange={vi.fn()}
        />,
    );

    return { onEnableOptional };
}

describe("RentalAgreementRateBuilder", () => {
    it("shows required pricing and hides irrelevant driver and event rates", () => {
        renderBuilder();

        expect(
            screen.getByRole("group", { name: "Customer base charge" }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole("group", {
                name: "Customer excess KM charge",
            }),
        ).toBeInTheDocument();
        expect(
            screen.queryByRole("group", { name: "Customer driver charge" }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole("group", { name: "Parking" }),
        ).not.toBeInTheDocument();
    });

    it("shows driver pricing only for with-driver contracts", () => {
        renderBuilder({ rentalMode: RENTAL_MODE.withDriver });

        expect(
            screen.getByRole("group", { name: "Customer driver charge" }),
        ).toBeInTheDocument();
    });

    it("adds an optional charge through the controlled selector", async () => {
        const user = userEvent.setup();
        const { onEnableOptional } = renderBuilder();

        await user.selectOptions(
            screen.getByRole("combobox", { name: "Add optional charge" }),
            "parking",
        );

        expect(onEnableOptional).toHaveBeenCalledWith("parking");
    });

    it("renders an enabled optional charge and its remove action", () => {
        renderBuilder({ enabledOptionalCodes: new Set(["parking"]) });

        expect(
            screen.getByRole("group", { name: "Parking" }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole("button", { name: "Remove Parking" }),
        ).toBeInTheDocument();
    });
});
