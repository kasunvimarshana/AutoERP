import { useApi } from "@/shared/hooks/useApi";
import type { SelectOption } from "@/shared/types/common";
import { humanize } from "@/shared/utils/object";
import { getRentalMetadata } from "../vehicleRentalApi";

export function useRentalMetadata() {
    return useApi((signal) => getRentalMetadata(signal), []);
}

export function rentalOptions(values?: readonly string[] | null): SelectOption[] {
    return (values ?? []).map((value) => ({
        value,
        label: humanize(value),
    }));
}
