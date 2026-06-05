import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { Spinner } from '../components/ui/Spinner';
import { PartyForm } from './PartyForm';
import type { PartyApi, PartyDetail, PartyInput } from './party.types';

type PartyEditorPageProps = {
    api: PartyApi;
    basePath: string;
    codeField: 'customer_code' | 'supplier_code';
    mode: 'create' | 'edit';
    noun: string;
};

function toInput(party: PartyDetail): PartyInput {
    return {
        address: party.address,
        code: party.code,
        creditLimit: party.creditLimit,
        displayName: party.displayName,
        email: party.email,
        mobile: party.mobile,
        name: party.name,
        notes: party.notes,
        organizationUnitId: party.organizationUnitId,
        paymentTermsDays: party.paymentTermsDays,
        phone: party.phone,
        status: party.status,
        taxNumber: party.taxNumber,
        vatNumber: party.vatNumber,
    };
}

export function PartyEditorPage({ api, basePath, codeField, mode, noun }: PartyEditorPageProps) {
    const navigate = useNavigate();
    const { id } = useParams();
    const [party, setParty] = useState<PartyDetail | null>(null);
    const [error, setError] = useState('');
    const editing = mode === 'edit';

    useEffect(() => {
        if (!editing || !id) return;

        let active = true;
        void api.get(Number(id)).then((response) => {
            if (active) setParty(response);
        }).catch((requestError) => {
            if (active) setError(requestError instanceof Error ? requestError.message : `Unable to load this ${noun.toLowerCase()}.`);
        });

        return () => {
            active = false;
        };
    }, [api, editing, id, noun]);

    async function submit(input: PartyInput) {
        const saved = editing && id ? await api.update(Number(id), input) : await api.create(input);
        navigate(`${basePath}/${saved.id}`, { replace: true, state: { party: saved } });
    }

    if (editing && !party && !error) {
        return <div className="flex items-center justify-center p-16 text-sm font-semibold text-slate-500"><Spinner /><span className="ml-3">Loading {noun.toLowerCase()}</span></div>;
    }

    return (
        <div className="mx-auto max-w-5xl space-y-5">
            <header><p className="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">{editing ? 'Edit record' : 'New record'}</p><h1 className="mt-1 text-3xl font-bold text-slate-950">{editing ? `Edit ${noun.toLowerCase()}` : `Create ${noun.toLowerCase()}`}</h1></header>
            {error ? <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div> : null}
            {!error ? <PartyForm codeField={codeField} initialValue={party ? toInput(party) : undefined} noun={noun} onCancel={() => navigate(basePath)} onSubmit={submit} submitLabel={editing ? `Update ${noun.toLowerCase()}` : `Create ${noun.toLowerCase()}`} /> : null}
        </div>
    );
}
