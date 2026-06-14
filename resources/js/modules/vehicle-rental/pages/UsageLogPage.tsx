import { useEffect, useMemo, useState } from 'react';
import { useParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable } from '@/shared/components/DataTable';
import { DetailGrid } from '@/shared/components/DetailGrid';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { useApi } from '@/shared/hooks/useApi';
import { RentalStatusBadge } from '../components/RentalStatusBadge';
import { UsageEventEditor } from '../components/UsageEventEditor';
import { UsageLogEditor } from '../components/UsageLogEditor';
import {
    changeRentalUsageStatus,
    createRentalAgreementVehicleLink,
    getRunningChartContext,
    listRentalUsageLogs,
    listRunningChartAgreements,
} from '../vehicleRentalApi';
import type { RentalUsageLog, RunningChartAgreementOption } from '../vehicleRentalTypes';

const today = () => new Date().toISOString().slice(0, 10);
const optionKey = (option: RunningChartAgreementOption) => `${option.agreement_id}:${option.agreement_vehicle_id}`;

export default function UsageLogPage() {
    const routeAgreementId = Number(useParams().id) || null;
    const options = useApi((signal) => listRunningChartAgreements('', signal), []);
    const [selectedKey, setSelectedKey] = useState('');
    const [usageDate, setUsageDate] = useState(today());
    const [startTime, setStartTime] = useState('');
    const [selectedId, setSelectedId] = useState<number | null>(null);
    const [counterpartKey, setCounterpartKey] = useState('');
    const [linkPeriod, setLinkPeriod] = useState({ effective_from: '', effective_to: '' });
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const selectedOption = useMemo(
        () => options.data?.find((row) => optionKey(row) === selectedKey) ?? null,
        [options.data, selectedKey],
    );
    const context = useApi(
        (signal) => getRunningChartContext({
            agreement_id: selectedOption!.agreement_id,
            agreement_vehicle_id: selectedOption!.agreement_vehicle_id,
            usage_date: usageDate,
            start_time: startTime || undefined,
        }, signal),
        [selectedOption?.agreement_id, selectedOption?.agreement_vehicle_id, usageDate, startTime],
        Boolean(selectedOption),
    );
    const logs = useApi(
        (signal) => listRentalUsageLogs(selectedOption!.agreement_id, signal),
        [selectedOption?.agreement_id],
        Boolean(selectedOption),
    );

    useEffect(() => {
        if (!options.data || selectedKey) return;
        const initial = options.data.find((row) => row.agreement_id === routeAgreementId) ?? options.data[0];
        if (!initial) return;
        setSelectedKey(optionKey(initial));
        setUsageDate(dateInsideOption(initial));
    }, [options.data, routeAgreementId, selectedKey]);

    useEffect(() => {
        setSelectedId(null);
        setCounterpartKey('');
        setLinkPeriod({ effective_from: '', effective_to: '' });
    }, [selectedKey]);

    const rows = logs.data ?? [];
    const selected = rows.find((row) => row.id === selectedId) ?? null;
    const counterpartOptions = (options.data ?? []).filter((row) =>
        selectedOption
        && row.vehicle_id === selectedOption.vehicle_id
        && row.direction !== selectedOption.direction
        && row.agreement_id !== selectedOption.agreement_id,
    );

    const changeStatus = async (row: RentalUsageLog, status: 'submit' | 'approve' | 'reject') => {
        if (!selectedOption || busy) return;
        setBusy(true);
        setError(null);
        try {
            await changeRentalUsageStatus(selectedOption.agreement_id, row.id, status);
            logs.reload();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setBusy(false);
        }
    };

    return (
        <>
            <ContentHeader title="Running chart workspace" description="One operational entry can resolve an outbound revenue context, an inbound cost context, or both." />
            <ErrorAlert error={error ?? options.error ?? context.error ?? logs.error} />
            <div className="space-y-5">
                <Panel title="Agreement context">
                    {options.loading ? <LoadingState /> : <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto]">
                        <Select
                            label="Agreement and vehicle"
                            value={selectedKey}
                            options={(options.data ?? []).map((row) => ({
                                value: optionKey(row),
                                label: `${row.agreement_number} / ${row.direction} / ${row.party_name ?? row.party_type} / ${row.vehicle_registration ?? row.vehicle_id}`,
                            }))}
                            onChange={(event) => {
                                const next = options.data?.find((row) => optionKey(row) === event.target.value);
                                setSelectedKey(event.target.value);
                                if (next) setUsageDate(dateInsideOption(next));
                                setStartTime('');
                            }}
                        />
                        {selectedOption && <div className="flex items-end pb-2 text-sm font-semibold text-slate-600">
                            {selectedOption.counterpart_agreement_number
                                ? `Linked to ${selectedOption.counterpart_agreement_number}`
                                : 'Single-side context'}
                        </div>}
                    </div>}
                    {selectedOption && <div className="mt-4">
                        <DetailGrid items={[
                            { label: 'Direction', value: selectedOption.direction },
                            { label: 'Party', value: selectedOption.party_name ?? selectedOption.party_type },
                            { label: 'Vehicle', value: selectedOption.vehicle_registration ?? selectedOption.vehicle_id },
                            { label: 'Rental type', value: selectedOption.rental_type.replaceAll('_', ' ') },
                            { label: 'Billing cycle', value: selectedOption.billing_cycle.replaceAll('_', ' ') },
                            { label: 'Status', value: <RentalStatusBadge status={selectedOption.status} /> },
                        ]} />
                    </div>}
                </Panel>

                {selectedOption && !selectedOption.counterpart_agreement_id && counterpartOptions.length > 0 && (
                    <Panel title="Link inbound and outbound allocations">
                        <form className="grid gap-4 md:grid-cols-2 xl:grid-cols-4" onSubmit={async (event) => {
                            event.preventDefault();
                            const counterpart = counterpartOptions.find((row) => optionKey(row) === counterpartKey);
                            if (!counterpart) return;
                            setBusy(true);
                            setError(null);
                            try {
                                const inbound = selectedOption.direction === 'inbound' ? selectedOption : counterpart;
                                const outbound = selectedOption.direction === 'outbound' ? selectedOption : counterpart;
                                await createRentalAgreementVehicleLink({
                                    inbound_agreement_vehicle_id: inbound.agreement_vehicle_id,
                                    outbound_agreement_vehicle_id: outbound.agreement_vehicle_id,
                                    effective_from: linkPeriod.effective_from,
                                    effective_to: linkPeriod.effective_to,
                                });
                                options.reload();
                                context.reload();
                            } catch (requestError) {
                                setError(toApiError(requestError));
                            } finally {
                                setBusy(false);
                            }
                        }}>
                            <Select label="Counterpart agreement" value={counterpartKey} options={counterpartOptions.map((row) => ({
                                value: optionKey(row),
                                label: `${row.agreement_number} / ${row.direction} / ${row.party_name ?? row.party_type}`,
                            }))} onChange={(event) => {
                                const counterpart = counterpartOptions.find((row) => optionKey(row) === event.target.value);
                                setCounterpartKey(event.target.value);
                                if (counterpart) {
                                    setLinkPeriod(overlapPeriod(selectedOption, counterpart));
                                }
                            }} />
                            <Input label="Effective from" type="datetime-local" value={linkPeriod.effective_from} onChange={(event) => setLinkPeriod({ ...linkPeriod, effective_from: event.target.value })} />
                            <Input label="Effective to" type="datetime-local" value={linkPeriod.effective_to} onChange={(event) => setLinkPeriod({ ...linkPeriod, effective_to: event.target.value })} />
                            <div className="flex items-end"><Button type="submit" loading={busy} disabled={!counterpartKey}>Create audited link</Button></div>
                        </form>
                    </Panel>
                )}

                {context.loading && selectedOption ? <LoadingState /> : context.data && (
                    <Panel title="Resolved financial contexts">
                        <div className="grid gap-4 lg:grid-cols-2">
                            {context.data.contexts.map((row) => (
                                <div key={row.agreement_id} className={`rounded-lg border p-4 ${row.financial_side === 'revenue' ? 'border-sky-200 bg-sky-50' : 'border-amber-200 bg-amber-50'}`}>
                                    <div className="flex items-center justify-between gap-3">
                                        <strong>{row.agreement_number}</strong>
                                        <RentalStatusBadge status={row.financial_side} />
                                    </div>
                                    <p className="mt-1 text-sm text-slate-600">{row.direction} / {row.party_name ?? row.party_type}</p>
                                    <DetailGrid items={[
                                        { label: 'Base', value: `${row.rate_snapshot.base_rate} / ${row.rate_snapshot.rate_unit}` },
                                        { label: 'Included KM', value: row.rate_snapshot.allowed_km },
                                        { label: 'Extra KM', value: row.rate_snapshot.extra_km_rate },
                                        { label: 'Overtime', value: row.rate_snapshot.overtime_rate },
                                        { label: 'Night out', value: row.rate_snapshot.night_out_rate },
                                    ]} />
                                </div>
                            ))}
                        </div>
                    </Panel>
                )}

                {selectedOption && context.data && (
                    <UsageLogEditor
                        key={selectedKey}
                        agreementId={selectedOption.agreement_id}
                        agreementVehicleId={selectedOption.agreement_vehicle_id}
                        startOdometer={context.data.last_valid_finish_odometer}
                        onContextChange={(date, time) => {
                            setUsageDate(date);
                            setStartTime(time);
                        }}
                        onSaved={(log) => {
                            logs.reload();
                            setSelectedId(log.id);
                        }}
                    />
                )}

                {selectedOption && (logs.loading ? <LoadingState /> : <DataTable rows={rows} rowKey={(row) => row.id} columns={[
                    { key: 'date', header: 'Date', render: (row) => row.usage_date },
                    { key: 'vehicle', header: 'Vehicle', render: (row) => row.vehicle?.registration_number ?? row.vehicle_id },
                    { key: 'time', header: 'ON / OFF', render: (row) => `${row.start_time ?? '-'} / ${row.end_time ?? '-'}` },
                    { key: 'km', header: 'Start / finish', render: (row) => `${row.start_odometer} / ${row.end_odometer}` },
                    { key: 'distance', header: 'Distance', render: (row) => row.distance_km },
                    { key: 'contexts', header: 'Financial contexts', render: (row) => row.contexts.map((item) => item.financial_side).join(' + ') },
                    { key: 'status', header: 'Status', render: (row) => <RentalStatusBadge status={row.status} /> },
                    { key: 'actions', header: '', render: (row) => <div className="flex flex-wrap gap-2">
                        {row.status === 'draft' && <Button type="button" variant="secondary" loading={busy} onClick={() => setSelectedId(row.id)}>Events ({row.events.length})</Button>}
                        {row.status === 'draft' && <Button type="button" loading={busy} onClick={() => changeStatus(row, 'submit')}>Submit</Button>}
                        {row.status === 'submitted' && <Button type="button" loading={busy} onClick={() => changeStatus(row, 'approve')}>Approve</Button>}
                        {row.status === 'submitted' && <Button type="button" variant="danger" loading={busy} onClick={() => changeStatus(row, 'reject')}>Reject</Button>}
                    </div> },
                ]} />)}

                {selectedOption && selected?.status === 'draft' && <>
                    <UsageEventEditor agreementId={selectedOption.agreement_id} usageLogId={selected.id} onSaved={() => logs.reload()} />
                    <Panel title={`Operational events / ${selected.usage_date}`}>
                        <DataTable rows={selected.events} rowKey={(row) => row.id} columns={[
                            { key: 'type', header: 'Type', render: (row) => row.event_type.replaceAll('_', ' ') },
                            { key: 'quantity', header: 'Quantity', render: (row) => row.quantity },
                            { key: 'remarks', header: 'Remarks', render: (row) => row.remarks ?? '-' },
                        ]} />
                    </Panel>
                </>}
            </div>
        </>
    );
}

function dateInsideOption(option: RunningChartAgreementOption): string {
    const current = today();
    const from = option.allocation_from.slice(0, 10);
    const to = (option.allocation_to ?? option.expected_end_at).slice(0, 10);
    return current >= from && current <= to ? current : from;
}

function overlapPeriod(left: RunningChartAgreementOption, right: RunningChartAgreementOption) {
    const from = [left.allocation_from, right.allocation_from].sort().at(-1)!.slice(0, 16);
    const leftTo = left.allocation_to ?? left.expected_end_at;
    const rightTo = right.allocation_to ?? right.expected_end_at;
    const to = [leftTo, rightTo].sort()[0].slice(0, 16);
    return { effective_from: from, effective_to: to };
}
