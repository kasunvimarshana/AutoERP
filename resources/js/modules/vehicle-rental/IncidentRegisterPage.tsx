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
import { formatBusinessDate } from '@/shared/utils/businessDate';
import { listVehicleUseRegister } from './vehicleUseApi';
import type { VehicleUse } from './vehicleUse';
import { IncidentReviewAction, IncidentStatus, IncidentType, INCIDENT_PERMISSION, INCIDENT_TYPE_LABELS, type RentalIncident } from './incidentEvidence';
import { listRentalIncidents, recordRentalIncident, reviewRentalIncident } from './incidentEvidenceApi';

export default function IncidentRegisterPage() {
    const auth = useAuth();
    const canRecord = hasPermission(auth, INCIDENT_PERMISSION.record);
    const canReview = hasPermission(auth, INCIDENT_PERMISSION.review);
    const [page, setPage] = useState(1);
    const [revision, setRevision] = useState(0);
    const [rows, setRows] = useState<RentalIncident[]>([]);
    const [meta, setMeta] = useState<PaginationMeta>();
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<ApiError | null>(null);
    const [busy, setBusy] = useState(false);
    const [vehicleSearch, setVehicleSearch] = useState('');
    const [query, setQuery] = useState('');
    const [vehiclePage, setVehiclePage] = useState(1);
    const [uses, setUses] = useState<VehicleUse[]>([]);
    const [useMeta, setUseMeta] = useState<PaginationMeta>();
    const [selectedUse, setSelectedUse] = useState('');
    const [type, setType] = useState<IncidentType | ''>('');
    const [occurredOn, setOccurredOn] = useState('');
    const [evidenceReference, setEvidenceReference] = useState('');
    const [description, setDescription] = useState('');
    const [reviewing, setReviewing] = useState<RentalIncident | null>(null);
    const [reviewReason, setReviewReason] = useState('');

    useEffect(() => {
        const controller = new AbortController();
        listRentalIncidents(page, controller.signal)
            .then(result => {
                if (!controller.signal.aborted) { setRows(result.data); setMeta(result.meta); setError(null); }
            })
            .catch(failure => { if (!controller.signal.aborted) setError(toApiError(failure)); })
            .finally(() => { if (!controller.signal.aborted) setLoading(false); });
        return () => controller.abort();
    }, [page, revision]);

    useEffect(() => {
        if (!canRecord) return;
        const controller = new AbortController();
        listVehicleUseRegister({ search: query || undefined }, vehiclePage, controller.signal)
            .then(result => { if (!controller.signal.aborted) { setUses(result.data); setUseMeta(result.meta); } })
            .catch(failure => { if (!controller.signal.aborted) setError(toApiError(failure)); });
        return () => controller.abort();
    }, [canRecord, query, vehiclePage]);

    function reload() { setLoading(true); setRevision(value => value + 1); }
    async function create(event: FormEvent) {
        event.preventDefault();
        if (busy || !selectedUse || !type) return;
        setBusy(true); setError(null);
        try {
            await recordRentalIncident({
                vehicle_use_id: Number(selectedUse), incident_type: type, occurred_on: occurredOn,
                evidence_reference: evidenceReference.trim(), description: description.trim(),
            });
            setSelectedUse(''); setType(''); setOccurredOn(''); setEvidenceReference(''); setDescription('');
            setPage(1); reload();
        } catch (failure) { setError(toApiError(failure)); }
        finally { setBusy(false); }
    }
    async function review(action: IncidentReviewAction) {
        if (busy || !reviewing) return;
        setBusy(true); setError(null);
        try {
            await reviewRentalIncident(reviewing, action, reviewReason);
            setReviewing(null); setReviewReason(''); reload();
        } catch (failure) { setError(toApiError(failure)); }
        finally { setBusy(false); }
    }

    return <div className="space-y-5">
        <ContentHeader title="Rental incident evidence" description="Record fuel, toll, repair, damage and other rental evidence. Confirmation verifies the record only; it never creates an invoice, owner deduction or payment." />
        <ErrorAlert error={error} inline />
        {canRecord && <Panel title="Record new incident">
            <form aria-label="Record rental incident" className="space-y-3" onSubmit={create}>
                <div className="flex flex-wrap items-end gap-2">
                    <Input label="Find a vehicle use (vehicle, agreement or party)" value={vehicleSearch} onChange={event => setVehicleSearch(event.target.value)} />
                    <Button type="button" variant="secondary" onClick={() => { setSelectedUse(''); setVehiclePage(1); setQuery(vehicleSearch.trim()); }}>Find assignments</Button>
                </div>
                <Select label="Vehicle assignment" required placeholder="Select a vehicle and customer agreement"
                    options={uses.map(use => ({ value: String(use.id), label: `${use.vehicle.label} — ${use.customer_agreement.reference} — ${use.customer_agreement.party_name}` }))}
                    value={selectedUse} onChange={event => setSelectedUse(event.target.value)} error={error?.fields.vehicle_use_id?.[0]} />
                <Pagination meta={useMeta} disabled={busy} onPageChange={setVehiclePage} />
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
            {loading ? <LoadingState label="Loading rental incidents…" /> : rows.length === 0 ? <p>No incident evidence recorded.</p> : <div className="space-y-3">
                {rows.map(incident => <article key={incident.id} className="space-y-2 border-t pt-3">
                    <div className="flex flex-wrap items-center justify-between gap-2">
                        <h2 className="font-medium">{incident.vehicle.label} · {INCIDENT_TYPE_LABELS[incident.incident_type]}</h2>
                        <StatusBadge status={incident.status} />
                    </div>
                    <p className="text-sm">{formatBusinessDate(incident.occurred_on)} · {incident.reference}</p>
                    <p className="text-sm">Customer agreement: {incident.vehicle.customer_agreement ?? 'Not recorded'} · Owner agreement: {incident.vehicle.owner_agreement ?? 'Company supply'}</p>
                    <p className="text-sm">Evidence: {incident.evidence_reference}</p>
                    <p>{incident.description}</p>
                    {canReview && incident.status === IncidentStatus.Recorded &&
                        <Button type="button" variant="secondary" disabled={busy} onClick={() => { setReviewing(incident); setReviewReason(''); }}>Review evidence</Button>}
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
