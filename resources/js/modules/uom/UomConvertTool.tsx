import { useState } from 'react';
import { ApiError, fieldError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { convertUom } from './uomApi';
import { UomLookupSelect } from './UomLookupSelect';
import type { UomConvertResult, UomSummary } from './uomTypes';

export default function UomConvertTool() {
    const [fromUom, setFromUom] = useState<UomSummary | null>(null);
    const [toUom, setToUom] = useState<UomSummary | null>(null);
    const [quantity, setQuantity] = useState('1');
    const [result, setResult] = useState<UomConvertResult | null>(null);
    const [error, setError] = useState<ApiError | null>(null);
    const [loading, setLoading] = useState(false);

    return (
        <>
            <ContentHeader title="UOM Convert" description="Convert a quantity using backend UOM conversions." />
            <Panel>
                <ErrorAlert error={error} />
                <form className="space-y-4" onSubmit={async (event) => {
                    event.preventDefault();
                    setError(null);
                    setResult(null);
                    setLoading(true);
                    try {
                        if (!fromUom || !toUom) {
                            throw new ApiError('Select both UOMs.', 422);
                        }
                        if (Number(fromUom.id) === Number(toUom.id)) {
                            throw new ApiError('From UOM and To UOM cannot be the same.', 422);
                        }
                        setResult(await convertUom({
                            from_uom_id: Number(fromUom.id),
                            to_uom_id: Number(toUom.id),
                            quantity,
                        }));
                    } catch (nextError) {
                        setError(nextError instanceof ApiError ? nextError : new ApiError('Unable to convert quantity.', null));
                    } finally {
                        setLoading(false);
                    }
                }}>
                    <div className="grid gap-4 md:grid-cols-3">
                        <UomLookupSelect label="From UOM" value={fromUom} onChange={setFromUom} excludeId={toUom?.id ?? null} error={fieldError(error, 'from_uom_id')} />
                        <UomLookupSelect label="To UOM" value={toUom} onChange={setToUom} excludeId={fromUom?.id ?? null} error={fieldError(error, 'to_uom_id')} />
                        <Input label="Quantity" value={quantity} onChange={(event) => setQuantity(event.target.value)} error={fieldError(error, 'quantity')} required />
                    </div>
                    <div className="flex justify-end"><Button type="submit" loading={loading}>Convert</Button></div>
                </form>
                {result && (
                    <div className="mt-6 rounded-xl border border-sky-100 bg-sky-50 p-4 text-sm">
                        <p className="text-slate-600">Converted quantity</p>
                        <p className="mt-1 text-2xl font-bold text-slate-950">
                            {result.converted_quantity} {result.to_uom.code}
                        </p>
                        <p className="mt-2 text-slate-600">
                            {result.quantity} {result.from_uom.code} ({result.from_uom.name}) x {result.conversion_factor} = {result.converted_quantity} {result.to_uom.code} ({result.to_uom.name})
                        </p>
                    </div>
                )}
            </Panel>
        </>
    );
}
