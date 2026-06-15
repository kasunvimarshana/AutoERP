import { useEffect, useMemo, useState } from 'react';
import { useParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { ActionMenu } from '@/shared/components/ActionMenu';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable } from '@/shared/components/DataTable';
import { DetailGrid } from '@/shared/components/DetailGrid';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { useApi } from '@/shared/hooks/useApi';
import { useDebounce } from '@/shared/hooks/useDebounce';
import { businessDateInputValue } from '@/shared/utils/businessDate';
import { useAuth } from '@/modules/auth/AuthProvider';
import { RentalStatusBadge } from '../components/RentalStatusBadge';
import { UsageEventEditor } from '../components/UsageEventEditor';
import { UsageLogEditor } from '../components/UsageLogEditor';
import {
    changeRentalAgreementVehicleLinkStatus,
    changeRentalUsageStatus,
    createRentalAgreementVehicleLink,
    deleteRentalUsageEvent,
    deleteRentalUsageLog,
    getRunningChartContext,
    listRentalUsageLogs,
    listRunningChartAgreements,
} from '../vehicleRentalApi';
import type { RentalUsageLog, RunningChartAgreementOption } from '../vehicleRentalTypes';

const today = businessDateInputValue;
const optionKey = (option: RunningChartAgreementOption) => `${option.agreement_id}:${option.agreement_vehicle_id}`;

