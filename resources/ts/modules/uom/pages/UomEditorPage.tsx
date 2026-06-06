import { useEffect, useState } from 'react';
import { useLocation, useNavigate, useParams } from 'react-router-dom';
import { Spinner } from '../../../shared/components/ui/Spinner';
import { PageHeader } from '../../../shared/components/erp/ErpUi';
import { UomForm } from '../components/UomForm';
import { uomApi } from '../services/uomApi';
import type { Uom, UomInput } from '../types/uom.types';

function toInput(uom: Uom): UomInput {
    return {
        decimalPrecision: uom.decimalPrecision,
        isBase: uom.isBase,
        name: uom.name,
        notes: uom.notes,
        status: uom.status,
        symbol: uom.symbol,
        uomCode: uom.uomCode,
    };
}

export function UomEditorPage({ mode }: { mode: 'create' | 'edit' }) {
    const navigate = useNavigate();
    const location = useLocation();
    const { id } = useParams();
    const stateUom = (location.state as { uom?: Uom } | null)?.uom;
    const [uom, setUom] = useState<Uom | null>(stateUom?.id === Number(id) ? stateUom : null);
    const [error, setError] = useState('');
    const editing = mode === 'edit';

    useEffect(() => {
        if (!editing || !id || uom) return;
        let active = true;
        void uomApi.get(Number(id)).then((response) => {
            if (active) setUom(response);
        }).catch((requestError) => {
            if (active) setError(requestError instanceof Error ? requestError.message : 'Unable to load this unit.');
        });

        return () => {
            active = false;
        };
    }, [editing, id, uom]);

    async function submit(input: UomInput) {
        const saved = editing && id ? await uomApi.update(Number(id), input) : await uomApi.create(input);
        navigate(`/uoms/${saved.id}`, { replace: true, state: { uom: saved } });
    }

    if (editing && !uom && !error) return <div className="flex items-center justify-center p-16 text-sm font-semibold text-slate-500"><Spinner /><span className="ml-3">Loading unit</span></div>;

    return <div className="mx-auto max-w-4xl space-y-5"><PageHeader eyebrow={editing ? 'Edit record' : 'New record'} subtitle="Define the unit label, decimal precision, and availability." title={editing ? 'Edit unit of measure' : 'Create unit of measure'} />{error ? <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div> : null}{!error ? <UomForm initialValue={uom ? toInput(uom) : undefined} onCancel={() => navigate('/uoms')} onSubmit={submit} submitLabel={editing ? 'Update UOM' : 'Create UOM'} /> : null}</div>;
}
