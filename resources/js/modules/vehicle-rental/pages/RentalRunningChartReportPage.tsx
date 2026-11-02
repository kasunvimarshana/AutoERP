import { useCallback, useEffect, useState, type FormEvent } from "react";
import { useSearchParams } from "react-router-dom";
import { toApiError, type ApiError } from "@/shared/api/apiError";
import { Button, LinkButton } from "@/shared/components/Button";
import { ContentHeader } from "@/shared/components/ContentHeader";
import { ErrorAlert } from "@/shared/components/ErrorAlert";
import { Input } from "@/shared/components/Input";
import { LoadingState } from "@/shared/components/LoadingState";
import { Panel } from "@/shared/components/Panel";
import type { NamedResource } from "@/shared/types/common";
import { businessDateInputValue } from "@/shared/utils/businessDate";
import { parsePositiveInteger } from "@/shared/utils/routeParams";
import {
    RentalAgreementLookupSelect,
    RentalAllocationLookupSelect,
} from "../components/RentalLookups";
import { RentalPage } from "../components/RentalPage";
import {
    getRentalAgreement,
    getRentalRunningChartExportUrl,
    getRentalRunningChartReport,
} from "../vehicleRentalApi";
import type {
    RentalRunningChartReportData,
    RentalRunningChartReportParams,
} from "../vehicleRentalTypes";

