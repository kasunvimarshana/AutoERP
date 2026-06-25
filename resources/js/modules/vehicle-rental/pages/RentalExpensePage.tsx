import { useState, type FormEvent } from "react";
import type { CustomerSummary } from "@/modules/customer/customerTypes";
import { CustomerLookupSelect } from "@/modules/customer/components/CustomerLookupSelect";
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
    createRentalExpense,
    listRentalExpenses,
    transitionRentalExpense,
} from "../vehicleRentalApi";
import { vehicleRentalPermissions } from "../vehicleRentalPermissions";
import type { RentalExpense } from "../vehicleRentalTypes";

interface ExpenseForm {
    expense_type: string;
    expense_date: string;
    currency_id: string;
    net_amount: string;
    tax_amount: string;
    tax_group_id: string;
    reference_number: string;
    description: string;
    allocation_type: string;
    target_agreement_id: string;
    target_vehicle_allocation_id: string;
    withholding_amount: string;
    markup_amount: string;
}

const emptyForm = (): ExpenseForm => ({
    expense_type: "fuel",
    expense_date: new Date().toISOString().slice(0, 10),
    currency_id: "",
    net_amount: "",
    tax_amount: "0",
    tax_group_id: "",
    reference_number: "",
    description: "",
    allocation_type: "company_cost",
    target_agreement_id: "",
    target_vehicle_allocation_id: "",
    withholding_amount: "0",
    markup_amount: "0",
});

