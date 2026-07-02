import { useCallback, useEffect, useRef, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { useConfirmDialog } from '@/shared/components/ConfirmDialog';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { Panel } from '@/shared/components/Panel';
import { Textarea } from '@/shared/components/Textarea';
import type { NamedResource } from '@/shared/types/common';
import { isPositiveDecimal } from '@/shared/utils/decimal';
import { createPurchaseReturn, getGoodsReceipt, getReturnableGoodsReceiptLines, type GoodsReceipt, type ReferencedPurchaseReturnPayload, type ReturnableLine } from '../purchaseApi';
import { decimalOr, todayDate } from '../purchaseFormUtils';
import { normalizeSourceId, sourceKey, sourceLineKey } from '../sourceIdentity';
import { useInitialSourceParam, type InitialSourceCommand, type InitialSourceParamDefinition } from '../hooks/useInitialSourceParam';
import { GoodsReceiptLookupSelect } from './PurchaseLookups';
import { PurchaseReturnLineEditor, type EditableReturnLine } from './PurchaseReturnLineEditor';

const initialSourceParams: Array<InitialSourceParamDefinition<'goods_receipt_note'>> = [
    { sourceType: 'goods_receipt_note', paramNames: ['goods_receipt_id', 'source_id'] },
];

export function PurchaseReturnForm() {
    const { confirm, confirmDialog } = useConfirmDialog();
    const navigate = useNavigate();
    const [searchParams, setSearchParams] = useSearchParams();
    const [source, setSourceState] = useState<NamedResource | null>(null);
    const [supplier, setSupplier] = useState<NamedResource | null>(null);
    const [warehouse, setWarehouse] = useState<NamedResource | null>(null);
    const [returnDate, setReturnDate] = useState(todayDate());
    const [reason, setReason] = useState('');
    const [lines, setLines] = useState<EditableReturnLine[]>([]);
    const [error, setError] = useState<ApiError | null>(null);
    const [sourceLoading, setSourceLoading] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [loadingKey, setLoadingKey] = useState<string | null>(null);
    const selectedKeyRef = useRef<string | null>(null);
    const loadingKeyRef = useRef<string | null>(null);
    const requestRef = useRef<{ key: string; controller: AbortController; generation: number } | null>(null);
    const generationRef = useRef(0);
    const mountedRef = useRef(true);
    const unmountCancelTimerRef = useRef<number | null>(null);
    const errorFor = (field: string) => fieldError(error, field);
    const hasEnteredLines = lines.some((line) => isPositiveDecimal(line.returned_quantity));

    const setSource = useCallback((next: NamedResource | null) => {
        selectedKeyRef.current = next?.id ? sourceKey('goods_receipt_note', next.id) : null;
        setSourceState(next);
    }, []);

    const cancelSourceRequest = useCallback((resetLoading = true) => {
        generationRef.current += 1;
        requestRef.current?.controller.abort();
        requestRef.current = null;
        loadingKeyRef.current = null;
        if (resetLoading && mountedRef.current) {
            setLoadingKey(null);
            setSourceLoading(false);
        }
    }, []);

    const clearSource = useCallback(() => {
        cancelSourceRequest();
        setSource(null);
        setSupplier(null);
        setWarehouse(null);
        setLines([]);
    }, [cancelSourceRequest, setSource]);

    const loadGoodsReceiptSource = useCallback(async (rawSourceId: number, _fallbackLabel: string): Promise<boolean> => {
        const sourceId = normalizeSourceId(rawSourceId);
        const key = sourceKey('goods_receipt_note', sourceId);
        if (sourceId === null || !key || loadingKeyRef.current === key) return false;

        cancelSourceRequest();

        const controller = new AbortController();
        const generation = generationRef.current;
        requestRef.current = { key, controller, generation };
        loadingKeyRef.current = key;
        setLoadingKey(key);
        setSourceLoading(true);
        setError(null);
        setSupplier(null);
        setWarehouse(null);
        setLines([]);

        try {
            const [grn, rows] = await Promise.all([
                getGoodsReceipt(sourceId, controller.signal),
                getReturnableGoodsReceiptLines(sourceId, controller.signal),
            ]);
            if (!mountedRef.current || isStaleSourceRequest(requestRef.current, key, controller, generation, generationRef.current)) return false;

            setSource(goodsReceiptLabel(grn));
            setSupplier(grn.supplier ?? null);
            setWarehouse(grn.warehouse ?? null);
            setLines(dedupeReturnLines(grn.id, rows.map((row) => editableReturnLine(row)).filter(isReturnLine)));

            return true;
        } catch (requestError) {
            if (!mountedRef.current || isStaleSourceRequest(requestRef.current, key, controller, generation, generationRef.current)) return false;
            setError(toApiError(requestError));
            setSource(null);
            setSupplier(null);
            setWarehouse(null);
            setLines([]);
            return false;
        } finally {
            if (requestRef.current?.controller === controller) {
                requestRef.current = null;
                loadingKeyRef.current = null;
                if (mountedRef.current) {
                    setLoadingKey(null);
                    setSourceLoading(false);
                }
            }
        }
    }, [cancelSourceRequest, setSource]);

    const processInitialSource = useCallback(async (command: InitialSourceCommand<'goods_receipt_note'>) => {
        await loadGoodsReceiptSource(command.sourceId, 'Selected goods receipt');
    }, [loadGoodsReceiptSource]);

    useInitialSourceParam({
        searchParams,
        setSearchParams,
        definitions: initialSourceParams,
        isUnavailable: (key) => selectedKeyRef.current === key || loadingKeyRef.current === key,
        onProcess: processInitialSource,
    });

    useEffect(() => {
        mountedRef.current = true;
        if (unmountCancelTimerRef.current !== null) {
            window.clearTimeout(unmountCancelTimerRef.current);
            unmountCancelTimerRef.current = null;
        }

        return () => {
            mountedRef.current = false;
            unmountCancelTimerRef.current = window.setTimeout(() => cancelSourceRequest(false), 0);
        };
    }, [cancelSourceRequest]);

    const payload = (): ReferencedPurchaseReturnPayload => ({
        return_date: returnDate,
        reason: reason || undefined,
        return_type: 'referenced',
        source_id: source?.id,
        lines: lines.filter((line) => line.include && isPositiveDecimal(line.returned_quantity)).map((line) => ({
            source_line_type: 'goods_receipt_note_line',
            source_line_id: line.source.id,
            returned_quantity: decimalOr(line.returned_quantity),
            reason: line.reason || undefined,
        })),
    });
    const changeSource = async (next: NamedResource | null) => {
        if (source?.id && next?.id !== source.id && hasEnteredLines && !await confirm({
            title: 'Change goods receipt?',
            message: 'Changing the goods receipt clears all entered return quantities.',
            confirmLabel: 'Change goods receipt',
        })) return;

        if (!next?.id) {
            clearSource();
            return;
        }

        setSource(next);
        setSupplier(null);
        setWarehouse(null);
        setLines([]);
        void loadGoodsReceiptSource(next.id, next.name);
    };

    const excludedGoodsReceiptIds = [
        ...(source?.id ? [source.id] : []),
        ...(loadingKey ? loadingKeyId(loadingKey, 'goods_receipt_note') : []),
    ];

    return (
        <form className="space-y-5" onSubmit={async (event) => {
            event.preventDefault();
            if (submitting || sourceLoading) return;
            setSubmitting(true);
            setError(null);
            try {
                const saved = await createPurchaseReturn(payload());
                navigate(`/purchase/returns/${saved.id}`);
            } catch (requestError) {
                setError(toApiError(requestError));
            } finally {
                setSubmitting(false);
            }
        }}>
            <ErrorAlert error={error} />
            <Panel title="Referenced source">
                <div className="grid gap-4 md:grid-cols-4">
                    <GoodsReceiptLookupSelect eligibility="returnable" value={source} onChange={(value) => void changeSource(value)} excludeIds={excludedGoodsReceiptIds} error={errorFor('source_id')} />
                    <Input label="Return date" type="date" value={returnDate} error={errorFor('return_date')} onChange={(event) => setReturnDate(event.target.value)} />
                    <div className="rounded-lg bg-slate-50 p-3 text-sm text-slate-700"><span className="font-medium">Warehouse</span><br />{warehouse?.name ?? '-'}</div>
                    <div className="rounded-lg bg-slate-50 p-3 text-sm text-slate-700"><span className="font-medium">Supplier</span><br />{supplier?.name ?? '-'}</div>
                </div>
            </Panel>
            <Panel title="Return lines">
                {sourceLoading ? <div className="text-sm text-slate-500">Loading source lines...</div> : <PurchaseReturnLineEditor lines={lines} onChange={setLines} errorFor={errorFor} />}
            </Panel>
            <Panel title="Reason">
                <Textarea label="Reason" value={reason} error={errorFor('reason')} onChange={(event) => setReason(event.target.value)} />
            </Panel>
            <div className="flex justify-end gap-2">
                <Button type="button" variant="secondary" onClick={() => navigate('/purchase/returns')}>Cancel</Button>
                <Button type="submit" loading={submitting} disabled={sourceLoading}>Save Draft</Button>
            </div>
            {confirmDialog}
        </form>
    );
}

function goodsReceiptLabel(grn: GoodsReceipt): NamedResource {
    return {
        id: grn.id,
        code: grn.grn_number,
        name: grn.grn_number ?? 'Goods receipt number unavailable',
    };
}

function editableReturnLine(row: ReturnableLine): EditableReturnLine | null {
    if (normalizeSourceId(row.id) === null) return null;

    return { source: row, include: false, returned_quantity: '0.000000', reason: '' };
}

function dedupeReturnLines(sourceId: number, lines: EditableReturnLine[]): EditableReturnLine[] {
    const seen = new Set<string>();

    return lines.filter((line) => {
        const key = sourceLineKey('goods_receipt_note', sourceId, line.source.id);
        if (!key || seen.has(key)) return false;
        seen.add(key);
        return true;
    });
}

function isReturnLine(line: EditableReturnLine | null): line is EditableReturnLine {
    return line !== null;
}

function loadingKeyId(key: string, type: string): number[] {
    const [sourceType, rawId] = key.split(':');
    const id = normalizeSourceId(rawId);
    return sourceType === type && id !== null ? [id] : [];
}

function isStaleSourceRequest(
    current: { key: string; controller: AbortController; generation: number } | null,
    key: string,
    controller: AbortController,
    generation: number,
    currentGeneration: number,
): boolean {
    return controller.signal.aborted
        || current?.key !== key
        || current.controller !== controller
        || current.generation !== generation
        || currentGeneration !== generation;
}
