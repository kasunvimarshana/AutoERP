import { useMemo, useState } from 'react';
import { useParams } from 'react-router-dom';
import { Button } from '../../../components/ui/Button';
import { ContentCard } from '../../../components/ui/ContentCard';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { Input } from '../../../components/forms/Input';
import { PageHeader } from '../../../components/layout/PageHeader';
import { DataTable, type DataTableColumn } from '../../../components/tables';
import { useAuth } from '../../auth/context/AuthContext';
import { useTenant } from '../../auth/context/TenantContext';
import { ConfirmWorkflowModal, DocumentHeader, WorkflowActionBar } from '../../shared/workflow';
import { formatCurrency, formatDateTime, formatQuantity, parsePositiveInteger } from '../../shared/utils';
import { useCompleteCycleCount, useCycleCount } from '../hooks';
import type { CycleCountLineRecord } from '../types';

export function CycleCountDetailPage() {
    const { tenantId } = useTenant();
    const { user } = useAuth();
    const { cycleCountId: cycleCountIdParam } = useParams();
    const cycleCountId = parsePositiveInteger(cycleCountIdParam ?? null, 0);
    const [draftCounts, setDraftCounts] = useState<Record<number, string>>({});
    const [confirmOpen, setConfirmOpen] = useState(false);
    const cycleCountQuery = useCycleCount(cycleCountId, tenantId, cycleCountId > 0);
    const completeMutation = useCompleteCycleCount(cycleCountId, tenantId);

    if (cycleCountId <= 0) {
        return <ErrorState description="The cycle count route is missing a valid cycle count ID." title="Invalid cycle count route" />;
    }

    if (cycleCountQuery.isPending) {
        return <LoadingState lines={10} />;
    }

    if (cycleCountQuery.isError) {
        return <ErrorState description={cycleCountQuery.error.message} title="Unable to load cycle count" />;
    }

    const cycleCount = cycleCountQuery.data;
    const lines = useMemo(
        () =>
            cycleCount.lines.map((line) => ({
                ...line,
                draftCountedQty: draftCounts[line.id] ?? String(line.counted_qty ?? line.system_qty ?? 0),
            })),
        [cycleCount.lines, draftCounts],
    );

    const columns: DataTableColumn<(CycleCountLineRecord & { draftCountedQty: string })>[] = [
        { key: 'product_id', header: 'Product', render: (line) => <span className="font-medium text-stone-950">#{line.product_id}</span> },
        { key: 'system_qty', header: 'System Qty', render: (line) => <span className="text-sm text-stone-700">{formatQuantity(line.system_qty)}</span> },
        {
            key: 'counted_qty',
            header: 'Counted Qty',
            render: (line) => (
                <Input
                    className="min-w-[7rem]"
                    label={undefined}
                    onChange={(event) => setDraftCounts((current) => ({ ...current, [line.id]: event.target.value }))}
                    value={line.draftCountedQty}
                />
            ),
        },
        { key: 'variance_qty', header: 'Variance', render: (line) => <span className="text-sm text-stone-700">{formatQuantity(line.variance_qty)}</span> },
        { key: 'variance_value', header: 'Variance Value', render: (line) => <span className="text-sm text-stone-700">{formatCurrency(line.variance_value)}</span> },
    ];

    async function handleCompleteConfirm() {
        await completeMutation.mutateAsync({
            tenant_id: tenantId,
            approved_by_user_id: user?.id ?? 1,
            lines: lines.map((line) => ({
                line_id: line.id,
                counted_qty: Number(line.draftCountedQty),
            })),
        });
        setConfirmOpen(false);
    }

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader breadcrumbs={[{ label: 'Inventory', href: '/inventory' }, { label: 'Cycle Counts', href: '/inventory/cycle-counts' }, { label: `Count #${cycleCount.id}` }]} description="Cycle count detail lets the team review system quantities, enter counted quantities, and complete the count using the supported backend workflow action." title={`Cycle Count #${cycleCount.id}`} />

            <DocumentHeader
                dateLabel="Counted At"
                dateValue={cycleCount.counted_at}
                documentNumber={`Count #${cycleCount.id}`}
                documentNumberLabel="Cycle Count"
                helperText="Use the editable counted quantities below before completing the count. Completion submits the line IDs and current counted quantities."
                metrics={[
                    { label: 'Warehouse', value: `#${cycleCount.warehouse_id}` },
                    { label: 'Location', value: cycleCount.location_id ? `#${cycleCount.location_id}` : 'All locations' },
                    { label: 'Approver', value: cycleCount.approved_by_user_id ? `#${cycleCount.approved_by_user_id}` : 'Pending' },
                ]}
                primaryPartyLabel="Counter"
                primaryPartyValue={cycleCount.counted_by_user_id ? `User #${cycleCount.counted_by_user_id}` : 'Unassigned'}
                status={cycleCount.status}
                title="Cycle count workflow"
            />

            <ContentCard className="p-0">
                <DataTable columns={columns} emptyState={<div className="p-6 text-sm text-stone-500">No count lines available.</div>} getRowKey={(line) => line.id} rows={lines} />
            </ContentCard>

            <ContentCard>
                <WorkflowActionBar description="Draft cycle counts can be started from the list screen. In-progress counts can be completed here using the current line values.">
                    {cycleCount.status === 'in_progress' ? <Button onClick={() => setConfirmOpen(true)} type="button">Complete Count</Button> : null}
                </WorkflowActionBar>
            </ContentCard>

            <ConfirmWorkflowModal confirmLabel="Complete cycle count" description={`Complete cycle count #${cycleCount.id} with the current counted quantities?`} isLoading={completeMutation.isPending} onCancel={() => setConfirmOpen(false)} onConfirm={() => void handleCompleteConfirm()} open={confirmOpen} title="Complete cycle count" />
        </div>
    );
}