export default function RentalExpensePage() {
    const auth = useAuth();
    const [form, setForm] = useState<ExpenseForm>(emptyForm);
    const [vehicle, setVehicle] = useState<VehicleSummary | null>(null);
    const [customer, setCustomer] = useState<CustomerSummary | null>(null);
    const [supplier, setSupplier] = useState<SupplierSummary | null>(null);
    const [saving, setSaving] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const [status, setStatus] = useState("");

    const result = useApi(
        (signal) =>
            listRentalExpenses(
                { status: status || undefined, per_page: 50 },
                signal,
            ),
        [status],
    );
    const canRecord = auth.permissions.includes(
        vehicleRentalPermissions.expensesRecord,
    );
    const canApprove = auth.permissions.includes(
        vehicleRentalPermissions.expensesApprove,
    );

    const save = async (event: FormEvent) => {
        event.preventDefault();
        if (!vehicle) return;
        setSaving(true);
        setActionError(null);
        try {
            await createRentalExpense({
                vehicle_id: vehicle.id,
                expense_type: form.expense_type,
                expense_date: form.expense_date,
                currency_id: Number(form.currency_id),
                net_amount: form.net_amount,
                tax_group_id: form.tax_group_id
                    ? Number(form.tax_group_id)
                    : null,
                tax_amount: form.tax_amount,
                reference_number: form.reference_number || null,
                description: form.description || null,
                allocations: [
                    {
                        allocation_type: form.allocation_type,
                        target_agreement_id: form.target_agreement_id
                            ? Number(form.target_agreement_id)
                            : null,
                        target_vehicle_allocation_id:
                            form.target_vehicle_allocation_id
                                ? Number(form.target_vehicle_allocation_id)
                                : null,
                        customer_id:
                            form.allocation_type === "customer_recovery"
                                ? (customer?.id ?? null)
                                : null,
                        supplier_id:
                            form.allocation_type === "owner_deduction"
                                ? (supplier?.id ?? null)
                                : null,
                        net_amount: form.net_amount,
                        tax_group_id: form.tax_group_id
                            ? Number(form.tax_group_id)
                            : null,
                        tax_amount: form.tax_amount,
                        withholding_amount: form.withholding_amount,
                        markup_amount: form.markup_amount,
                    },
                ],
            });
            setForm(emptyForm());
            setVehicle(null);
            setCustomer(null);
            setSupplier(null);
            result.reload();
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setSaving(false);
        }
    };

    const transition = async (row: RentalExpense, nextStatus: string) => {
        setActionError(null);
        try {
            await transitionRentalExpense(row.id, nextStatus);
            result.reload();
        } catch (error) {
            setActionError(toApiError(error));
        }
    };

    const columns: DataColumn<RentalExpense>[] = [
        {
            key: "number",
            header: "Expense",
            render: (row) => row.expense_number,
        },
        {
            key: "date",
            header: "Date",
            render: (row) => formatDate(row.expense_date),
        },
        {
            key: "vehicle",
            header: "Vehicle",
            render: (row) => readableRelation(row.vehicle),
        },
        {
            key: "type",
            header: "Type",
            render: (row) => row.expense_type.replaceAll("_", " "),
        },
        {
            key: "amount",
            header: "Gross amount",
            render: (row) => row.gross_amount,
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
                    {canRecord && row.status === "draft" && (
                        <Button
                            variant="ghost"
                            onClick={() => void transition(row, "submitted")}
                        >
                            Submit
                        </Button>
                    )}
                    {canApprove && row.status === "submitted" && (
                        <Button
                            variant="ghost"
                            onClick={() => void transition(row, "approved")}
                        >
                            Approve
                        </Button>
                    )}
                    {canApprove && row.status === "submitted" && (
                        <Button
                            variant="ghost"
                            onClick={() => void transition(row, "rejected")}
                        >
                            Reject
                        </Button>
                    )}
                </div>
            ),
        },
    ];

    return (
        <RentalPage>
            <ContentHeader
                title="Rental expenses"
                description="Record an operational expense once, then allocate it as company cost, customer recovery or owner deduction."
            />
            <ErrorAlert error={actionError ?? result.error} />
            {canRecord && (
                <form onSubmit={save} className="mb-5">
                    <Panel title="New expense">
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <VehicleLookupSelect
                                value={vehicle}
                                onChange={setVehicle}
                            />
                            <Select
                                label="Expense type"
                                value={form.expense_type}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        expense_type: event.target.value,
                                    })
                                }
                                options={[
                                    "fuel",
                                    "toll",
                                    "parking",
                                    "repair",
                                    "service",
                                    "driver_allowance",
                                    "licence",
                                    "insurance",
                                    "damage",
                                    "other",
                                ].map((value) => ({
                                    value,
                                    label: value.replaceAll("_", " "),
                                }))}
                            />
                            <Input
                                label="Expense date"
                                type="date"
                                required
                                value={form.expense_date}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        expense_date: event.target.value,
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
                                label="Net amount"
                                type="number"
                                min="0.000001"
                                step="0.000001"
                                required
                                value={form.net_amount}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        net_amount: event.target.value,
                                    })
                                }
                            />
                            <Input
                                label="Tax amount"
                                type="number"
                                min="0"
                                step="0.000001"
                                value={form.tax_amount}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        tax_amount: event.target.value,
                                    })
                                }
                            />
                            <Input
                                label="Tax group ID"
                                type="number"
                                min="1"
                                value={form.tax_group_id}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        tax_group_id: event.target.value,
                                    })
                                }
                            />
                            <Input
                                label="Reference"
                                value={form.reference_number}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        reference_number: event.target.value,
                                    })
                                }
                            />
                            <Select
                                label="Financial treatment"
                                value={form.allocation_type}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        allocation_type: event.target.value,
                                    })
                                }
                                options={[
                                    "company_cost",
                                    "customer_recovery",
                                    "owner_deduction",
                                    "employee_reimbursement",
                                ].map((value) => ({
                                    value,
                                    label: value.replaceAll("_", " "),
                                }))}
                            />
                            <Input
                                label="Target agreement ID"
                                type="number"
                                min="1"
                                value={form.target_agreement_id}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        target_agreement_id: event.target.value,
                                    })
                                }
                            />
                            <Input
                                label="Target allocation ID"
                                type="number"
                                min="1"
                                value={form.target_vehicle_allocation_id}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        target_vehicle_allocation_id:
                                            event.target.value,
                                    })
                                }
                            />
                            <Input
                                label="Withholding amount"
                                type="number"
                                min="0"
                                step="0.000001"
                                value={form.withholding_amount}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        withholding_amount: event.target.value,
                                    })
                                }
                            />
                            <Input
                                label="Markup amount"
                                type="number"
                                min="0"
                                step="0.000001"
                                value={form.markup_amount}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        markup_amount: event.target.value,
                                    })
                                }
                            />
                            {form.allocation_type === "customer_recovery" && (
                                <CustomerLookupSelect
                                    value={customer}
                                    onChange={setCustomer}
                                />
                            )}
                            {form.allocation_type === "owner_deduction" && (
                                <SupplierLookupSelect
                                    value={supplier}
                                    onChange={setSupplier}
                                />
                            )}
                        </div>
                        <div className="mt-4">
                            <Textarea
                                label="Description"
                                value={form.description}
                                onChange={(event) =>
                                    setForm({
                                        ...form,
                                        description: event.target.value,
                                    })
                                }
                            />
                        </div>
                        <div className="mt-4 flex justify-end">
                            <Button
                                type="submit"
                                loading={saving}
                                disabled={
                                    !vehicle ||
                                    !form.currency_id ||
                                    !form.net_amount
                                }
                            >
                                Save expense
                            </Button>
                        </div>
                    </Panel>
                </form>
            )}
            <div className="mb-4 max-w-sm">
                <Select
                    label="Status"
                    value={status}
                    onChange={(event) => setStatus(event.target.value)}
                    options={[
                        "draft",
                        "submitted",
                        "approved",
                        "rejected",
                        "allocated",
                        "reversed",
                    ].map((value) => ({ value, label: value }))}
                />
            </div>
            {result.loading ? (
                <LoadingState />
            ) : (
                <DataTable
                    rows={result.data?.data ?? []}
                    columns={columns}
                    rowKey={(row) => row.id}
                    emptyMessage="No rental expenses found."
                />
            )}
        </RentalPage>
    );
}
