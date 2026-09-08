import { VehicleUsePanel } from './VehicleUsePanel';
import { USE_PERMISSION } from './vehicleUse';
import { AgreementHistoryPanel } from './AgreementHistoryPanel';
import { listAgreements, transitionAgreement } from './agreementApi';
import { useEffect, useState } from 'react';
import { useAuth } from '@/modules/auth/AuthProvider';
import { hasPermission } from '@/modules/auth/accessControl';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Pagination } from '@/shared/components/Pagination';
import type { PaginationMeta } from '@/shared/types/pagination';
import { AgreementEditor } from './AgreementEditor';
import { AgreementAction, AgreementKind, AgreementStatus, DriverMode, RentalBasis, TERM_LABELS, agreementPermissions, type Agreement, type TermKey } from './agreements';

export default function AgreementsPage({ kind }: { kind: AgreementKind }) {
    const auth = useAuth();
    const canManage = hasPermission(auth, agreementPermissions[kind].manage);
    const canViewUse = hasPermission(auth, USE_PERMISSION.view);
    const canManageUse = hasPermission(auth, USE_PERMISSION.manage);
    const [rows, setRows] = useState<Agreement[]>([]);
    const [meta, setMeta] = useState<PaginationMeta>();
    const [page, setPage] = useState(1);
    const [revision, setRevision] = useState(0);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<ApiError | null>(null);
    const [editing, setEditing] = useState<Agreement | 'new' | null>(null);
    const [selected, setSelected] = useState<Agreement | null>(null);
    const [action, setAction] = useState<AgreementAction | null>(null);
    const [reason, setReason] = useState('');
    const [saving, setSaving] = useState(false);
    const [showVehicles, setShowVehicles] = useState(false);
    const [showHistory, setShowHistory] = useState(false);
    useEffect(() => {
        const controller = new AbortController();
        listAgreements(kind, page, controller.signal).then(result => {
            if (!controller.signal.aborted) { setRows(result.data); setMeta(result.meta); setError(null); }
        }).catch(failure => { if (!controller.signal.aborted) setError(toApiError(failure)); })
            .finally(() => { if (!controller.signal.aborted) setLoading(false); });
        return () => controller.abort();
    }, [kind, page, revision]);
    function reload() { setSelected(null); setAction(null); setEditing(null); setLoading(true); setRevision(value => value + 1); }
    async function confirm() {
        if (!selected || !action) return;
        setSaving(true); setError(null);
        try { await transitionAgreement(kind, selected, action, reason); reload(); }
        catch (failure) { setError(toApiError(failure)); }
        finally { setSaving(false); }
    }
    return <div>
        <ContentHeader title={kind === AgreementKind.Customer ? 'Customer Rental Agreements' : 'Owner Rental Agreements'} description="Record agreed terms and preserve their history. Assign vehicles from active customer agreements and preserve custody history. Billing is not yet enabled."
            actions={<><Button variant="secondary" onClick={reload} disabled={saving || editing !== null}>Reload</Button>{canManage && <Button onClick={() => { setEditing('new'); setSelected(null); }} disabled={saving || editing !== null}>New agreement</Button>}</>} />
        <ErrorAlert error={error} inline />
        {editing !== null && <AgreementEditor key={editing === 'new' ? 'new' : editing.id} kind={kind} record={editing === 'new' ? undefined : editing} onSaved={reload} onCancel={() => setEditing(null)} />}
        {loading ? <p role="status">Loading agreements…</p> : <div className="mt-5 overflow-x-auto rounded-xl border border-slate-200 bg-white">
            <table className="w-full text-left text-sm"><caption className="sr-only">Rental agreements</caption><thead><tr>{['Reference', 'Party', 'Period', 'Basis', 'Status', 'Details'].map(label => <th className="p-3" key={label}>{label}</th>)}</tr></thead>
                <tbody>{rows.map(row => <tr key={row.id} className="border-t border-slate-100"><td className="p-3">{row.reference}</td><td className="p-3">{row.party.name}</td><td className="p-3">{row.starts_on} – {row.ends_on ?? 'Open-ended'}</td><td className="p-3">{row.basis === RentalBasis.Daily ? 'Daily' : 'Monthly'}</td><td className="p-3 capitalize">{row.status}</td><td className="p-3"><Button variant="secondary" disabled={saving || editing !== null} onClick={() => { setSelected(row); setAction(null); setReason(''); setShowHistory(false); setShowVehicles(false); }}>Review {row.reference}</Button></td></tr>)}</tbody>
            </table>{rows.length === 0 && <p className="p-5 text-slate-500">No agreements have been recorded.</p>}
        </div>}
        <Pagination meta={meta} onPageChange={value => { setLoading(true); setPage(value); setSelected(null); }} />
        {selected && <section className="mt-5 space-y-4 rounded-xl border border-slate-200 bg-white p-5" aria-label="Agreement review">
            <h2 className="text-xl font-semibold">{selected.reference} · {selected.party.name}</h2>
            <p>{selected.currency.code} · {selected.driver_mode === DriverMode.SelfDrive ? 'Self-drive' : 'With driver'}{selected.vehicle ? ` · ${selected.vehicle.registration_number ?? selected.vehicle.vehicle_number}` : ''}</p>
            <dl className="grid gap-3 sm:grid-cols-2">{(Object.keys(TERM_LABELS) as TermKey[]).map(key => <div key={key}><dt className="text-sm text-slate-500">{TERM_LABELS[key]}</dt><dd>{selected.terms[key] ?? 'Not specified'}</dd></div>)}</dl>
            {selected.notes && <p>{selected.notes}</p>}
            {kind === AgreementKind.Customer && canViewUse && <Button variant="secondary" onClick={() => setShowVehicles(value => !value)}>{showVehicles ? 'Hide vehicles' : 'View assigned vehicles'}</Button>}
            {kind === AgreementKind.Customer && canViewUse && showVehicles && <VehicleUsePanel key={selected.id} agreement={selected} canManage={canManageUse} />}
            <Button variant="secondary" onClick={() => setShowHistory(value => !value)}>{showHistory ? 'Hide history' : 'View history'}</Button>
            {showHistory && <AgreementHistoryPanel key={selected.id} kind={kind} id={selected.id} />}
            {canManage && !action && <div className="flex gap-2">
                {selected.status === AgreementStatus.Draft && <><Button variant="secondary" onClick={() => { setEditing(selected); setSelected(null); }}>Edit draft</Button><Button onClick={() => setAction(AgreementAction.Activate)}>Activate</Button></>}
                {selected.status === AgreementStatus.Active && <Button variant="secondary" onClick={() => setAction(AgreementAction.Close)}>Close agreement</Button>}
            </div>}
            {action && <div className="space-y-3 border-t pt-4">
                <p>{action === AgreementAction.Activate ? 'Activation freezes these terms. Confirm that the recorded details match the agreement. This does not reserve the vehicle or create a financial document.' : 'Close this agreement while preserving its original terms and history.'}</p>
                {action === AgreementAction.Close && <Input label="Closure reason" value={reason} onChange={event => setReason(event.target.value)} required disabled={saving} error={error?.fields.reason?.[0]} />}
                <Button onClick={confirm} loading={saving} disabled={action === AgreementAction.Close && !reason.trim()}>Confirm {action === AgreementAction.Activate ? 'activation' : 'closure'}</Button>
                <Button variant="secondary" disabled={saving} onClick={() => setAction(null)}>Cancel</Button>
            </div>}
        </section>}
    </div>;
}
