import { useEffect, useState } from 'react';
import { Link, useLocation, useParams } from 'react-router-dom';
import { Spinner } from '../components/ui/Spinner';
import type { PartyApi, PartyDetail } from './party.types';

export function PartyDetailPage({ api, basePath, noun }: { api: PartyApi; basePath: string; noun: string }) {
    const { id } = useParams();
    const location = useLocation();
    const stateParty = (location.state as { party?: PartyDetail } | null)?.party;
    const [party, setParty] = useState<PartyDetail | null>(stateParty?.id === Number(id) ? stateParty : null);
    const [error, setError] = useState('');

    useEffect(() => {
        if (party || !id) return;

        let active = true;
        void api.get(Number(id)).then((response) => {
            if (active) setParty(response);
        }).catch((requestError) => {
            if (active) setError(requestError instanceof Error ? requestError.message : `Unable to load this ${noun.toLowerCase()}.`);
        });

        return () => {
            active = false;
        };
    }, [api, id, noun, party]);

    if (!party && !error) return <div className="flex items-center justify-center p-16 text-sm font-semibold text-slate-500"><Spinner /><span className="ml-3">Loading {noun.toLowerCase()}</span></div>;
    if (!party) return <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>;

    return (
        <div className="mx-auto max-w-5xl space-y-5">
            <header className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div><p className="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">{party.code}</p><h1 className="mt-1 text-3xl font-bold text-slate-950">{party.name}</h1><p className="mt-1 text-sm text-slate-500">{party.displayName || `${noun} record`}</p></div>
                <div className="flex gap-2"><Link className="inline-flex h-10 items-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50" to={basePath}>Back</Link><Link className="inline-flex h-10 items-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700" to={`${basePath}/${party.id}/edit`}>Edit</Link></div>
            </header>

            <div className="grid gap-5 lg:grid-cols-3">
                <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
                    <h2 className="font-bold text-slate-950">Contact and identity</h2>
                    <dl className="mt-4 grid gap-4 sm:grid-cols-2"><Item label="Email" value={party.email} /><Item label="Phone" value={party.phone} /><Item label="Mobile" value={party.mobile} /><Item label="Status" value={party.status} /><Item label="Tax number" value={party.taxNumber} /><Item label="VAT number" value={party.vatNumber} /></dl>
                </section>
                <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 className="font-bold text-slate-950">Credit</h2>
                    <dl className="mt-4 space-y-4"><Item label="Credit limit" value={Number(party.creditLimit).toLocaleString(undefined, { maximumFractionDigits: 4 })} /><Item label="Current balance" value={party.currentCreditBalance ?? 'Not connected'} /><Item label="Available credit" value={party.availableCredit ?? 'Not connected'} /><Item label="Payment terms" value={`${party.paymentTermsDays} days`} /></dl>
                </section>
                <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
                    <h2 className="font-bold text-slate-950">Primary address</h2>
                    {party.address ? <p className="mt-4 leading-7 text-slate-600">{[party.address.addressLine1, party.address.addressLine2, party.address.city, party.address.stateProvince, party.address.postalCode, party.address.countryName].filter(Boolean).join(', ')}</p> : <p className="mt-4 text-sm text-slate-500">No primary address recorded.</p>}
                </section>
                <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><h2 className="font-bold text-slate-950">Notes</h2><p className="mt-4 whitespace-pre-wrap text-sm leading-6 text-slate-600">{party.notes || 'No notes.'}</p></section>
            </div>
        </div>
    );
}

function Item({ label, value }: { label: string; value?: string | number | null }) {
    return <div><dt className="text-xs font-bold uppercase tracking-wide text-slate-400">{label}</dt><dd className="mt-1 text-sm font-semibold text-slate-800">{value === null || value === undefined || value === '' ? 'Not provided' : value}</dd></div>;
}
