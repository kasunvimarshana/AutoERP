import { useEffect, useState } from 'react';
import { Link, useLocation, useParams } from 'react-router-dom';
import { Spinner } from '../../../shared/components/ui/Spinner';
import { uomApi } from '../services/uomApi';
import type { Uom } from '../types/uom.types';

export function UomDetailPage() {
    const { id } = useParams();
    const location = useLocation();
    const stateUom = (location.state as { uom?: Uom } | null)?.uom;
    const [uom, setUom] = useState<Uom | null>(stateUom?.id === Number(id) ? stateUom : null);
    const [error, setError] = useState('');

    useEffect(() => {
        if (uom || !id) return;
        let active = true;
        void uomApi.get(Number(id)).then((response) => {
            if (active) setUom(response);
        }).catch((requestError) => {
            if (active) setError(requestError instanceof Error ? requestError.message : 'Unable to load this unit.');
        });

        return () => {
            active = false;
        };
    }, [id, uom]);

    if (!uom && !error) return <div className="flex items-center justify-center p-16 text-sm font-semibold text-slate-500"><Spinner /><span className="ml-3">Loading unit</span></div>;
    if (!uom) return <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>;

    return <div className="mx-auto max-w-4xl space-y-5"><header className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><p className="text-xs font-bold uppercase tracking-[0.18em] text-blue-600">{uom.uomCode}</p><h1 className="mt-1 text-3xl font-bold text-slate-950">{uom.name}</h1><p className="mt-1 text-sm text-slate-500">{uom.symbol || 'No symbol'}</p></div><div className="flex gap-2"><Link className="inline-flex h-10 items-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50" to="/uoms">Back</Link><Link className="inline-flex h-10 items-center rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700" state={{ uom }} to={`/uoms/${uom.id}/edit`}>Edit</Link></div></header><div className="grid gap-5 md:grid-cols-2"><section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><h2 className="font-bold text-slate-950">Configuration</h2><dl className="mt-4 grid gap-4 sm:grid-cols-2"><Item label="Status" value={uom.status} /><Item label="Decimal precision" value={uom.decimalPrecision} /><Item label="Base unit" value={uom.isBase ? 'Yes' : 'No'} /><Item label="Organization unit" value={uom.organizationUnitId} /></dl></section><section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><h2 className="font-bold text-slate-950">Notes</h2><p className="mt-4 whitespace-pre-wrap text-sm leading-6 text-slate-600">{uom.notes || 'No notes.'}</p></section></div></div>;
}

function Item({ label, value }: { label: string; value?: string | number | null }) {
    return <div><dt className="text-xs font-bold uppercase tracking-wide text-slate-400">{label}</dt><dd className="mt-1 text-sm font-semibold text-slate-800">{value === null || value === undefined || value === '' ? 'Not provided' : value}</dd></div>;
}