export default function RentalRunningChartReportPage() {
    const [searchParams, setSearchParams] = useSearchParams();

    // Default dates to the 1st of current month until today
    const now = new Date();
    const defaultStart = new Date(now.getFullYear(), now.getMonth(), 1);
    const defaultEnd = now;

    const initialAgreementId = parsePositiveInteger(searchParams.get("agreement_id"));
    const initialAllocationId = parsePositiveInteger(searchParams.get("allocation_id"));
    const initialStartDate = searchParams.get("start_date") || businessDateInputValue(defaultStart);
    const initialEndDate = searchParams.get("end_date") || businessDateInputValue(defaultEnd);

    const [agreement, setAgreement] = useState<NamedResource | null>(
        initialAgreementId ? { id: initialAgreementId, name: `Agreement #${initialAgreementId}` } : null,
    );
    const [allocation, setAllocation] = useState<NamedResource | null>(
        initialAllocationId ? { id: initialAllocationId, name: `Allocation #${initialAllocationId}` } : null,
    );
    const [startDate, setStartDate] = useState<string>(initialStartDate);
    const [endDate, setEndDate] = useState<string>(initialEndDate);

    const [loading, setLoading] = useState<boolean>(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [report, setReport] = useState<RentalRunningChartReportData | null>(null);

    // If agreement ID is in URL on load, fetch agreement details to set proper display name
    useEffect(() => {
        if (initialAgreementId && (!agreement || agreement.name === `Agreement #${initialAgreementId}`)) {
            const controller = new AbortController();
            getRentalAgreement(initialAgreementId, controller.signal)
                .then((ag) => {
                    setAgreement({
                        id: ag.id,
                        name: `${ag.agreement_number} - ${ag.customer?.name ?? "Customer"}`,
                    });
                })
                .catch(() => {});
            return () => controller.abort();
        }
    }, [initialAgreementId]);

    const fetchReport = useCallback(
        async (params: RentalRunningChartReportParams) => {
            if (!params.agreement_id && !params.allocation_id) {
                return;
            }
            setLoading(true);
            setError(null);
            try {
                const data = await getRentalRunningChartReport(params);
                setReport(data);
                // Update URL search parameters
                const newParams: Record<string, string> = {
                    start_date: params.start_date,
                    end_date: params.end_date,
                };
                if (params.agreement_id) {
                    newParams.agreement_id = String(params.agreement_id);
                }
                if (params.allocation_id) {
                    newParams.allocation_id = String(params.allocation_id);
                }
                setSearchParams(newParams);
            } catch (err) {
                setError(toApiError(err));
                setReport(null);
            } finally {
                setLoading(false);
            }
        },
        [setSearchParams],
    );

    // Auto-fetch if initial query params exist
    useEffect(() => {
        if (initialAgreementId || initialAllocationId) {
            void fetchReport({
                agreement_id: initialAgreementId ?? undefined,
                allocation_id: initialAllocationId ?? undefined,
                start_date: initialStartDate,
                end_date: initialEndDate,
            });
        }
    }, []);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        if (!agreement?.id && !allocation?.id) {
            return;
        }
        void fetchReport({
            agreement_id: agreement?.id,
            allocation_id: allocation?.id,
            start_date: startDate,
            end_date: endDate,
        });
    };

    const handlePresetMonth = (offsetMonths: number) => {
        const d = new Date();
        d.setMonth(d.getMonth() + offsetMonths);
        const y = d.getFullYear();
        const m = d.getMonth();
        const first = new Date(y, m, 1);
        const last = new Date(y, m + 1, 0);
        setStartDate(businessDateInputValue(first));
        setEndDate(businessDateInputValue(last));
    };

    const currentParams: RentalRunningChartReportParams = {
        agreement_id: agreement?.id,
        allocation_id: allocation?.id,
        start_date: startDate,
        end_date: endDate,
    };

    const printUrl = getRentalRunningChartExportUrl("print", currentParams);
    const pdfUrl = getRentalRunningChartExportUrl("pdf", currentParams);
    const htmlUrl = getRentalRunningChartExportUrl("html", currentParams);

    const handlePrint = () => {
        window.open(printUrl, "_blank");
    };

    const handleDownloadPdf = () => {
        window.open(pdfUrl, "_blank");
    };

    const handleOpenFullscreen = () => {
        window.open(htmlUrl, "_blank");
    };

    return (
        <RentalPage>
            <ContentHeader
                title="Running Chart Report (Lessee Sheet)"
                description="Official running chart report matching standard physical operations with custom date range, accurate comparative & cumulative mileage, overtime hours, night-outs, pass expenses, and replacement vehicle history."
                actions={
                    <div className="flex flex-wrap gap-2">
                        <LinkButton
                            to="/vehicle-rental/running-chart"
                            variant="secondary"
                        >
                            Daily Running Chart
                        </LinkButton>
                        <LinkButton
                            to="/vehicle-rental/reports"
                            variant="secondary"
                        >
                            All Rental Reports
                        </LinkButton>
                    </div>
                }
            />

            <ErrorAlert error={error} />

            {/* Filter and Configuration Controls */}
            <Panel title="Report Parameters & Date Range">
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <div className="md:col-span-2">
                            <RentalAgreementLookupSelect
                                direction="inbound"
                                required
                                value={agreement}
                                onChange={(val) => {
                                    setAgreement(val);
                                    setAllocation(null);
                                }}
                            />
                        </div>
                        <div className="md:col-span-2">
                            <RentalAllocationLookupSelect
                                agreementId={agreement?.id}
                                value={allocation}
                                onChange={setAllocation}
                            />
                        </div>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4 items-end">
                        <Input
                            type="date"
                            label="Start Date"
                            required
                            value={startDate}
                            onChange={(e) => setStartDate(e.target.value)}
                        />
                        <Input
                            type="date"
                            label="End Date"
                            required
                            value={endDate}
                            onChange={(e) => setEndDate(e.target.value)}
                        />
                        <div className="flex gap-2">
                            <Button
                                type="button"
                                variant="secondary"
                                className="w-1/2 text-xs"
                                onClick={() => handlePresetMonth(0)}
                            >
                                This Month
                            </Button>
                            <Button
                                type="button"
                                variant="secondary"
                                className="w-1/2 text-xs"
                                onClick={() => handlePresetMonth(-1)}
                            >
                                Last Month
                            </Button>
                        </div>
                        <div>
                            <Button
                                type="submit"
                                variant="primary"
                                className="w-full"
                                loading={loading}
                                disabled={loading || (!agreement?.id && !allocation?.id)}
                            >
                                Generate Report
                            </Button>
                        </div>
                    </div>
                </form>

                {report && (
                    <div className="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-4">
                        <div className="text-xs text-slate-500">
                            Showing report for{" "}
                            <span className="font-semibold text-slate-800">
                                {report.header.client_name}
                            </span>{" "}
                            ({report.header.duration}) • Vehicle:{" "}
                            <span className="font-semibold text-slate-800">
                                {report.header.agreement_vehicle_number}
                            </span>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={handlePrint}
                            >
                                🖨️ Print Sheet
                            </Button>
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={handleDownloadPdf}
                            >
                                📥 Export PDF
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                onClick={handleOpenFullscreen}
                            >
                                🔗 Open Printable View
                            </Button>
                        </div>
                    </div>
                )}
            </Panel>

            {loading && <LoadingState label="Generating Running Chart report..." />}

            {/* Quick KPI Metrics Summary */}
            {report && !loading && (
                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
                    <div className="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                        <div className="text-xs font-medium text-slate-500">Total Mileage</div>
                        <div className="mt-1 text-lg font-bold text-slate-900">
                            {report.summary.total_comparative_mileage.toLocaleString()} <span className="text-xs font-normal text-slate-500">KM</span>
                        </div>
                    </div>
                    <div className="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                        <div className="text-xs font-medium text-slate-500">Final Cumulative</div>
                        <div className="mt-1 text-lg font-bold text-indigo-700">
                            {report.summary.final_cumulative_mileage.toLocaleString()} <span className="text-xs font-normal text-slate-500">KM</span>
                        </div>
                    </div>
                    <div className="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                        <div className="text-xs font-medium text-slate-500">Excess Mileage</div>
                        <div className={`mt-1 text-lg font-bold ${report.summary.excess_km > 0 ? "text-rose-600" : "text-emerald-700"}`}>
                            {report.summary.excess_km.toLocaleString()} <span className="text-xs font-normal text-slate-500">KM</span>
                        </div>
                    </div>
                    <div className="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                        <div className="text-xs font-medium text-slate-500">Normal OT</div>
                        <div className="mt-1 text-lg font-bold text-slate-900">
                            {report.summary.total_normal_ot_hours} <span className="text-xs font-normal text-slate-500">Hrs</span>
                        </div>
                    </div>
                    <div className="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                        <div className="text-xs font-medium text-slate-500">Double OT</div>
                        <div className="mt-1 text-lg font-bold text-amber-700">
                            {report.summary.total_double_ot_hours} <span className="text-xs font-normal text-slate-500">Hrs</span>
                        </div>
                    </div>
                    <div className="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                        <div className="text-xs font-medium text-slate-500">Night Outs</div>
                        <div className="mt-1 text-lg font-bold text-slate-900">
                            {report.summary.total_night_out} <span className="text-xs font-normal text-slate-500">Nights</span>
                        </div>
                    </div>
                    <div className="rounded-lg border border-slate-200 bg-white p-3 shadow-sm">
                        <div className="text-xs font-medium text-slate-500">Total Pass / Toll</div>
                        <div className="mt-1 text-lg font-bold text-slate-900">
                            Rs. {report.summary.total_pass}
                        </div>
                    </div>
                </div>
            )}

            {/* Authentic Paper Document Preview */}
            {report && !loading && (
                <div className="overflow-x-auto rounded-xl border border-slate-300 bg-slate-100 p-4 shadow-inner">
                    <div className="mx-auto min-w-[1000px] max-w-[1200px] rounded-lg border border-slate-400 bg-white p-6 shadow-md font-sans text-slate-900">
                        {/* Report Header */}
                        <div className="text-center pb-2 border-b-2 border-slate-800">
                            <h1 className="text-xl font-black uppercase tracking-wider text-slate-900">
                                {report.header.company_name}
                            </h1>
                            <div className="text-sm font-bold uppercase tracking-wide text-slate-800 mt-0.5">
                                {report.header.report_title}
                            </div>
                            <div className="text-xs font-semibold uppercase text-slate-600 mt-0.5">
                                {report.header.report_subtitle}
                            </div>
                        </div>

                        {/* Metadata Two-Column Grid */}
                        <div className="mt-3 grid grid-cols-2 gap-4 text-xs">
                            <div className="space-y-1">
                                <div className="flex">
                                    <span className="w-36 font-bold text-slate-700 uppercase">Name of Client :</span>
                                    <span className="font-extrabold uppercase text-slate-900">{report.header.client_name}</span>
                                </div>
                                <div className="flex">
                                    <span className="w-36 font-bold text-slate-700 uppercase">Duration :</span>
                                    <span className="font-semibold text-slate-900">{report.header.duration}</span>
                                </div>
                                <div className="flex">
                                    <span className="w-36 font-bold text-slate-700 uppercase">Page :</span>
                                    <span className="text-slate-800">{report.header.page_number}</span>
                                </div>
                            </div>
                            <div className="space-y-1 text-right">
                                <div className="flex justify-end">
                                    <span className="font-bold text-slate-700 uppercase mr-2">Report Date :</span>
                                    <span className="text-slate-900">{report.header.report_date}</span>
                                </div>
                                <div className="flex justify-end">
                                    <span className="font-bold text-slate-700 uppercase mr-2">Agreement No :</span>
                                    <span className="font-extrabold uppercase text-slate-900">{report.header.agreement_number}</span>
                                </div>
                                <div className="flex justify-end">
                                    <span className="font-bold text-slate-700 uppercase mr-2">Agreement Vehicle No :</span>
                                    <span className="font-extrabold uppercase tracking-wider text-slate-900">{report.header.agreement_vehicle_number}</span>
                                </div>
                            </div>
                        </div>

                        {/* Running Chart Sheet Table */}
                        <div className="mt-4 overflow-hidden rounded border border-slate-800">
                            <table className="w-full border-collapse text-[11px]">
                                <thead>
                                    <tr className="bg-slate-200 border-b-2 border-slate-800 text-slate-900 font-extrabold uppercase text-center divide-x divide-slate-400">
                                        <th className="py-1 px-1.5 w-16">Date</th>
                                        <th className="py-1 px-1.5 w-20">Start</th>
                                        <th className="py-1 px-1.5 w-20">Finish</th>
                                        <th className="py-1 px-1.5 w-24">Comparative Mileage</th>
                                        <th className="py-1 px-1.5 w-24">Cumulative Mileage</th>
                                        <th className="py-1 px-1 w-16">ON</th>
                                        <th className="py-1 px-1 w-16">OFF</th>
                                        <th className="py-1 px-1 w-14">OT Hrs</th>
                                        <th className="py-1 px-1 w-12">OT Type</th>
                                        <th className="py-1 px-1 w-14">Night Out</th>
                                        <th className="py-1 px-1.5 w-16">Pass</th>
                                        <th className="py-1 px-2 text-left">Remark</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-300">
                                    {report.rows.map((row) => {
                                        const isReplacement = row.remarks && row.remarks.includes("For Repl.");
                                        const hasActivity = row.comparative_mileage > 0 || row.ot_hours !== "" || row.pass !== "";

                                        return (
                                            <tr
                                                key={row.date}
                                                className={`divide-x divide-slate-200 ${
                                                    isReplacement
                                                        ? "bg-amber-50 font-medium"
                                                        : hasActivity
                                                          ? "hover:bg-slate-50"
                                                          : "text-slate-400 bg-slate-50/50"
                                                }`}
                                            >
                                                <td className="py-1 px-1 text-center font-semibold text-slate-800">
                                                    {row.date}
                                                </td>
                                                <td className="py-1 px-1.5 text-right font-mono text-slate-800">
                                                    {row.start_odometer !== "0" ? row.start_odometer : ""}
                                                </td>
                                                <td className="py-1 px-1.5 text-right font-mono text-slate-800">
                                                    {row.finish_odometer !== "0" ? row.finish_odometer : ""}
                                                </td>
                                                <td className="py-1 px-1.5 text-right font-bold text-slate-900">
                                                    {row.comparative_mileage > 0 ? row.comparative_mileage.toLocaleString() : ""}
                                                </td>
                                                <td className="py-1 px-1.5 text-right font-bold text-indigo-900">
                                                    {row.cumulative_mileage > 0 ? row.cumulative_mileage.toLocaleString() : ""}
                                                </td>
                                                <td className="py-1 px-1 text-center text-[10px]">
                                                    {row.on_time}
                                                </td>
                                                <td className="py-1 px-1 text-center text-[10px]">
                                                    {row.off_time}
                                                </td>
                                                <td className="py-1 px-1 text-right font-medium text-slate-900">
                                                    {row.ot_hours}
                                                </td>
                                                <td className="py-1 px-1 text-center font-bold text-slate-900">
                                                    {row.ot_type}
                                                </td>
                                                <td className="py-1 px-1 text-center">
                                                    {row.night_out}
                                                </td>
                                                <td className="py-1 px-1.5 text-right font-mono text-slate-900">
                                                    {row.pass}
                                                </td>
                                                <td className="py-1 px-2 text-left">
                                                    {isReplacement ? (
                                                        <span className="inline-block rounded bg-amber-200/80 px-1.5 py-0.5 text-[10px] font-bold text-amber-950">
                                                            {row.remarks}
                                                        </span>
                                                    ) : (
                                                        <span className="text-slate-700 text-[10px]">{row.remarks}</span>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>

                        {/* Report Footer Summary Section */}
                        <div className="mt-3 grid grid-cols-3 gap-3 text-xs border border-slate-700 bg-slate-50 p-2.5 rounded">
                            <div className="space-y-1">
                                <div className="flex justify-between border-b border-slate-200 pb-0.5">
                                    <span className="font-bold text-slate-700">Excess KM :</span>
                                    <span className={`font-black ${report.summary.excess_km > 0 ? "text-rose-600" : "text-slate-900"}`}>
                                        {report.summary.excess_km.toLocaleString()}
                                    </span>
                                </div>
                                <div className="flex justify-between border-b border-slate-200 pb-0.5">
                                    <span className="font-bold text-slate-700">Total Night Out :</span>
                                    <span className="font-bold text-slate-900">{report.summary.total_night_out}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="font-bold text-slate-700">Total Garage KM :</span>
                                    <span className="font-bold text-slate-900">{report.summary.total_garage_km}</span>
                                </div>
                            </div>
                            <div className="space-y-1">
                                <div className="flex justify-between border-b border-slate-200 pb-0.5">
                                    <span className="font-bold text-slate-700">Normal OT Hrs :</span>
                                    <span className="font-black text-slate-900">{report.summary.total_normal_ot_hours}</span>
                                </div>
                                <div className="flex justify-between border-b border-slate-200 pb-0.5">
                                    <span className="font-bold text-slate-700">Double OT Hrs :</span>
                                    <span className="font-black text-amber-800">{report.summary.total_double_ot_hours}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="font-bold text-slate-700">Included Agreement KM :</span>
                                    <span className="font-bold text-slate-800">{report.summary.included_km.toLocaleString()}</span>
                                </div>
                            </div>
                            <div className="space-y-1">
                                <div className="flex justify-between border-b border-slate-200 pb-0.5">
                                    <span className="font-bold text-slate-700">Total Pass :</span>
                                    <span className="font-black text-slate-900">{report.summary.total_pass}</span>
                                </div>
                                <div className="flex justify-between border-b border-slate-200 pb-0.5">
                                    <span className="font-bold text-slate-700">Total Comparative KM :</span>
                                    <span className="font-black text-slate-900">{report.summary.total_comparative_mileage.toLocaleString()}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="font-bold text-slate-700">Final Cumulative KM :</span>
                                    <span className="font-black text-indigo-900">{report.summary.final_cumulative_mileage.toLocaleString()}</span>
                                </div>
                            </div>
                        </div>

                        {report.summary.total_driver_salary_amount && Number(report.summary.total_driver_salary_amount) > 0 ? (
                            <div className="mt-3 rounded border border-indigo-200 bg-indigo-50/60 p-3 text-xs">
                                <h4 className="font-bold uppercase tracking-wider text-indigo-950">
                                    Driver Duty, Overtime &amp; Salary Reconciliation
                                </h4>
                                <div className="mt-2 grid grid-cols-2 sm:grid-cols-4 gap-3">
                                    <div>
                                        <span className="text-slate-500">Normal OT Hours:</span>
                                        <p className="font-bold text-slate-900 mt-0.5">{report.summary.total_normal_ot_hours} hrs</p>
                                    </div>
                                    <div>
                                        <span className="text-slate-500">Double OT Hours:</span>
                                        <p className="font-bold text-slate-900 mt-0.5">{report.summary.total_double_ot_hours} hrs</p>
                                    </div>
                                    <div>
                                        <span className="text-slate-500">Total Driver OT Pay:</span>
                                        <p className="font-black text-amber-800 mt-0.5">
                                            Rs. {Number(report.summary.total_driver_ot_amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                        </p>
                                    </div>
                                    <div>
                                        <span className="text-slate-500">Gross Driver Salary:</span>
                                        <p className="font-black text-indigo-950 mt-0.5">
                                            Rs. {Number(report.summary.total_driver_salary_amount || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        ) : null}

                        {/* Signatures Area */}
                        <div className="mt-8 grid grid-cols-3 gap-8 pt-6 text-center text-xs text-slate-600">
                            <div>
                                <div className="border-t border-dashed border-slate-500 pt-1 font-bold text-slate-800 uppercase">
                                    Driver's Signature
                                </div>
                                <div className="text-[10px] text-slate-500 mt-0.5">Assigned Chauffeur</div>
                            </div>
                            <div>
                                <div className="border-t border-dashed border-slate-500 pt-1 font-bold text-slate-800 uppercase">
                                    Lessee / Authorized Signature
                                </div>
                                <div className="text-[10px] text-slate-500 mt-0.5">Client Representative</div>
                            </div>
                            <div>
                                <div className="border-t border-dashed border-slate-500 pt-1 font-bold text-slate-800 uppercase">
                                    Management Verification
                                </div>
                                <div className="text-[10px] text-slate-500 mt-0.5">AT Tours & Transport</div>
                            </div>
                        </div>

                        {/* Footer Software Credits */}
                        <div className="mt-6 border-t border-slate-200 pt-2 text-center text-[10px] text-slate-400">
                            Software by AutoERP • AT Tours & Transport (Pvt) Ltd
                        </div>
                    </div>
                </div>
            )}
        </RentalPage>
    );
}
