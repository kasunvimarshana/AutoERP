import { Link } from "react-router-dom";
import { LinkButton } from "@/shared/components/Button";
import { ContentHeader } from "@/shared/components/ContentHeader";
import { ErrorAlert } from "@/shared/components/ErrorAlert";
import { LoadingState } from "@/shared/components/LoadingState";
import { Panel } from "@/shared/components/Panel";
import { useApi } from "@/shared/hooks/useApi";
import { listReports } from "@/modules/reporting/reportingApi";
import type { ReportDefinition } from "@/modules/reporting/reportingTypes";
import { RentalPage } from "../components/RentalPage";

const groups = [
    [
        "Operations",
        [
            "availability",
            "agreement",
            "allocation",
            "custody",
            "replacement",
            "running",
            "overtime",
            "utilization",
        ],
    ],
    [
        "Customer billing",
        ["revenue", "customer", "invoice", "outstanding", "deposit"],
    ],
    ["Owner payables", ["owner", "cost", "payable", "deduction"]],
    [
        "Compliance and management",
        ["finance", "licence", "document", "profitability", "tax"],
    ],
] as const;

export default function RentalReportsPage() {
    const result = useApi((signal) => listReports(signal), []);
    const reports = (result.data ?? []).filter((report) =>
        report.key.startsWith("vehicle-rental."),
    );

    return (
        <RentalPage>
            <ContentHeader
                title="Vehicle Rental reports"
                description="Operational and financial reports backed by approved rental sources and central Finance documents."
            />
            <div className="mb-6 rounded-xl border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-5 shadow-sm">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div className="inline-flex items-center gap-1.5 rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-800 mb-2">
                            Official Lessee Document
                        </div>
                        <h2 className="text-lg font-bold text-slate-900">
                            Running Chart Report (Landscape A4 Sheet)
                        </h2>
                        <p className="mt-1 text-sm text-slate-600 max-w-2xl">
                            Generate the official running chart matching standard monthly basis physical operations with custom date range, accurate comparative & cumulative mileage, overtime hours, night-outs, expressway pass expenses, and vehicle replacement audit remarks. Supports preview, instant printing, and PDF export.
                        </p>
                    </div>
                    <div className="shrink-0">
                        <LinkButton
                            to="/vehicle-rental/running-chart/report"
                            variant="primary"
                        >
                            Open Running Chart Report
                        </LinkButton>
                    </div>
                </div>
            </div>

            <ErrorAlert error={result.error} />
            {result.loading ? (
                <LoadingState label="Loading rental reports..." />
            ) : (
                <div className="grid gap-5 xl:grid-cols-2">
                    {groups.map(([title, keywords]) => {
                        const rows = reports.filter((report) =>
                            keywords.some((keyword) =>
                                `${report.key} ${report.title}`
                                    .toLowerCase()
                                    .includes(keyword),
                            ),
                        );
                        return rows.length > 0 ? (
                            <ReportGroup
                                key={title}
                                title={title}
                                reports={rows}
                            />
                        ) : null;
                    })}
                    {reports.length === 0 && (
                        <Panel>
                            <p className="text-sm text-slate-500">
                                No Vehicle Rental reports are registered.
                            </p>
                        </Panel>
                    )}
                </div>
            )}
        </RentalPage>
    );
}

function ReportGroup({
    title,
    reports,
}: {
    title: string;
    reports: ReportDefinition[];
}) {
    return (
        <Panel title={title}>
            <div className="divide-y divide-slate-100">
                {reports.map((report) => (
                    <Link
                        key={report.key}
                        to={`/reports/${report.key}`}
                        className="block rounded-lg px-2 py-3 hover:bg-slate-50"
                    >
                        <div className="font-semibold text-slate-900">
                            {report.title}
                        </div>
                        <div className="mt-1 text-sm text-slate-500">
                            {report.description ?? report.key}
                        </div>
                    </Link>
                ))}
            </div>
        </Panel>
    );
}