export default function UsageLogPage() {
    const routeAgreementId = Number(useParams().id) || null;
    const auth = useAuth();
    const [search, setSearch] = useState('');
    const debouncedSearch = useDebounce(search);
    const [page, setPage] = useState(1);
    const [restrictAgreementId, setRestrictAgreementId] = useState(routeAgreementId);
    const [selectedOption, setSelectedOption] = useState<RunningChartAgreementOption | null>(null);
    const [usageDate, setUsageDate] = useState(today());
    const [startTime, setStartTime] = useState('');
    const [selectedId, setSelectedId] = useState<number | null>(null);
    const [counterpartSearch, setCounterpartSearch] = useState('');
    const debouncedCounterpartSearch = useDebounce(counterpartSearch);
    const [counterpartPage, setCounterpartPage] = useState(1);
    const [counterpartKey, setCounterpartKey] = useState('');
    const [linkPeriod, setLinkPeriod] = useState({ effective_from: '', effective_to: '' });
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    const options = useApi(
        (signal) => listRunningChartAgreements({
            search: debouncedSearch || undefined,
            agreement_id: restrictAgreementId ?? undefined,
            page,
            per_page: 25,
        }, signal),
        [debouncedSearch, restrictAgreementId, page],
        true,
        true,
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
        true,
    );
    const logs = useApi(
        (signal) => listRentalUsageLogs(selectedOption!.agreement_id, signal),
        [selectedOption?.agreement_id],
        Boolean(selectedOption),
        true,
    );
    const counterpartDirection = selectedOption?.direction === 'outbound' ? 'inbound' : 'outbound';
    const counterparts = useApi(
        (signal) => listRunningChartAgreements({
            search: debouncedCounterpartSearch || undefined,
            vehicle_id: selectedOption?.vehicle_id,
            direction: counterpartDirection,
            page: counterpartPage,
            per_page: 25,
        }, signal),
        [debouncedCounterpartSearch, selectedOption?.vehicle_id, counterpartDirection, counterpartPage],
        Boolean(selectedOption && !context.data?.agreement_vehicle_link),
        true,
    );

    useEffect(() => {
        if (selectedOption || !options.data?.data.length) return;
        const initial = options.data.data.find((row) => row.agreement_id === routeAgreementId)
            ?? options.data.data[0];
        setSelectedOption(initial);
        setUsageDate(dateInsideOption(initial));
    }, [options.data, routeAgreementId, selectedOption]);

    useEffect(() => {
        setSelectedId(null);
        setCounterpartKey('');
        setCounterpartSearch('');
        setCounterpartPage(1);
        setLinkPeriod({ effective_from: '', effective_to: '' });
        setError(null);
    }, [selectedOption?.agreement_id, selectedOption?.agreement_vehicle_id, usageDate, startTime]);

    const optionRows = useMemo(() => {
        const rows = options.data?.data ?? [];
        if (!selectedOption || rows.some((row) => optionKey(row) === optionKey(selectedOption))) {
            return rows;
        }
        return [selectedOption, ...rows];
    }, [options.data, selectedOption]);
    const counterpartRows = counterparts.data?.data ?? [];
    const selectedCounterpart = counterpartRows.find((row) => optionKey(row) === counterpartKey) ?? null;
    const rows = logs.data ?? [];
    const selected = rows.find((row) => row.id === selectedId) ?? null;
    const openLink = context.data?.agreement_vehicle_link ?? null;
    const superAdmin = auth.user?.roles?.includes('Super Admin');
    const canManageLinks = superAdmin
        || auth.user?.permissions?.includes('vehicle-rental.links.manage');
    const canApproveLinks = superAdmin
        || auth.user?.permissions?.includes('vehicle-rental.links.approve');
    const canRecordUsage = superAdmin
        || auth.user?.permissions?.includes('vehicle-rental.usage.record');
    const canApproveUsage = superAdmin
        || auth.user?.permissions?.includes('vehicle-rental.usage.approve');

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

    const deleteUsage = async (row: RentalUsageLog) => {
        if (!selectedOption || busy) return;
        setBusy(true);
        setError(null);
        try {
            await deleteRentalUsageLog(selectedOption.agreement_id, row.id);
            setSelectedId(null);
            logs.reload();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setBusy(false);
        }
    };

    const deleteEvent = async (eventId: number) => {
        if (!selectedOption || !selected || busy) return;
        setBusy(true);
        setError(null);
        try {
            await deleteRentalUsageEvent(selectedOption.agreement_id, selected.id, eventId);
            logs.reload();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setBusy(false);
        }
    };

    const changeLinkStatus = async (status: 'submit' | 'approve') => {
        if (!openLink || busy) return;
        setBusy(true);
        setError(null);
        try {
            await changeRentalAgreementVehicleLinkStatus(openLink.id, status);
            context.reload();
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setBusy(false);
        }
    };

    return (
        <>
            <ContentHeader title="Running chart workspace" description="One physical entry resolves its applicable revenue, cost, or standalone agreement context." />
            <ErrorAlert error={error ?? options.error ?? context.error ?? logs.error ?? counterparts.error} />
            <div className="space-y-5">
                <Panel title="1. Select agreement, vehicle, and time">
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <Input
                            label="Search agreements, parties, or vehicles"
                            value={search}
                            onChange={(event) => {
                                setSearch(event.target.value);
                                setRestrictAgreementId(null);
                                setPage(1);
                            }}
                        />
                        <Select
                            label="Agreement and vehicle"
                            value={selectedOption ? optionKey(selectedOption) : ''}
                            options={optionRows.map((row) => ({
                                value: optionKey(row),
                                label: `${row.agreement_number} / ${row.direction} / ${row.party_name ?? row.party_type} / ${row.vehicle_registration ?? row.vehicle_id}`,
                            }))}
                            onChange={(event) => {
                                const next = optionRows.find((row) => optionKey(row) === event.target.value) ?? null;
                                setSelectedOption(next);
                                if (next) setUsageDate(dateInsideOption(next));
                                setStartTime('');
                            }}
                        />
                        <Input label="Usage date" type="date" value={usageDate} onChange={(event) => setUsageDate(event.target.value)} />
                        <Input label="ON time" type="time" value={startTime} onChange={(event) => setStartTime(event.target.value)} />
                    </div>
                    {options.loading && <LoadingState />}
                    <Pagination meta={options.data?.meta} onPageChange={setPage} />
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

                {selectedOption && openLink && (
                    <Panel title="Allocation link lifecycle">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p className="font-semibold">Link #{openLink.id}</p>
                                <p className="text-sm text-slate-600">
                                    {openLink.effective_from} to {openLink.effective_to}
                                </p>
                            </div>
                            <div className="flex items-center gap-2">
                                <RentalStatusBadge status={openLink.status} />
                                {openLink.status === 'draft' && canManageLinks && (
                                    <Button type="button" loading={busy} onClick={() => void changeLinkStatus('submit')}>
                                        Submit link
                                    </Button>
                                )}
                                {openLink.status === 'submitted' && canApproveLinks && (
                                    <Button type="button" loading={busy} onClick={() => void changeLinkStatus('approve')}>
                                        Approve link
                                    </Button>
                                )}
                            </div>
                        </div>
                    </Panel>
                )}

                {selectedOption && context.data && !openLink && canManageLinks && (
                    <Panel title="Link inbound and outbound allocations">
                        <form className="grid gap-4 md:grid-cols-2 xl:grid-cols-4" onSubmit={async (event) => {
                            event.preventDefault();
                            if (!selectedCounterpart) return;
                            setBusy(true);
                            setError(null);
                            try {
                                const inbound = selectedOption.direction === 'inbound' ? selectedOption : selectedCounterpart;
                                const outbound = selectedOption.direction === 'outbound' ? selectedOption : selectedCounterpart;
                                await createRentalAgreementVehicleLink({
                                    inbound_agreement_vehicle_id: inbound.agreement_vehicle_id,
                                    outbound_agreement_vehicle_id: outbound.agreement_vehicle_id,
                                    effective_from: linkPeriod.effective_from,
                                    effective_to: linkPeriod.effective_to,
                                });
                                context.reload();
                            } catch (requestError) {
                                setError(toApiError(requestError));
                            } finally {
                                setBusy(false);
                            }
                        }}>
                            <Input
                                label="Search counterpart"
                                value={counterpartSearch}
                                onChange={(event) => {
                                    setCounterpartSearch(event.target.value);
                                    setCounterpartPage(1);
                                    setCounterpartKey('');
                                }}
                            />
                            <Select
                                label="Counterpart agreement"
                                value={counterpartKey}
                                options={counterpartRows.map((row) => ({
                                    value: optionKey(row),
                                    label: `${row.agreement_number} / ${row.direction} / ${row.party_name ?? row.party_type}`,
                                }))}
                                onChange={(event) => {
                                    const counterpart = counterpartRows.find((row) => optionKey(row) === event.target.value);
                                    setCounterpartKey(event.target.value);
                                    if (counterpart) setLinkPeriod(overlapPeriod(selectedOption, counterpart));
                                }}
                            />
                            <Input label="Effective from" type="datetime-local" value={linkPeriod.effective_from} onChange={(event) => setLinkPeriod({ ...linkPeriod, effective_from: event.target.value })} />
                            <Input label="Effective to" type="datetime-local" value={linkPeriod.effective_to} onChange={(event) => setLinkPeriod({ ...linkPeriod, effective_to: event.target.value })} />
                            <div className="md:col-span-2 xl:col-span-4">
                                <Pagination meta={counterparts.data?.meta} onPageChange={setCounterpartPage} />
                            </div>
                            <div className="md:col-span-2 xl:col-span-4 flex justify-end">
                                <Button type="submit" loading={busy} disabled={!selectedCounterpart}>Create draft link</Button>
                            </div>
                        </form>
                    </Panel>
                )}

                {context.loading && selectedOption ? <LoadingState /> : context.data && (
                    <Panel title="2. Authoritative agreement context">
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

                {selectedOption && context.data && canRecordUsage && (
                    <UsageLogEditor
                        key={optionKey(selectedOption)}
                        agreementId={selectedOption.agreement_id}
                        agreementVehicleId={selectedOption.agreement_vehicle_id}
                        startOdometer={context.data.last_valid_finish_odometer}
                        initialUsageDate={usageDate}
                        initialStartTime={startTime}
                        onSaved={(log) => {
                            logs.reload();
                            setSelectedId(log.id);
                        }}
                    />
                )}

                {selectedOption && <Panel title="4. Review saved running charts">
                    {logs.loading ? <LoadingState /> : <DataTable rows={rows} rowKey={(row) => row.id} columns={[
                    { key: 'date', header: 'Date', render: (row) => row.usage_date },
                    { key: 'vehicle', header: 'Vehicle', render: (row) => row.vehicle?.registration_number ?? row.vehicle_id },
                    { key: 'time', header: 'ON / OFF', render: (row) => `${row.start_time ?? '-'} / ${row.end_time ?? '-'}` },
                    { key: 'km', header: 'Start / finish', render: (row) => `${row.start_odometer} / ${row.end_odometer}` },
                    { key: 'distance', header: 'Distance', render: (row) => row.distance_km },
                    { key: 'contexts', header: 'Financial contexts', render: (row) => row.contexts.map((item) => item.financial_side).join(' + ') },
                    { key: 'status', header: 'Status', render: (row) => <RentalStatusBadge status={row.status} /> },
                    { key: 'actions', header: '', render: (row) => <div className="flex flex-wrap justify-end gap-2">
                        {['draft', 'rejected'].includes(row.status) && canRecordUsage && <Button type="button" variant="secondary" loading={busy} onClick={() => setSelectedId(row.id)}>Events ({row.events.length})</Button>}
                        {row.status === 'draft' && canRecordUsage && <Button type="button" loading={busy} onClick={() => void changeStatus(row, 'submit')}>Submit</Button>}
                        {row.status === 'submitted' && canApproveUsage && <Button type="button" loading={busy} onClick={() => void changeStatus(row, 'approve')}>Approve</Button>}
                        {((['draft', 'rejected'].includes(row.status) && canRecordUsage) || (row.status === 'submitted' && canApproveUsage)) && (
                            <ActionMenu>
                                {['draft', 'rejected'].includes(row.status) && canRecordUsage && <Button className="w-full justify-start" type="button" variant="ghost" loading={busy} onClick={() => void deleteUsage(row)}>Delete draft</Button>}
                                {row.status === 'submitted' && canApproveUsage && <Button className="w-full justify-start text-rose-700" type="button" variant="ghost" loading={busy} onClick={() => void changeStatus(row, 'reject')}>Reject</Button>}
                            </ActionMenu>
                        )}
                    </div> },
                ]} />}
                </Panel>}

                {selectedOption && selected && ['draft', 'rejected'].includes(selected.status) && canRecordUsage && <>
                    <UsageEventEditor agreementId={selectedOption.agreement_id} usageLogId={selected.id} onSaved={() => logs.reload()} />
                    <Panel title={`5. Events and expenses / ${selected.usage_date}`}>
                        <DataTable rows={selected.events} rowKey={(row) => row.id} columns={[
                            { key: 'type', header: 'Type', render: (row) => row.event_type.replaceAll('_', ' ') },
                            { key: 'quantity', header: 'Quantity', render: (row) => row.quantity },
                            { key: 'remarks', header: 'Remarks', render: (row) => row.remarks ?? '-' },
                            { key: 'actions', header: '', render: (row) => <Button type="button" variant="danger" loading={busy} onClick={() => void deleteEvent(row.id)}>Delete</Button> },
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
