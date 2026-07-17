import { readFileSync } from "node:fs";
import { resolve } from "node:path";
import { describe, expect, it } from "vitest";

function pageSource(): string {
    return readFileSync(
        resolve(
            process.cwd(),
            "resources/js/modules/vehicle-rental/pages/RentalReplacementPage.tsx",
        ),
        "utf8",
    );
}

describe("Rental replacement workflow", () => {
    it("does not expose or submit an unimplemented billing-continuity choice", () => {
        const source = pageSource();

        expect(source).not.toContain("billing_continuity_rule");
        expect(source).not.toContain('label="Continuity"');
        expect(source).toContain("Complete replacement");
    });
});
