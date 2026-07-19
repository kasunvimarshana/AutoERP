import { readFileSync } from "node:fs";
import { resolve } from "node:path";
import { describe, expect, it } from "vitest";

function pageSource(): string {
    return readFileSync(
        resolve(
            process.cwd(),
            "resources/js/modules/vehicle-rental/pages/RentalReservationDetailPage.tsx",
        ),
        "utf8",
    );
}

describe("Rental reservation workflow", () => {
    it("blocks confirmation until a specific vehicle is selected", () => {
        const source = pageSource();

        expect(source).toContain(
            'const canConfirm = row.status === "pending" && Boolean(row.requested_vehicle)',
        );
        expect(source).toContain("disabled={!canConfirm}");
        expect(source).toContain(
            "Select a specific available vehicle before confirming this",
        );
    });

    it("offers agreement conversion only after confirmation", () => {
        const source = pageSource();

        expect(source).toContain('row.status === "confirmed"');
        expect(source).toContain("Create lessee agreement");
    });
});
