import { useState, type FormEvent } from "react";
import type { SupplierSummary } from "@/modules/supplier/supplierTypes";
import { SupplierLookupSelect } from "@/modules/supplier/components/SupplierLookupSelect";
import type { VehicleSummary } from "@/modules/vehicle/vehicleTypes";
import { VehicleLookupSelect } from "@/modules/vehicle/components/VehicleLookupSelect";
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
import { Textarea } from "@/shared/components/Textarea";
import { toApiError, type ApiError } from "@/shared/api/apiError";
import { useApi } from "@/shared/hooks/useApi";
import { formatDate } from "@/shared/utils/formatDate";
import { readableRelation } from "@/shared/utils/object";
import { RentalPage } from "../components/RentalPage";
import {
    activateVehicleFinanceAgreement,
    createVehicleFinanceAgreement,
    createVehicleFinancePayable,
    listVehicleFinanceAgreements,
} from "../vehicleRentalApi";
import { vehicleRentalPermissions } from "../vehicleRentalPermissions";
import type {
    VehicleFinanceAgreement,
    VehicleFinanceInstallment,
} from "../vehicleRentalTypes";

interface FinanceForm {
    agreement_number: string;
    agreement_date: string;
    starts_at: string;
    matures_at: string;
    currency_id: string;
    principal_amount: string;
    initial_deposit_amount: string;
    residual_value: string;
    interest_method: string;
    annual_interest_rate: string;
    installment_frequency: string;
    installment_count: string;
    payment_term_days: string;
    remarks: string;
}

const emptyForm = (): FinanceForm => ({
    agreement_number: "",
    agreement_date: new Date().toISOString().slice(0, 10),
    starts_at: "",
    matures_at: "",
    currency_id: "",
    principal_amount: "",
    initial_deposit_amount: "0",
    residual_value: "0",
    interest_method: "flat",
    annual_interest_rate: "0",
    installment_frequency: "monthly",
    installment_count: "12",
    payment_term_days: "0",
    remarks: "",
});

