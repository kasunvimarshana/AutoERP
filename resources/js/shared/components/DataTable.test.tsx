import { fireEvent, render, screen } from "@testing-library/react";
import { BrowserRouter } from "react-router-dom";
import { describe, expect, it, vi } from "vitest";
import { DataTable } from "./DataTable";

describe("DataTable", () => {
    const sampleRows = [
        { id: 1, name: "Item One", category: "Hardware" },
        { id: 2, name: "Item Two", category: "Software" },
    ];
    const columns = [
        { key: "name", header: "Name", render: (r: typeof sampleRows[0]) => r.name },
        { key: "category", header: "Category", render: (r: typeof sampleRows[0]) => r.category },
    ];

    it("renders empty message when rows are empty", () => {
        render(
            <BrowserRouter>
                <DataTable
                    rows={[]}
                    columns={columns}
                    rowKey={(r) => r.id}
                    emptyMessage="Custom empty prompt"
                />
            </BrowserRouter>,
        );

        expect(screen.getByText("Custom empty prompt")).toBeInTheDocument();
    });

    it("enables keyboard accessibility and triggers onRowClick with Space or Enter", () => {
        const handleRowClick = vi.fn();
        render(
            <BrowserRouter>
                <DataTable
                    rows={sampleRows}
                    columns={columns}
                    rowKey={(r) => r.id}
                    onRowClick={handleRowClick}
                />
            </BrowserRouter>,
        );

        const rows = screen.getAllByRole("button");
        // Each sample row renders a mobile article and a desktop table row button
        expect(rows.length).toBeGreaterThanOrEqual(2);

        const firstRow = rows[0];
        expect(firstRow).toHaveAttribute("tabindex", "0");

        fireEvent.keyDown(firstRow, { key: "Enter" });
        expect(handleRowClick).toHaveBeenCalledWith(sampleRows[0]);

        fireEvent.keyDown(firstRow, { key: " " });
        expect(handleRowClick).toHaveBeenCalledTimes(2);
    });
});
