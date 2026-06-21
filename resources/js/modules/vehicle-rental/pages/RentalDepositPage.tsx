import { useState, type FormEvent } from "react";
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
    applyRentalDeposit,
    forfeitRentalDeposit,
    listRentalDeposits,
    receiveRentalDeposit,
    refundRentalDeposit,
} from "../vehicleRentalApi";
import { vehicleRentalPermissions } from "../vehicleRentalPermissions";
import type { RentalDeposit } from "../vehicleRentalTypes";

const today = new Date().toISOString().slice(0, 10);

export default function RentalDepositPage() {
    const auth = useAuth();
    const [status, setStatus] = useState("");
    const [selected, setSelected] = useState<RentalDeposit | null>(null);
    const [action, setAction] = useState("receive");
    const [amount, setAmount] = useState("");
    const [invoiceId, setInvoiceId] = useState("");
    const [paymentMethodId, setPaymentMethodId] = useState("");
    const [reference, setReference] = useState("");
    const [saving, setSaving] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);

    const result = useApi(
        (signal) =>
            listRentalDeposits(
                { status: status || undefined, per_page: 50 },
                signal,
            ),
        [status],
    );
    const canManage = auth.permissions.includes(
        vehicleRentalPermissions.depositsManage,
    );

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        if (!selected) return;
        setSaving(true);
        setActionError(null);
        try {
            if (action === "receive") {
                await receiveRentalDeposit(selected.id, {
                    amount,
                    payment_date: today,
                    payment_method_id: Number(paymentMethodId),
                    reference_number: reference || null,
                });
            } else if (action === "refund") {
                await refundRentalDeposit(selected.id, {
                    amount,
                    payment_date: today,
                    payment_method_id: Number(paymentMethodId),
                    reference_number: reference || null,
                });
            } else if (action === "apply") {
                await applyRentalDeposit(selected.id, {
                    invoice_id: Number(invoiceId),
                    amount,
                });
            } else {
                await forfeitRentalDeposit(selected.id, {
                    invoice_id: Number(invoiceId),
                    amount,
                });
            }
            setAmount("");
            setInvoiceId("");
            setReference("");
            setSelected(null);
            result.reload();
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setSaving(false);
        }
    };

    const columns: DataColumn<RentalDeposit>[] = [
        {
            key: "agreement",
            header: "Agreement",
            render: (row) =>
                row.agreement ? (
                    <Link
                        className="font-semibold text-blue-700"
                        to={`/vehicle-rental/agreements/${row.agreement.id}`}
                    >
                        {readableRelation(row.agreement)}
                    </Link>
                ) : (
                    "-"
                ),
        },
        {
            key: "required",
            header: "Required",
            render: (row) => row.required_amount,
        },
        {
            key: "received",
            header: "Received",
            render: (row) => row.received_amount,
        },
        {
            key: "applied",
            header: "Applied",
            render: (row) => row.applied_amount,
        },
        {
            key: "refunded",
            header: "Refunded",
            render: (row) => row.refunded_amount,
        },
        {
            key: "balance",
            header: "Balance",
            render: (row) => row.balance_amount,
        },
        {
            key: "status",
            header: "Status",
            render: (row) => <StatusBadge status={row.status} />,
        },
        {
            key: "due",
            header: "Due",
            render: (row) => formatDate(row.due_date),
        },
        {
            key: "action",
            header: "",
            className: "text-right",
            render: (row) =>
                canManage ? (
                    <Button
                        variant="secondary"
                        onClick={() => setSelected(row)}
                    >
                        Manage
                    </Button>
                ) : null,
        },
    ];

    return (
        <RentalPage>
            <ContentHeader
                title="Security deposits"
                description="Track the contractual requirement, liability balance, invoice applications, refunds and forfeitures."
            />
            <ErrorAlert error={actionError ?? result.error} />
            <div className="mb-4 max-w-sm">
                <Select
                    label="Status"
                    value={status}
                    onChange={(event) => setStatus(event.target.value)}
                    options={[
                        "pending",
                        "partially_received",
                        "received",
                        "partially_applied",
                        "refunded",
                        "forfeited",
                        "closed",
                    ].map((value) => ({
                        value,
                        label: value.replaceAll("_", " "),
                    }))}
                />
            </div>
            {selected && (
                <Panel
                    title={`Manage deposit — ${readableRelation(selected.agreement)}`}
                    className="mb-5"
                >
                    <form
                        onSubmit={submit}
                        className="grid gap-4 md:grid-cols-2 xl:grid-cols-5"
                    >
                        <Select
                            label="Action"
                            value={action}
                            onChange={(event) => setAction(event.target.value)}
                            options={[
                                { value: "receive", label: "Receive deposit" },
                                { value: "apply", label: "Apply to invoice" },
                                { value: "refund", label: "Refund deposit" },
                                {
                                    value: "forfeit",
                                    label: "Forfeit against invoice",
                                },
                            ]}
                        />
                        <Input
                            label="Amount"
                            type="number"
                            min="0.000001"
                            step="0.000001"
                            required
                            value={amount}
                            onChange={(event) => setAmount(event.target.value)}
                        />
                        {(action === "apply" || action === "forfeit") && (
                            <Input
                                label="Invoice ID"
                                type="number"
                                min="1"
                                required
                                value={invoiceId}
                                onChange={(event) =>
                                    setInvoiceId(event.target.value)
                                }
                            />
                        )}
                        {(action === "receive" || action === "refund") && (
                            <Input
                                label="Payment method ID"
                                type="number"
                                min="1"
                                required
                                value={paymentMethodId}
                                onChange={(event) =>
                                    setPaymentMethodId(event.target.value)
                                }
                            />
                        )}
                        {(action === "receive" || action === "refund") && (
                            <Input
                                label="Reference"
                                value={reference}
                                onChange={(event) =>
                                    setReference(event.target.value)
                                }
                            />
                        )}
                        <div className="flex items-end gap-2">
                            <Button type="submit" loading={saving}>
                                Save
                            </Button>
                            <Button
                                variant="ghost"
                                onClick={() => setSelected(null)}
                            >
                                Cancel
                            </Button>
                        </div>
                    </form>
                </Panel>
            )}
            {result.loading ? (
                <LoadingState />
            ) : (
                <DataTable
                    rows={result.data?.data ?? []}
                    columns={columns}
                    rowKey={(row) => row.id}
                    emptyMessage="No deposit requirements found."
                />
            )}
        </RentalPage>
    );
}
