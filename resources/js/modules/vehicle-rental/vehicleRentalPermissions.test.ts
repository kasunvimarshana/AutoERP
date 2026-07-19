import { describe, expect, it } from "vitest";
import {
    vehicleRentalNavigationPermissions,
    vehicleRentalPermissions,
} from "./vehicleRentalPermissions";

describe("vehicle rental permissions", () => {
    it("keeps every permission unique and namespaced", () => {
        const values = Object.values(vehicleRentalPermissions);

        expect(new Set(values).size).toBe(values.length);
        expect(
            values.every((permission) =>
                permission.startsWith("vehicle-rental."),
            ),
        ).toBe(true);
        expect(vehicleRentalNavigationPermissions).toEqual(values);
    });

    it("separates operational, financial, approval, and profitability access", () => {
        expect(vehicleRentalPermissions.view).toBe("vehicle-rental.view");
        expect(vehicleRentalPermissions.financialView).toBe(
            "vehicle-rental.financial.view",
        );
        expect(vehicleRentalPermissions.profitabilityView).toBe(
            "vehicle-rental.profitability.view",
        );
        expect(vehicleRentalPermissions.usageApprove).not.toBe(
            vehicleRentalPermissions.usageRecord,
        );
        expect(vehicleRentalPermissions.calculationsApprove).not.toBe(
            vehicleRentalPermissions.calculationsManage,
        );
    });
});