export default function VehicleFinancePage() {
    const auth = useAuth();
    const [form, setForm] = useState<FinanceForm>(emptyForm);
    const [supplier, setSupplier] = useState<SupplierSummary | null>(null);
    const [vehicle, setVehicle] = useState<VehicleSummary | null>(null);
    const [saving, setSaving] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const [selected, setSelected] = useState<VehicleFinanceAgreement | null>(
        null,
    );

    const result = useApi(
        (signal) => listVehicleFinanceAgreements({ per_page: 50 }, signal),
        [],
    );
    const canManage = auth.permissions.includes(
        vehicleRentalPermissions.financeAgreementsManage,
    );

    const save = async (event: FormEvent) => {
        event.preventDefault();
        if (!supplier || !vehicle) return;
        setSaving(true);
        setActionError(null);
        try {
            await createVehicleFinanceAgreement({
                ...form,
                supplier_id: supplier.id,
                vehicle_id: vehicle.id,
                currency_id: Number(form.currency_id),
                installment_count: Number(form.installment_count),
                payment_term_days: Number(form.payment_term_days),
            });
            setForm(emptyForm());
            setSupplier(null);
            setVehicle(null);
            result.reload();
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setSaving(false);
        }
    };

    const activate = async (row: VehicleFinanceAgreement) => {
        setActionError(null);
        try {
            await activateVehicleFinanceAgreement(row.id);
            result.reload();
        } catch (error) {
            setActionError(toApiError(error));
        }
    };

    const createPayable = async (installment: VehicleFinanceInstallment) => {
        setActionError(null);
        try {
            await createVehicleFinancePayable(installment.id, {
                invoice_date: installment.due_date,
                status: "draft",
            });
            result.reload();
        } catch (error) {
            setActionError(toApiError(error));
        }
    };

    const columns: DataColumn<VehicleFinanceAgreement>[] = [
        {
            key: "number",
            header: "Finance agreement",
            render: (row) => row.agreement_number,
        },
        {
            key: "supplier",
            header: "Leasing company",
            render: (row) => readableRelation(row.supplier),
        },
        {
            key: "vehicle",
            header: "Vehicle",
            render: (row) => readableRelation(row.vehicle),
        },
        {
            key: "period",
            header: "Period",
            render: (row) =>
                `${formatDate(row.starts_at)} – ${formatDate(row.matures_at)}`,
        },
        {
            key: "principal",
            header: "Principal",
            render: (row) => row.principal_amount,
        },
        {
            key: "installments",
            header: "Installments",
            render: (row) => String(row.installment_count),
        },
        {
            key: "status",
            header: "Status",
            render: (row) => <StatusBadge status={row.status} />,
        },
        {
            key: "actions",
            header: "",
            className: "text-right",
            render: (row) => (
                <div className="flex justify-end gap-2">
                    <Button variant="ghost" onClick={() => setSelected(row)}>
                        Schedule
                    </Button>
                    {canManage && row.status === "draft" && (
                        <Button
                            variant="secondary"
                            onClick={() => void activate(row)}
                        >
                            Activate
                        </Button>
                    )}
                </div>
            ),
        },
    ];

    const installmentColumns: DataColumn<VehicleFinanceInstallment>[] = [
        { key: "number", header: "#", render: (row) => row.installment_number },
        {
            key: "due",
            header: "Due date",
            render: (row) => formatDate(row.due_date),
        },
        {
            key: "principal",
            header: "Principal",
            render: (row) => row.principal_due,
        },
        {
            key: "interest",
            header: "Interest",
            render: (row) => row.interest_due,
        },
        { key: "total", header: "Total", render: (row) => row.total_due },
        { key: "balance", header: "Balance", render: (row) => row.balance_due },
        {
            key: "status",
            header: "Status",
            render: (row) => <StatusBadge status={row.status} />,
        },
        {
            key: "action",
            header: "",
            className: "text-right",
            render: (row) =>
                canManage && !row.invoice_id ? (
                    <Button
                        variant="secondary"
                        onClick={() => void createPayable(row)}
                    >
                        Create payable
                    </Button>
                ) : row.invoice_id ? (
                    `Invoice #${row.invoice_id}`
                ) : null,
        },
    ];

    return (
        <RentalPage>
            <ContentHeader
                title="Vehicle finance"
                description="Track leasing-company agreements and installment schedules separately from usage-based owner payables."
            />
            <ErrorAlert error={actionError ?? result.error} />
            {canManage && (
                <form onSubmit={save} className="mb-5">
                    <Panel title="New finance agreement">
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <SupplierLookupSelect
                                value={supplier}
                                onChange={setSupplier}
                            />
                            <VehicleLookupSelect
                                value={vehicle}
                                onChange={setVehicle}
                            />
                            <Input
                                label="Agreement number"
                                value={form.agreement_number}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        agreement_number: event.target.value,
                                    })
                                }
                            />
                            <Input
                                label="Agreement date"
                                type="date"
                                required
                                value={form.agreement_date}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        agreement_date: event.target.value,
                                    })
                                }
                            />
                            <Input
                                label="Starts at"
                                type="date"
                                required
                                value={form.starts_at}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        starts_at: event.target.value,
                                    })
                                }
                            />
                            <Input
                                label="Matures at"
                                type="date"
                                required
                                value={form.matures_at}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        matures_at: event.target.value,
                                    })
                                }
                            />
                            <Input
                                label="Currency ID"
                                type="number"
                                min="1"
                                required
                                value={form.currency_id}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        currency_id: event.target.value,
                                    })
                                }
                            />
                            <Input
                                label="Principal"
                                type="number"
                                min="0.000001"
                                step="0.000001"
                                required
                                value={form.principal_amount}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        principal_amount: event.target.value,
                                    })
                                }
                            />
                            <Input
                                label="Initial deposit"
                                type="number"
                                min="0"
                                step="0.000001"
                                value={form.initial_deposit_amount}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        initial_deposit_amount:
                                            event.target.value,
                                    })
                                }
                            />
                            <Input
                                label="Residual value"
                                type="number"
                                min="0"
                                step="0.000001"
                                value={form.residual_value}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        residual_value: event.target.value,
                                    })
                                }
                            />
                            <Select
                                label="Interest method"
                                value={form.interest_method}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        interest_method: event.target.value,
                                    })
                                }
                                options={[
                                    "flat",
                                    "reducing_balance",
                                    "custom",
                                ].map((value) => ({
                                    value,
                                    label: value.replaceAll("_", " "),
                                }))}
                            />
                            <Input
                                label="Annual interest %"
                                type="number"
                                min="0"
                                step="0.000001"
                                value={form.annual_interest_rate}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        annual_interest_rate:
                                            event.target.value,
                                    })
                                }
                            />
                            <Select
                                label="Frequency"
                                value={form.installment_frequency}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        installment_frequency:
                                            event.target.value,
                                    })
                                }
                                options={[
                                    "weekly",
                                    "monthly",
                                    "quarterly",
                                    "yearly",
                                ].map((value) => ({ value, label: value }))}
                            />
                            <Input
                                label="Installment count"
                                type="number"
                                min="1"
                                max="600"
                                required
                                value={form.installment_count}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        installment_count: event.target.value,
                                    })
                                }
                            />
                            <Input
                                label="Payment term days"
                                type="number"
                                min="0"
                                value={form.payment_term_days}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        payment_term_days: event.target.value,
                                    })
                                }
                            />
                        </div>
                        <div className="mt-4">
                            <Textarea
                                label="Remarks"
                                value={form.remarks}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        remarks: event.target.value,
                                    })
                                }
                            />
                        </div>
                        <div className="mt-4 flex justify-end">
                            <Button
                                type="submit"
                                loading={saving}
                                disabled={!supplier || !vehicle}
                            >
                                Create finance agreement
                            </Button>
                        </div>
                    </Panel>
                </form>
            )}
            {selected && (
                <Panel
                    title={`Installment schedule — ${selected.agreement_number}`}
                    className="mb-5"
                >
                    <div className="mb-3 flex justify-end">
                        <Button
                            variant="ghost"
                            onClick={() => setSelected(null)}
                        >
                            Close
                        </Button>
                    </div>
                    <DataTable
                        rows={selected.installments ?? []}
                        columns={installmentColumns}
                        rowKey={(row) => row.id}
                        emptyMessage="No installments generated."
                    />
                </Panel>
            )}
            {result.loading ? (
                <LoadingState />
            ) : (
                <DataTable
                    rows={result.data?.data ?? []}
                    columns={columns}
                    rowKey={(row) => row.id}
                    emptyMessage="No vehicle finance agreements found."
                />
            )}
        </RentalPage>
    );
}
