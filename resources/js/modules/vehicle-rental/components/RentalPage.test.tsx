import { readFileSync } from "node:fs";
import { resolve } from "node:path";
import { describe, expect, it } from "vitest";

function source(path: string): string {
    return readFileSync(resolve(process.cwd(), path), "utf8");
}

describe("Vehicle Rental page navigation ownership", () => {
    it("uses the application navigation as the single source of truth", () => {
        const rentalPage = source(
            "resources/js/modules/vehicle-rental/components/RentalPage.tsx",
        );

        expect(rentalPage).not.toContain("RentalModuleNav");
        expect(rentalPage).toContain("return children");
    });

    it("does not keep the removed duplicate module navigation component", () => {
        expect(() =>
            source(
                "resources/js/modules/vehicle-rental/components/RentalModuleNav.tsx",
            ),
        ).toThrow();
    });
});
