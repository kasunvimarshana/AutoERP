import { useState } from 'react';
import { useParams } from 'react-router-dom';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { useApi } from '@/shared/hooks/useApi';
import { readableRelation } from '@/shared/utils/object';
import { RentalStatusBadge } from '../components/RentalStatusBadge';
import { UsageEventEditor } from '../components/UsageEventEditor';
import { UsageLogEditor } from '../components/UsageLogEditor';
import { getRentalAgreement, listRentalUsageLogs } from '../vehicleRentalApi';
import type { RentalUsageLog } from '../vehicleRentalTypes';

export default function UsageLogPage() {
    const agreementId = Number(useParams().id);
    const agreement = useApi((signal) => getRentalAgreement(agreementId, signal), [agreementId]);
    const logs = useApi((signal) => listRentalUsageLogs(agreementId, signal), [agreementId]);
    const [selectedId, setSelectedId] = useState<number | null>(null);
    if (agreement.loading) return <LoadingState />;
    if (!agreement.data) return <ErrorAlert error={agreement.error} />;
    const rows = logs.data ?? [];
    const selected = rows.find((row) => row.id === selectedId) ?? null;
    const appendLog = (log: RentalUsageLog) => {
        logs.reload();
        setSelectedId(log.id);
    };
    return (
        <>
            <ContentHeader title={`Running chart / ${agreement.data.agreement_number}`} description="Backend-calculated distance, cumulative mileage, driver time, and billable usage facts." />
            <ErrorAlert error={logs.error} />
            <div className="space-y-5">
                <UsageLogEditor agreementId={agreementId} allocations={agreement.data.vehicles} onSaved={appendLog} />
                {logs.loading ? <LoadingState /> : <DataTable rows={rows} rowKey={(row) => row.id} columns={[
                    { key: 'date', header: 'Date', render: (row) => row.usage_date },
                    { key: 'vehicle', header: 'Vehicle', render: (row) => row.vehicle?.registration_number ?? readableRelation(row.vehicle) },
                    { key: 'driver', header: 'Driver', render: (row) => readableRelation(row.driver) },
                    { key: 'time', header: 'ON / OFF', render: (row) => `${row.start_time ?? '-'} / ${row.end_time ?? '-'}` },
                    { key: 'km', header: 'Start / finish', render: (row) => `${row.start_odometer} / ${row.end_odometer}` },
                    { key: 'distance', header: 'Distance', render: (row) => row.distance_km },
                    { key: 'status', header: 'Status', render: (row) => <RentalStatusBadge status={row.status} /> },
                    { key: 'events', header: '', render: (row) => <button type="button" className="font-semibold text-sky-700 hover:underline" onClick={() => setSelectedId(row.id)}>Events ({row.events.length})</button> },
                ]} />}
                {selected && <>
                    <UsageEventEditor agreementId={agreementId} usageLogId={selected.id} onSaved={() => logs.reload()} />
                    <Panel title={`Events / ${selected.usage_date}`}>
                        <DataTable rows={selected.events} rowKey={(row) => row.id} columns={[
                            { key: 'type', header: 'Type', render: (row) => row.event_type.replaceAll('_', ' ') },
                            { key: 'quantity', header: 'Quantity', render: (row) => row.quantity },
                            { key: 'rate', header: 'Rate', render: (row) => row.rate_snapshot },
                            { key: 'amount', header: 'Amount', render: (row) => row.amount },
                            { key: 'remarks', header: 'Remarks', render: (row) => row.remarks ?? '-' },
                        ]} />
                    </Panel>
                </>}
            </div>
        </>
    );
}
