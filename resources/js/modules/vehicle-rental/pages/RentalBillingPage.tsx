import { useMemo, useState, type FormEvent } from "react";
import { Link } from "react-router-dom";
import { useAuth } from "@/modules/auth/AuthProvider";
import { Button } from "@/shared/components/Button";
import { ContentHeader } from "@/shared/components/ContentHeader";
import { DataTable, type DataColumn } from "@/shared/components/DataTable";
import { ErrorAlert } from "@/shared/components/ErrorAlert";
import { Input } from "@/shared/components/Input";
import { LoadingState } from "@/shared/components/LoadingState";
import { Panel } from "@/shared/components/Panel";
import { Select } from "@/shared/components/Select";
import { StatusBadge } from "@/shared/components/StatusBadge";
import { toApiError, type ApiError } from "@/shared/api/apiError";
import { useApi } from "@/shared/hooks/useApi";
import { formatDate } from "@/shared/utils/formatDate";
import { readableRelation } from "@/shared/utils/object";
import { RentalPage } from "../components/RentalPage";
import {
    calculateRentalAgreement,
    createRentalInvoice,
    listRentalAgreements,
    listRentalCalculationRuns,
    transitionRentalCalculationRun,
} from "../vehicleRentalApi";
import { vehicleRentalPermissions } from "../vehicleRentalPermissions";
import type { RentalCalculationRun } from "../vehicleRentalTypes";

const today = new Date().toISOString().slice(0, 10);
const monthStart = `${today.slice(0, 7)}-01`;

