import { ContentHeader } from "@/shared/components/ContentHeader";
import { ErrorAlert } from "@/shared/components/ErrorAlert";
import { LoadingState } from "@/shared/components/LoadingState";
import { Panel } from "@/shared/components/Panel";
import { useApi } from "@/shared/hooks/useApi";
import { getRentalDashboard } from "../vehicleRentalApi";
import { RentalPage } from "../components/RentalPage";

const labels: Record<string, string> = {
    active_agreements: "Active agreements",
    active_allocations: "Active allocations",
    usage_pending_approval: "Usage pending approval",
    calculations_pending_approval: "Calculations pending approval",
};

export default function RentalDashboardPage() {
    const result = useApi((signal) => getRentalDashboard(signal), []);
    return (
        <RentalPage>
            <ContentHeader
                title="Vehicle Rental"
                description="Customer rentals, vehicle-owner costs, custody, running charts, deposits, and vehicle finance."
            />
            <ErrorAlert error={result.error} />
            {result.loading ? (
                <LoadingState />
            ) : (
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {Object.entries(result.data ?? {}).map(([key, value]) => (
                        <Panel key={key}>
                            <p className="text-sm text-slate-500">
                                {labels[key] ?? key}
                            </p>
                            <p className="mt-2 text-3xl font-bold text-slate-950">
                                {value}
                            </p>
                        </Panel>
                    ))}
                </div>
            )}
            <Panel title="Canonical workflow" className="mt-6">
                <div className="grid gap-4 text-sm text-slate-600 lg:grid-cols-2">
                    <div>
                        <strong className="text-slate-900">
                            Customer revenue
                        </strong>
                        <p className="mt-1">
                            Customer agreement to allocation to custody to running
                            chart to revenue calculation to invoice to receipt.
                        </p>
                    </div>
                    <div>
                        <strong className="text-slate-900">
                            Vehicle-owner cost
                        </strong>
                        <p className="mt-1">
                            Owner agreement to source allocation to same running
                            chart to cost calculation to payable to deductions to
                            payment.
                        </p>
                    </div>
                </div>
            </Panel>
        </RentalPage>
    );
}
