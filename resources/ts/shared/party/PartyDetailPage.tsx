import { useEffect, useState } from 'react';
import { Link, useLocation, useParams } from 'react-router-dom';
import { Spinner } from '../components/ui/Spinner';
import { CreditUtilization, FormSection, MoneyDisplay, PageHeader, SecondaryLink, StatusBadge } from '../components/erp/ErpUi';
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
            <PageHeader actions={<><SecondaryLink to={basePath}>Back</SecondaryLink><Link className="inline-flex h-10 items-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white shadow-sm shadow-blue-600/20 hover:bg-blue-700" to={`${basePath}/${party.id}/edit`}>Edit</Link></>} eyebrow={party.code} subtitle={party.displayName || `${noun} record`} title={party.name} />

            <div className="grid gap-5 lg:grid-cols-3">
                <div className="lg:col-span-2"><FormSection title="Contact and identity"><dl className="grid gap-4 sm:grid-cols-2"><Item label="Email" value={party.email} /><Item label="Phone" value={party.phone} /><Item label="Mobile" value={party.mobile} /><Item label="Status" value={<StatusBadge value={party.status} />} /><Item label="Tax number" value={party.taxNumber} /><Item label="VAT number" value={party.vatNumber} /></dl></FormSection></div>
                <FormSection title="Credit and terms"><div className="space-y-4"><CreditUtilization availableCredit={party.availableCredit} creditLimit={party.creditLimit} currentBalance={party.currentCreditBalance ?? 0} /><dl className="space-y-4"><Item label="Payment terms" value={`${party.paymentTermsDays} days`} /><Item label="Credit source" value={party.currentCreditBalance ? 'Connected balance' : 'Limit configured; live exposure not connected'} /></dl></div></FormSection>
                <div className="lg:col-span-2"><FormSection title="Primary address">{party.address ? <p className="leading-7 text-slate-600">{[party.address.addressLine1, party.address.addressLine2, party.address.city, party.address.stateProvince, party.address.postalCode, party.address.countryName].filter(Boolean).join(', ')}</p> : <p className="text-sm text-slate-500">No primary address recorded.</p>}</FormSection></div>
                <FormSection title="Notes"><p className="whitespace-pre-wrap text-sm leading-6 text-slate-600">{party.notes || 'No notes.'}</p></FormSection>
            </div>
        </div>
    );
}

function Item({ label, value }: { label: string; value?: React.ReactNode }) {
    return <div><dt className="text-xs font-bold uppercase tracking-wide text-slate-400">{label}</dt><dd className="mt-1 text-sm font-semibold text-slate-800">{value === null || value === undefined || value === '' ? 'Not provided' : value}</dd></div>;
}
