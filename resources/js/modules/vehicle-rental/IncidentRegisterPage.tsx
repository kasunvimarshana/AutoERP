import { useEffect, useState, type FormEvent } from 'react';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Pagination } from '@/shared/components/Pagination';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { Textarea } from '@/shared/components/Textarea';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import type { PaginationMeta } from '@/shared/types/pagination';
import { useApi } from '@/shared/hooks/useApi';
import { formatBusinessDate } from '@/shared/utils/businessDate';
import { CHART_LABELS } from './runningCharts';
import { IncidentReviewAction, IncidentStatus, IncidentType, INCIDENT_PERMISSION, INCIDENT_TYPE_LABELS, type RentalIncident } from './incidentEvidence';
import { listIncidentVehicleUseOptions, listIncidentRunningCharts, listRentalIncidents, recordRentalIncident, reviewRentalIncident } from './incidentEvidenceApi';
import { IncidentHistoryPanel } from './IncidentHistoryPanel';

export default function IncidentRegisterPage() {
    const auth = useAuth();
    const canRecord = hasPermission(auth, INCIDENT_PERMISSION.record);
    const canReview = hasPermission(auth, INCIDENT_PERMISSION.review);
    const [page, setPage] = useState(1);
    const [revision, setRevision] = useState(0);
    const [rows, setRows] = useState<RentalIncident[]>([]);
    const [meta, setMeta] = useState<PaginationMeta>();
    const [loading, setLoading] = useState(true);
    const [listFailed, setListFailed] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [busy, setBusy] = useState(false);
    const [vehicleSearch, setVehicleSearch] = useState('');
    const [query, setQuery] = useState('');
    const [vehiclePage, setVehiclePage] = useState(1);
    const [selectedUse, setSelectedUse] = useState('');
    const [chartPage, setChartPage] = useState(1);
    const [selectedChart, setSelectedChart] = useState('');
    const [type, setType] = useState<IncidentType | ''>('');
    const [occurredOn, setOccurredOn] = useState('');
    const [evidenceReference, setEvidenceReference] = useState('');
    const [description, setDescription] = useState('');
    const [reviewing, setReviewing] = useState<RentalIncident | null>(null);
    const [historyId, setHistoryId] = useState<number | null>(null);
    const [reviewReason, setReviewReason] = useState('');
    const vehicleUses = useApi(
        signal => listIncidentVehicleUseOptions(query, vehiclePage, signal),
        [query, vehiclePage],
        canRecord,
    );
    const runningCharts = useApi(
        signal => listIncidentRunningCharts(Number(selectedUse), chartPage, signal),
        [selectedUse, chartPage],
        canRecord && selectedUse !== '',
    );
    const uses = vehicleUses.data?.data ?? [];
    const useMeta = vehicleUses.data?.meta;
    const usesLoading = vehicleUses.loading;
    const charts = runningCharts.data?.data ?? [];
    const chartMeta = runningCharts.data?.meta;
    const chartsLoading = runningCharts.loading;

    useEffect(() => {
        const controller = new AbortController();
        listRentalIncidents(page, controller.signal)
            .then(result => {
                if (!controller.signal.aborted) { setRows(result.data); setMeta(result.meta); setListFailed(false); setError(null); }
            })
            .catch(failure => {
                if (!controller.signal.aborted) {
                    setRows([]); setMeta(undefined); setListFailed(true); setError(toApiError(failure));
                }
            })
            .finally(() => { if (!controller.signal.aborted) setLoading(false); });
        return () => controller.abort();
    }, [page, revision]);

    function reload() { setLoading(true); setRevision(value => value + 1); }
    async function create(event: FormEvent) {
        event.preventDefault();
        const selectedAssignment = uses.find(use => String(use.id) === selectedUse);
        if (busy || !selectedAssignment || !type) return;
        setBusy(true); setError(null);
        try {
            await recordRentalIncident({
                vehicle_use_id: selectedAssignment.id,
                expected_use_version: selectedAssignment.row_version,
                running_chart_id: selectedChart ? Number(selectedChart) : null,
                incident_type: type, occurred_on: occurredOn,
                evidence_reference: evidenceReference.trim(), description: description.trim(),
            });
            setSelectedUse(''); setSelectedChart(''); setChartPage(1); setType(''); setOccurredOn(''); setEvidenceReference(''); setDescription('');
            setPage(1); reload();
        } catch (failure) { setError(toApiError(failure)); }
        finally { setBusy(false); }
    }
    async function review(action: IncidentReviewAction) {
        if (busy || !reviewing) return;
        setBusy(true); setError(null);
        try {
            await reviewRentalIncident(reviewing, action, reviewReason);
            setReviewing(null); setReviewReason(''); setHistoryId(null); reload();
        } catch (failure) { setError(toApiError(failure)); }
        finally { setBusy(false); }
    }

    return <div className="space-y-5">
        <ContentHeader title="Rental incident evidence" description="Record fuel, toll, repair, damage and other rental evidence. Confirmation verifies the record only; it never creates an invoice, owner deduction or payment." />
        <ErrorAlert error={error ?? vehicleUses.error ?? runningCharts.error} inline />
        {canRecord && <Panel title="Record new incident">
            <form aria-label="Record rental incident" className="space-y-3" onSubmit={create}>
                <div className="flex flex-wrap items-end gap-2">
                    <Input label="Find a vehicle use (vehicle, agreement or party)" value={vehicleSearch} onChange={event => setVehicleSearch(event.target.value)} />
                    <Button type="button" variant="secondary" onClick={() => { setSelectedUse(''); setSelectedChart(''); setChartPage(1); setVehiclePage(1); setQuery(vehicleSearch.trim()); }}>Find assignments</Button>
                </div>
                <Select label="Vehicle assignment" required placeholder="Select a vehicle and customer agreement"
                    options={uses.map(use => ({ value: String(use.id), label: `${use.vehicle_label} — ${use.customer_agreement_reference ?? 'No agreement'} — ${use.customer_party_name ?? 'Unnamed party'}` }))}
                    value={selectedUse} disabled={usesLoading || busy} onChange={event => { setSelectedUse(event.target.value); setSelectedChart(''); setChartPage(1); }} error={error?.fields.vehicle_use_id?.[0]} />
                {usesLoading && <LoadingState label="Finding vehicle assignments…" />}
                <Pagination meta={useMeta} disabled={busy || usesLoading} onPageChange={next => { setSelectedUse(''); setSelectedChart(''); setChartPage(1); setVehiclePage(next); }} />
                {selectedUse && <>
                    <Select label="Running Chart (optional)" placeholder="No Running Chart linked"
                        options={charts.map(chart => ({
                            value: String(chart.id),
                            label: `${chart.reference} — ${CHART_LABELS[chart.status]}`,
                        }))}
                        value={selectedChart} disabled={chartsLoading || busy}
                        onChange={event => setSelectedChart(event.target.value)}
                        error={error?.fields.running_chart_id?.[0]} />
                    {chartsLoading && <LoadingState label="Finding Running Charts…" />}
                    <Pagination meta={chartMeta} disabled={busy || chartsLoading}
                        onPageChange={next => { setSelectedChart(''); setChartPage(next); }} />
                </>}
                <div className="grid gap-3 md:grid-cols-2">
                    <Select label="Incident category" required value={type}
                        options={Object.values(IncidentType).map(value => ({ value, label: INCIDENT_TYPE_LABELS[value] }))}
                        onChange={event => setType(event.target.value as IncidentType | '')} error={error?.fields.incident_type?.[0]} />
                    <Input label="Business date of incident" required type="date" value={occurredOn} onChange={event => setOccurredOn(event.target.value)} error={error?.fields.occurred_on?.[0]} />
                </div>
                <Input label="Evidence / document reference" required value={evidenceReference}
                    onChange={event => setEvidenceReference(event.target.value)} error={error?.fields.evidence_reference?.[0]} />
                <Textarea label="What happened? Record the factual evidence." required value={description}
                    onChange={event => setDescription(event.target.value)} error={error?.fields.description?.[0]} />
                <p className="text-sm text-slate-600">No party liability, tax amount, debit note, credit note or payable is inferred from this record. Financial adjustments need a separate authorized decision.</p>
                <Button type="submit" loading={busy} disabled={!selectedUse || !type || !occurredOn || !evidenceReference.trim() || !description.trim()}>Record evidence</Button>
            </form>
        </Panel>}
        <Panel title="Recorded incidents">
            {loading ? <LoadingState label="Loading rental incidents…" /> : listFailed ? null : rows.length === 0 ? <p>No incident evidence recorded.</p> : <div className="space-y-3">
                {rows.map(incident => <article key={incident.id} className="space-y-2 border-t pt-3">
                    <div className="flex flex-wrap items-center justify-between gap-2">
                        <h2 className="font-medium">{incident.vehicle.label} · {INCIDENT_TYPE_LABELS[incident.incident_type]}</h2>
                        <StatusBadge status={incident.status} />
                    </div>
                    <p className="text-sm">{formatBusinessDate(incident.occurred_on)} · {incident.reference}</p>
                    <p className="text-sm">Customer agreement: {incident.vehicle.customer_agreement ?? 'Not recorded'} · Owner agreement: {incident.vehicle.owner_agreement ?? 'Company supply'}</p>
                    <p className="text-sm">Evidence: {incident.evidence_reference}</p>
                    {incident.running_chart && <p className="text-sm">Running Chart: {incident.running_chart}</p>}
                    <p>{incident.description}</p>
                    {canReview && incident.status === IncidentStatus.Recorded &&
                        <Button type="button" variant="secondary" disabled={busy} onClick={() => { setReviewing(incident); setReviewReason(''); }}>Review evidence</Button>}
                    <Button type="button" variant="secondary" aria-expanded={historyId === incident.id}
                        onClick={() => setHistoryId(historyId === incident.id ? null : incident.id)}>
                        {historyId === incident.id ? 'Hide evidence history' : 'View evidence history'}
                    </Button>
                    {historyId === incident.id && <IncidentHistoryPanel key={incident.id} id={incident.id} />}
                    {reviewing?.id === incident.id && <div className="space-y-2 rounded-lg border bg-slate-50 p-3">
                        <Textarea label="Review reason (required)" required value={reviewReason} onChange={event => setReviewReason(event.target.value)} error={error?.fields.reason?.[0]} />
                        <p className="text-sm">Confirming factual evidence does not approve a customer charge or owner deduction.</p>
                        <div className="flex flex-wrap gap-2">
                            <Button type="button" disabled={busy || !reviewReason.trim()} onClick={() => void review(IncidentReviewAction.Confirm)}>Confirm evidence</Button>
                            <Button type="button" variant="danger" disabled={busy || !reviewReason.trim()} onClick={() => void review(IncidentReviewAction.Reject)}>Reject evidence</Button>
                            <Button type="button" variant="secondary" disabled={busy} onClick={() => setReviewing(null)}>Cancel</Button>
                        </div>
                    </div>}
                </article>)}
            </div>}
            <Pagination meta={meta} disabled={busy || loading} onPageChange={next => { setPage(next); setLoading(true); }} />
            <Button type="button" variant="secondary" disabled={busy || loading} onClick={reload}>Reload incidents</Button>
        </Panel>
    </div>;
}
