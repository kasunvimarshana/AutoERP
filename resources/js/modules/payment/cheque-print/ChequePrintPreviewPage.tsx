import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { useApi } from '@/shared/hooks/useApi';
import { listChequeTemplates, markChequePrinted, previewCheque, updateChequeTemplate } from './chequePrintApi';
import { ChequePreviewCanvas } from './ChequePreviewCanvas';
import { coordinateFields, type ChequeCoordinateKey, type ChequeTemplate } from './chequePrintTypes';

export default function ChequePrintPreviewPage() {
    const paymentId = Number(useParams().id);
    const templates = useApi((signal) => listChequeTemplates(true, signal), []);
    const [templateId, setTemplateId] = useState<number | null>(null);
    const preview = useApi(
        (signal) => previewCheque(paymentId, templateId ?? undefined, signal),
        [paymentId, templateId],
        templateId !== null,
    );
    const [adjustedTemplate, setAdjustedTemplate] = useState<ChequeTemplate | null>(null);
    const [notes, setNotes] = useState('');
    const [busy, setBusy] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const [message, setMessage] = useState('');

    useEffect(() => {
        const rows = templates.data?.data ?? [];
        if (templateId === null && rows.length > 0) {
            setTemplateId((rows.find((template) => template.is_default) ?? rows[0]).id);
        }
    }, [templateId, templates.data]);

    useEffect(() => {
        if (preview.data?.template) {
            setAdjustedTemplate(preview.data.template);
        }
    }, [preview.data]);

    function changeCoordinate(key: ChequeCoordinateKey, value: string) {
        setAdjustedTemplate((current) => current ? { ...current, [key]: value || null } : current);
    }

    async function saveAlignment() {
        if (!adjustedTemplate) return;
        setBusy(true);
        setActionError(null);
        setMessage('');
        try {
            await updateChequeTemplate(adjustedTemplate.id, Object.fromEntries(
                coordinateFields.flatMap(([x, y]) => [[x, adjustedTemplate[x]], [y, adjustedTemplate[y]]]),
            ));
            preview.reload();
            setMessage('Alignment saved.');
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setBusy(false);
        }
    }

    async function markPrinted() {
        if (!adjustedTemplate) return;
        setBusy(true);
        setActionError(null);
        setMessage('');
        try {
            const log = await markChequePrinted(paymentId, adjustedTemplate.id, notes);
            setMessage(`Marked as printed at ${new Date(log.printed_at).toLocaleString()}.`);
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setBusy(false);
        }
    }

    const options = (templates.data?.data ?? []).map((template) => ({
        value: String(template.id),
        label: `${template.bank_name ? `${template.bank_name} - ` : ''}${template.template_name}${template.is_default ? ' (default)' : ''}`,
    }));
    const activeTemplate = adjustedTemplate ?? preview.data?.template ?? null;

    return (
        <div className="cheque-print-workspace">
            <div className="no-print">
                <ContentHeader
                    title="Cheque print"
                    description={preview.data ? `${preview.data.payment.payment_number} / ${preview.data.payment.status}` : 'Select a bank template and verify alignment before printing.'}
                />
                <ErrorAlert error={actionError ?? templates.error ?? preview.error} />
                {message && <div className="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{message}</div>}
                <Panel>
                    <div className="grid gap-4 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto_auto] md:items-end">
                        <Select
                            label="Cheque template"
                            value={templateId ?? ''}
                            options={options}
                            onChange={(event) => setTemplateId(event.target.value ? Number(event.target.value) : null)}
                        />
                        <Input label="Print notes" value={notes} onChange={(event) => setNotes(event.target.value)} />
                        <Button variant="secondary" disabled={!preview.data} onClick={() => window.print()}>Print</Button>
                        <Button loading={busy} disabled={!preview.data} onClick={() => void markPrinted()}>Mark as printed</Button>
                    </div>
                </Panel>
            </div>

            {preview.loading || templates.loading
                ? <div className="no-print"><LoadingState /></div>
                : (templates.data?.data.length ?? 0) === 0
                    ? <div className="no-print mt-5 rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500">Create and activate a cheque template before previewing this payment.</div>
                : preview.data && activeTemplate && (
                    <>
                        <div className="cheque-print-stage mt-5 overflow-auto rounded-lg border border-slate-300 bg-slate-200 p-4 print:m-0 print:overflow-visible print:border-0 print:bg-white print:p-0">
                            <ChequePreviewCanvas payment={preview.data.payment} template={activeTemplate} />
                        </div>
                        <div className="no-print mt-5">
                            <Panel title="Test alignment">
                                <p className="mb-4 text-sm text-slate-500">Adjust positions in millimeters, test with browser print, then save the alignment to this bank template.</p>
                                <div className="space-y-4">
                                    {coordinateFields.map(([xKey, yKey, label]) => (
                                        <div key={label} className="grid gap-3 sm:grid-cols-[minmax(0,1fr)_160px_160px] sm:items-end">
                                            <div className="pb-2 text-sm font-semibold text-slate-800">{label}</div>
                                            <DecimalInput label="X (mm)" value={activeTemplate[xKey] ?? ''} onChange={(event) => changeCoordinate(xKey, event.target.value)} />
                                            <DecimalInput label="Y (mm)" value={activeTemplate[yKey] ?? ''} onChange={(event) => changeCoordinate(yKey, event.target.value)} />
                                        </div>
                                    ))}
                                </div>
                                <div className="mt-5 flex justify-end">
                                    <Button loading={busy} onClick={() => void saveAlignment()}>Save alignment</Button>
                                </div>
                            </Panel>
                        </div>
                    </>
                )}
        </div>
    );
}
