import { useCallback } from "react";
import { GenericLookupSelect } from "@/shared/components/GenericLookupSelect";
import type { LookupLoadParams } from "@/shared/types/lookup";
import { searchEmployees } from "@/modules/hr/hrApi";
import type { EmployeeSummary } from "@/modules/hr/hrTypes";

export function RentalDriverLookupSelect({
    value,
    onChange,
    error,
}: {
    value: EmployeeSummary | null;
    onChange: (value: EmployeeSummary | null) => void;
    error?: string;
}) {
    const search = useCallback(
        (params: LookupLoadParams) => searchEmployees(params),
        [],
    );

    return (
        <GenericLookupSelect
            label="Driver"
            value={value}
            onChange={onChange}
            search={search}
            formatLabel={(employee) =>
                `${employee.employee_number} ${employee.display_name}`.trim()
            }
            error={error}
        />
    );
}
