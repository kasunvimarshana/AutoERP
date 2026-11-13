import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { Select } from "./Select";

describe("Select", () => {
    it("renders label, placeholder, options, and associates label with select", () => {
        render(
            <Select
                label="Country"
                options={[
                    { value: "us", label: "United States" },
                    { value: "ca", label: "Canada" },
                ]}
                placeholder="Choose country"
            />,
        );

        const select = screen.getByRole("combobox", { name: "Country" });
        expect(select).toBeInTheDocument();
        expect(screen.getByText("Choose country")).toBeInTheDocument();
        expect(screen.getByText("United States")).toBeInTheDocument();
        expect(screen.getByText("Canada")).toBeInTheDocument();
    });

    it("exposes validation error as accessible description", () => {
        render(
            <Select
                label="Status"
                options={[{ value: "active", label: "Active" }]}
                error="Status is required."
            />,
        );

        const select = screen.getByRole("combobox", { name: "Status" });
        expect(select).toHaveAttribute("aria-invalid", "true");
        expect(select).toHaveAccessibleDescription("Status is required.");
    });

    it("supports multiple mode without placeholder option", () => {
        render(
            <Select
                multiple
                label="Tags"
                options={[
                    { value: "t1", label: "Tag 1" },
                    { value: "t2", label: "Tag 2" },
                ]}
                placeholder="Choose tags"
            />,
        );

        const select = screen.getByRole("listbox", { name: "Tags" });
        expect(select).toHaveAttribute("multiple");
        expect(screen.queryByText("Choose tags")).not.toBeInTheDocument();
    });
});