export default function RentalBillingPage() {
    const auth = useAuth();
    const [agreementId, setAgreementId] = useState("");
    const [financialSide, setFinancialSide] = useState("revenue");
    const [periodStart, setPeriodStart] = useState(monthStart);
    const [periodEnd, setPeriodEnd] = useState(today);
    const [saving, setSaving] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);

    const agreements = useApi(
        (signal) =>
            listRentalAgreements({ status: "active", per_page: 100 }, signal),
        [],
    );
    const runs = useApi(
        (signal) =>
            listRentalCalculationRuns(
                {
                    agreement_id: agreementId || undefined,
                    financial_side: financialSide,
                    per_page: 50,
                },
                signal,
            ),
        [agreementId, financialSide],
    );

    const agreementOptions = useMemo(
        () =>
            (agreements.data?.data ?? []).map((row) => ({
                value: String(row.id),
                label: `${row.agreement_number} · ${readableRelation(row.customer ?? row.supplier)}`,
            })),
        [agreements.data],
    );
    const canCalculate = auth.permissions.includes(
        vehicleRentalPermissions.calculationsManage,
    );
    const canApprove = auth.permissions.includes(
        vehicleRentalPermissions.calculationsApprove,
    );
    const canCreateDocument = auth.permissions.includes(
        vehicleRentalPermissions.financialCreate,
    );

    const calculate = async (event: FormEvent) => {
        event.preventDefault();
        if (!agreementId) return;
        setSaving(true);
        setActionError(null);
        try {
            await calculateRentalAgreement(Number(agreementId), {
                financial_side: financialSide,
                period_start: periodStart,
                period_end: periodEnd,
            });
            runs.reload();
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setSaving(false);
        }
    };

    const transition = async (run: RentalCalculationRun, status: string) => {
        setActionError(null);
        try {
            await transitionRentalCalculationRun(run.id, status);
            runs.reload();
        } catch (error) {
            setActionError(toApiError(error));
        }
    };

    const createDocument = async (run: RentalCalculationRun) => {
        setActionError(null);
        try {
            await createRentalInvoice(run.id, {
                invoice_date: today,
                due_date: today,
                status: "draft",
                notes: `${run.billing_period?.financial_side === "cost" ? "Owner rental payable" : "Customer rental invoice"} from approved rental calculation`,
            });
            runs.reload();
        } catch (error) {
            setActionError(toApiError(error));
        }
    };

    const columns: DataColumn<RentalCalculationRun>[] = [
        {
            key: "run",
            header: "Run",
            render: (row) => `#${row.id} v${row.run_version}`,
        },
        {
            key: "agreement",
            header: "Agreement",
            render: (row) =>
                row.billing_period?.agreement ? (
                    <Link
                        className="font-semibold text-blue-700"
                        to={`/vehicle-rental/agreements/${row.billing_period.agreement.id}`}
                    >
                        {readableRelation(row.billing_period.agreement)}
                    </Link>
                ) : (
                    "-"
                ),
        },
        {
            key: "side",
            header: "Side",
            render: (row) => row.billing_period?.financial_side ?? "-",
        },
        {
            key: "period",
            header: "Period",
            render: (row) =>
                `${formatDate(row.billing_period?.period_start)} – ${formatDate(row.billing_period?.period_end)}`,
        },
        {
            key: "total",
            header: "Grand total",
            render: (row) => row.grand_total,
        },
        {
            key: "status",
            header: "Status",
            render: (row) => (
                <div className="space-y-1">
                    <StatusBadge status={row.calculation_status} />
                    <div className="text-xs text-slate-500">
                        {row.document_status.replaceAll("_", " ")}
                    </div>
                </div>
            ),
        },
        {
            key: "actions",
            header: "",
            className: "text-right",
            render: (row) => (
                <div className="flex justify-end gap-2">
                    {canCalculate &&
                        row.calculation_status === "calculated" && (
                            <Button
                                variant="ghost"
                                onClick={() =>
                                    void transition(row, "submitted")
                                }
                            >
                                Submit
                            </Button>
                        )}
                    {canApprove && row.calculation_status === "submitted" && (
                        <Button
                            variant="ghost"
                            onClick={() => void transition(row, "approved")}
                        >
                            Approve
                        </Button>
                    )}
                    {canCreateDocument &&
                        row.calculation_status === "approved" &&
                        row.document_status !== "generated" && (
                            <Button
                                variant="secondary"
                                onClick={() => void createDocument(row)}
                            >
                                {row.billing_period?.financial_side === "cost"
                                    ? "Create payable"
                                    : "Create invoice"}
                            </Button>
                        )}
                </div>
            ),
        },
    ];

    return (
        <RentalPage>
            <ContentHeader
                title="Rental billing"
                description="Calculate customer revenue and owner cost independently from the same approved usage stream."
            />
            <ErrorAlert error={actionError ?? agreements.error ?? runs.error} />
            {canCalculate && (
                <Panel title="New calculation">
                    <form
                        onSubmit={calculate}
                        className="grid gap-4 md:grid-cols-2 xl:grid-cols-5"
                    >
                        <Select
                            label="Agreement"
                            required
                            value={agreementId}
                            onChange={(event) =>
                                setAgreementId(event.target.value)
                            }
                            options={agreementOptions}
                        />
                        <Select
                            label="Financial side"
                            value={financialSide}
                            onChange={(event) =>
                                setFinancialSide(event.target.value)
                            }
                            options={[
                                { value: "revenue", label: "Customer revenue" },
                                { value: "cost", label: "Owner cost" },
                            ]}
                        />
                        <Input
                            label="Period start"
                            type="date"
                            required
                            value={periodStart}
                            onChange={(event) =>
                                setPeriodStart(event.target.value)
                            }
                        />
                        <Input
                            label="Period end"
                            type="date"
                            required
                            value={periodEnd}
                            onChange={(event) =>
                                setPeriodEnd(event.target.value)
                            }
                        />
                        <div className="flex items-end">
                            <Button
                                type="submit"
                                loading={saving}
                                disabled={!agreementId}
                            >
                                Calculate
                            </Button>
                        </div>
                    </form>
                </Panel>
            )}
            <div className="mt-5">
                {runs.loading ? (
                    <LoadingState />
                ) : (
                    <DataTable
                        rows={runs.data?.data ?? []}
                        columns={columns}
                        rowKey={(row) => row.id}
                        emptyMessage="No rental calculation runs found."
                    />
                )}
            </div>
        </RentalPage>
    );
}
