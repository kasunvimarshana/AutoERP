import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { Input } from "./Input";

describe("Input", () => {
    it("keeps help text out of the accessible label", () => {
        render(
            <Input
                label="Agreement date"
                hint="Date the contract is prepared."
            />,
        );

        const input = screen.getByRole("textbox", {
            name: "Agreement date",
        });

        expect(input).toHaveAccessibleDescription(
            "Date the contract is prepared.",
        );
        expect(
            screen.queryByRole("textbox", {
                name: "Agreement date Date the contract is prepared.",
            }),
        ).not.toBeInTheDocument();
    });

    it("exposes validation errors as the input description", () => {
        render(<Input label="Start" error="Start is required." />);

        const input = screen.getByRole("textbox", { name: "Start" });

        expect(input).toHaveAttribute("aria-invalid", "true");
        expect(input).toHaveAccessibleDescription("Start is required.");
    });
});
