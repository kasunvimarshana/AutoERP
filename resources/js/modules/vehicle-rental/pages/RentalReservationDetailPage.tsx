import { useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { toApiError, type ApiError } from "@/shared/api/apiError";
import { Button, LinkButton } from "@/shared/components/Button";
import { ContentHeader } from "@/shared/components/ContentHeader";
import { DetailGrid } from "@/shared/components/DetailGrid";
import { ErrorAlert } from "@/shared/components/ErrorAlert";
import { LoadingState } from "@/shared/components/LoadingState";
import { Panel } from "@/shared/components/Panel";
import { StatusBadge } from "@/shared/components/StatusBadge";
import { useApi } from "@/shared/hooks/useApi";
import { formatDate } from "@/shared/utils/formatDate";
import { readableRelation } from "@/shared/utils/object";
import { RentalPage } from "../components/RentalPage";
import {
    getRentalReservation,
    transitionRentalReservation,
} from "../vehicleRentalApi";

export default function RentalReservationDetailPage() {
    const id = Number(useParams().id);
    const navigate = useNavigate();
    const result = useApi((signal) => getRentalReservation(id, signal), [id]);
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const transition = async (status: string) => {
        if (!result.data) return;

        setBusy(true);
        try {
            await transitionRentalReservation(id, result.data.row_version, status);
            navigate(0);
        } catch (e) {
            setError(toApiError(e));
        } finally {
            setBusy(false);
        }
    };
    if (result.loading)
        return (
            <RentalPage>
                <LoadingState />
            </RentalPage>
        );
    if (!result.data)
        return (
            <RentalPage>
                <ErrorAlert error={result.error} />
            </RentalPage>
        );
    const row = result.data;
    return (
        <RentalPage>
            <ContentHeader
                title={row.reservation_number}
                description="Customer rental request and conversion readiness."
                actions={
                    <>
                        {row.status === "draft" && (
                            <Button
                                loading={busy}
                                onClick={() => void transition("pending")}
                            >
                                Submit
                            </Button>
                        )}
                        {row.status === "pending" && (
                            <Button
                                loading={busy}
                                onClick={() => void transition("confirmed")}
                            >
                                Confirm
                            </Button>
                        )}
                        <LinkButton
                            variant="secondary"
                            to={`/vehicle-rental/agreements/create?reservation_id=${row.id}`}
                        >
                            Create agreement
                        </LinkButton>
                    </>
                }
            />
            <ErrorAlert error={error ?? result.error} />
            <Panel>
                <DetailGrid
                    items={[
                        {
                            label: "Customer",
                            value: readableRelation(row.customer),
                        },
                        {
                            label: "Requested vehicle",
                            value: readableRelation(row.requested_vehicle),
                        },
                        {
                            label: "Period",
                            value: `${formatDate(row.requested_start_at)} - ${formatDate(row.requested_end_at)}`,
                        },
                        {
                            label: "Rental mode",
                            value: row.rental_mode.replaceAll("_", " "),
                        },
                        { label: "Billing cycle", value: row.billing_cycle },
                        {
                            label: "Status",
                            value: <StatusBadge status={row.status} />,
                        },
                        {
                            label: "Estimated amount",
                            value: row.estimated_amount,
                        },
                        {
                            label: "Estimated deposit",
                            value: row.estimated_deposit_amount,
                        },
                    ]}
                />
            </Panel>
        </RentalPage>
    );
}
