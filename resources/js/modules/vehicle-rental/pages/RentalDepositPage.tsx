import { useState, type FormEvent } from "react";
import { Link } from "react-router-dom";
import { useAuth } from "@/modules/auth/AuthProvider";
import { hasPermission } from "@/modules/auth/accessControl";
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
import type { NamedResource } from "@/shared/types/common";
import { businessDateInputValue } from "@/shared/utils/businessDate";
import { readableRelation } from "@/shared/utils/object";
import {
    RentalInvoiceLookupSelect,
    RentalPaymentMethodLookupSelect,
} from "../components/RentalLookups";
import { RentalPage } from "../components/RentalPage";
import { agreementDetailPath } from "../rentalAgreementPresentation";
import {
    applyRentalDeposit,
    forfeitRentalDeposit,
    listRentalDeposits,
    receiveRentalDeposit,
    reverseRentalDepositLink,
    refundRentalDeposit,
} from "../vehicleRentalApi";
import { vehicleRentalPermissions } from "../vehicleRentalPermissions";
import type { RentalDeposit, RentalDepositLink } from "../vehicleRentalTypes";


export default function RentalDepositPage() {
    const auth = useAuth();
    const [status, setStatus] = useState("");
    const [selected, setSelected] = useState<RentalDeposit | null>(null);
    const [action, setAction] = useState("receive");
    const [amount, setAmount] = useState("");
    const [invoice, setInvoice] = useState<NamedResource | null>(null);
    const [paymentMethod, setPaymentMethod] = useState<NamedResource | null>(null);
    const [receiptLinkId, setReceiptLinkId] = useState("");
    const [reference, setReference] = useState("");
    const [reason, setReason] = useState("");
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
    const canManage = hasPermission(
        auth,
        vehicleRentalPermissions.depositsManage,
    );
    const receiptLinks = (selected?.links ?? []).filter((link) =>
        link.link_type === "receipt"
        && link.status === "active"
        && link.payment?.id
        && link.payment.row_version,
    );
    const selectedReceiptLink = receiptLinks.find((link) => String(link.id) === receiptLinkId)
        ?? receiptLinks[0]
        ?? null;
    const receiptSelectValue = receiptLinkId || (receiptLinks[0] ? String(receiptLinks[0].id) : "");

    const submit = async (event: FormEvent) => {
        event.preventDefault();
        if (!selected) return;
        setSaving(true);
        setActionError(null);
        try {
            if (action === "receive") {
                await receiveRentalDeposit(selected.id, {
                    expected_requirement_version: selected.row_version,
                    amount,
                    payment_date: businessDateInputValue(),
                    payment_method_id: paymentMethod?.id,
                    reference_number: reference || null,
                });
            } else if (action === "refund") {
                if (!selectedReceiptLink?.payment) return;
                await refundRentalDeposit(selected.id, {
                    expected_requirement_version: selected.row_version,
                    payment_id: selectedReceiptLink.payment.id,
                    expected_payment_version: selectedReceiptLink.payment.row_version,
                    amount,
                    refund_date: businessDateInputValue(),
                    payment_method_id: paymentMethod?.id,
                    reference_number: reference || null,
                    reason,
                });
            } else if (action === "apply") {
                if (!selectedReceiptLink?.payment) return;
                await applyRentalDeposit(selected.id, {
                    expected_requirement_version: selected.row_version,
                    payment_id: selectedReceiptLink.payment.id,
                    expected_payment_version: selectedReceiptLink.payment.row_version,
                    invoice_id: invoice?.id,
                    amount,
                    allocation_date: businessDateInputValue(),
                });
            } else {
                if (!selectedReceiptLink?.payment) return;
                await forfeitRentalDeposit(selected.id, {
                    expected_requirement_version: selected.row_version,
                    payment_id: selectedReceiptLink.payment.id,
                    expected_payment_version: selectedReceiptLink.payment.row_version,
                    invoice_id: invoice?.id,
                    amount,
                    allocation_date: businessDateInputValue(),
                });
            }
            setAmount("");
            setInvoice(null);
            setPaymentMethod(null);
            setReceiptLinkId("");
            setReference("");
            setReason("");
            setSelected(null);
            result.reload();
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setSaving(false);
        }
    };

    const reverseLink = async (link: RentalDepositLink) => {
        if (!selected || !link.payment?.row_version) return;
        const reversalReason = window.prompt("Enter the reason for reversing this deposit movement.");
        if (!reversalReason?.trim()) return;

        setSaving(true);
        setActionError(null);
        try {
            const updated = await reverseRentalDepositLink(
                link.id,
                selected.row_version,
                link.payment.row_version,
                reversalReason.trim(),
            );
            setSelected(updated);
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
                        to={agreementDetailPath("lessee", row.agreement.id)}
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
                        onClick={() => {
                            setSelected(row);
                            setAmount("");
                            setInvoice(null);
                            setPaymentMethod(null);
                            setReceiptLinkId("");
                            setReference("");
                            setReason("");
                        }}
                    >
                        Manage
                    </Button>
                ) : null,
        },
    ];
    const linkColumns: DataColumn<RentalDepositLink>[] = [
        {
            key: "type",
            header: "Movement",
            render: (row) => row.link_type.replaceAll("_", " "),
        },
        {
            key: "reference",
            header: "Reference",
            render: (row) =>
                depositLinkReference(row),
        },
        {
            key: "amount",
            header: "Amount",
            render: (row) => row.amount,
        },
        {
            key: "status",
            header: "Status",
            render: (row) => <StatusBadge status={row.status} />,
        },
        {
            key: "linked",
            header: "Linked",
            render: (row) => formatDate(row.linked_at),
        },
        {
            key: "action",
            header: "",
            className: "text-right",
            render: (row) =>
                canManage
                && row.status === "active"
                && row.link_type !== "reversal"
                && row.payment?.row_version ? (
                    <Button
                        variant="secondary"
                        loading={saving}
                        onClick={() => void reverseLink(row)}
                    >
                        Reverse
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
                    title={`Manage deposit - ${readableRelation(selected.agreement)}`}
                    className="mb-5"
                >
                    <form
                        onSubmit={submit}
                        className="grid gap-4 md:grid-cols-2 xl:grid-cols-5"
                    >
                        <Select
                            label="Action"
                            value={action}
                            onChange={(event) => {
                                setAction(event.target.value);
                                setInvoice(null);
                                setPaymentMethod(null);
                                setReceiptLinkId("");
                                setReason("");
                            }}
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
                            <RentalInvoiceLookupSelect
                                value={invoice}
                                onChange={setInvoice}
                                invoiceType="rental"
                                direction="outbound"
                                partyId={selected.customer?.id ?? null}
                                disabled={!selected.customer?.id}
                                required
                            />
                        )}
                        {(action === "apply" || action === "refund" || action === "forfeit") && (
                            <Select
                                label="Deposit receipt"
                                value={receiptSelectValue}
                                onChange={(event) => setReceiptLinkId(event.target.value)}
                                required
                                options={receiptLinks.map((link) => ({
                                    value: String(link.id),
                                    label: [
                                        link.payment?.payment_number ?? link.payment?.name ?? `Payment ${link.payment?.id}`,
                                        link.amount,
                                        link.payment?.posting_status?.replaceAll("_", " "),
                                    ].filter(Boolean).join(" - "),
                                }))}
                            />
                        )}
                        {(action === "receive" || action === "refund") && (
                            <RentalPaymentMethodLookupSelect
                                value={paymentMethod}
                                onChange={setPaymentMethod}
                                direction={action === "refund" ? "outbound" : "inbound"}
                                required
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
                        {action === "refund" && (
                            <Input
                                label="Reason"
                                required
                                value={reason}
                                onChange={(event) =>
                                    setReason(event.target.value)
                                }
                            />
                        )}
                        <div className="flex items-end gap-2">
                            <Button
                                type="submit"
                                loading={saving}
                                disabled={
                                    !amount ||
                                    ((action === "apply" || action === "forfeit") && !invoice) ||
                                    ((action === "receive" || action === "refund") && !paymentMethod) ||
                                    ((action === "apply" || action === "refund" || action === "forfeit") && !selectedReceiptLink) ||
                                    (action === "refund" && !reason.trim())
                                }
                            >
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
                    {(selected.links?.length ?? 0) > 0 && (
                        <div className="mt-4">
                            <DataTable
                                rows={selected.links ?? []}
                                columns={linkColumns}
                                rowKey={(row) => row.id}
                            />
                        </div>
                    )}
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

function depositLinkReference(link: RentalDepositLink): string {
    if (link.payment) return readableRelation(link.payment);
    if (link.invoice) return readableRelation(link.invoice);
    if (link.reverses_link) {
        return [
            "Reverses",
            link.reverses_link.link_type?.replaceAll("_", " "),
            link.reverses_link.amount,
        ].filter(Boolean).join(" ");
    }

    return "-";
}
