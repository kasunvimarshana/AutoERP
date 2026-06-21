import type { ReactNode } from "react";
import { RentalModuleNav } from "./RentalModuleNav";

export function RentalPage({ children }: { children: ReactNode }) {
    return (
        <>
            <RentalModuleNav />
            {children}
        </>
    );
}
