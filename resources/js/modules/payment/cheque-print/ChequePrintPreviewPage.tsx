/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { useState } from 'react';
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
import {
    getChequeExportUrl,
    listChequeTemplates,
    markChequePrinted,
    previewCheque,
    updateChequeTemplate,
} from './chequePrintApi';
import { ChequePreviewCanvas } from './ChequePreviewCanvas';
import {
    coordinateFields,
    type ChequeCoordinateKey,
    type ChequeTemplate,
} from './chequePrintTypes';
import { formatBusinessDateTime } from '@/shared/utils/businessDate';

export default function ChequePrintPreviewPage() {
    const params = useParams();
    const paymentId = Number(params.paymentId);
    const lineId = Number(params.lineId);

    const templates = useApi((signal) => listChequeTemplates({ all: true, activeOnly: true }, signal), []);
    const [templateId, setTemplateId] = useState<number | null>(null);
    const templateRows = templates.data?.data ?? [];
    const defaultTemplateId = (templateRows.find((template) => template.is_default) ?? templateRows[0])?.id ?? null;
    const selectedTemplateId = templateId ?? defaultTemplateId;

    const preview = useApi(
        (signal) => previewCheque(paymentId, lineId, selectedTemplateId ?? undefined, signal),
        [paymentId, lineId, selectedTemplateId],
        selectedTemplateId !== null,
    );

    const [adjustedTemplate, setAdjustedTemplate] = useState<ChequeTemplate | null>(null);
    const [notes, setNotes] = useState('');
    const [busy, setBusy] = useState(false);
    const [actionError, setActionError] = useState<ApiError | null>(null);
    const [message, setMessage] = useState('');
    const [showGrid, setShowGrid] = useState(false);
    const [activeField, setActiveField] = useState<string | null>(null);

    const activeTemplate = adjustedTemplate?.id === preview.data?.template.id
        ? adjustedTemplate
        : preview.data?.template ?? null;

    function changeCoordinate(key: ChequeCoordinateKey, value: string) {
        if (!activeTemplate) return;
        setAdjustedTemplate({
            ...activeTemplate,
            [key]: value || null,
        } as ChequeTemplate);
    }

    async function saveAlignment() {
        if (!activeTemplate) return;
        setBusy(true);
        setActionError(null);
        setMessage('');
        try {
            const coordinatePayload = Object.fromEntries(
                coordinateFields.flatMap(([x, y]) => [[x, activeTemplate[x]], [y, activeTemplate[y]]]),
            );

            await updateChequeTemplate(activeTemplate.id, {
                ...coordinatePayload,
                expected_version: activeTemplate.row_version,
            });

            preview.reload();
            setMessage('Alignment successfully saved to template.');
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setBusy(false);
        }
    }

    async function markPrinted() {
        if (!activeTemplate) return;
        setBusy(true);
        setActionError(null);
        setMessage('');
        try {
            const log = await markChequePrinted(paymentId, lineId, activeTemplate.id, notes);
            setMessage(`Cheque successfully recorded as printed at ${formatBusinessDateTime(log.printed_at)}.`);
        } catch (error) {
            setActionError(toApiError(error));
        } finally {
            setBusy(false);
        }
    }

    const options = templateRows.map((template) => ({
        value: String(template.id),
        label: `${template.bank_name ? `${template.bank_name} - ` : ''}${template.template_name}${template.is_default ? ' (default)' : ''}`,
    }));

    const pdfUrl = activeTemplate
        ? getChequeExportUrl(paymentId, lineId, 'pdf', activeTemplate.id)
        : null;

    return (
        <div className="cheque-print-workspace">
            <div className="no-print space-y-4">
                <ContentHeader
                    title="Cheque preview & print"
                    description={
                        preview.data
                            ? `${preview.data.payment.payment_number} &bull; ${preview.data.payment.payee_name} &bull; ${preview.data.payment.formatted_amount ?? preview.data.payment.amount}`
                            : 'Select a bank template, verify alignment, and print or export.'
                    }
                />
                <ErrorAlert error={actionError ?? templates.error ?? preview.error} />
                {message && (
                    <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm font-medium text-emerald-800">
                        {message}
                    </div>
                )}

                <Panel>
                    <div className="grid gap-4 md:grid-cols-[minmax(0,1.2fr)_minmax(0,1.5fr)_auto_auto_auto] md:items-end">
                        <Select
                            label="Cheque template"
                            value={selectedTemplateId ?? ''}
                            options={options}
                            onChange={(event) => {
                                setTemplateId(event.target.value ? Number(event.target.value) : null);
                                setAdjustedTemplate(null);
                            }}
                        />
                        <Input
                            label="Audit notes"
                            placeholder="Optional print audit log notes..."
                            value={notes}
                            onChange={(event) => setNotes(event.target.value)}
                        />
                        {pdfUrl ? (
                            <a
                                href={pdfUrl}
                                target="_blank"
                                rel="noreferrer"
                                className="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition-colors"
                            >
                                Download PDF
                            </a>
                        ) : null}
                        <Button
                            variant="secondary"
                            disabled={!preview.data}
                            onClick={() => window.print()}
                        >
                            Print
                        </Button>
                        <Button
                            loading={busy}
                            disabled={!preview.data}
                            onClick={() => void markPrinted()}
                        >
                            Mark printed
                        </Button>
                    </div>
                </Panel>
            </div>

            {preview.loading || templates.loading ? (
                <div className="no-print mt-6"><LoadingState /></div>
            ) : templateRows.length === 0 ? (
                <div className="no-print mt-6 rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500">
                    No active cheque templates found. Please create and activate a template first.
                </div>
            ) : preview.data && activeTemplate && (
                <>
                    {/* Cheque Canvas Stage */}
                    <div className="cheque-print-stage mt-6 overflow-auto rounded-xl border border-slate-300 bg-slate-200 p-6 flex flex-col items-center print:m-0 print:overflow-visible print:border-0 print:bg-white print:p-0">
                        <div className="no-print mb-3 flex w-full max-w-4xl justify-between items-center text-xs text-slate-600">
                            <span className="font-medium">
                                Template: {activeTemplate.template_name} ({activeTemplate.page_width_mm} &times; {activeTemplate.page_height_mm} mm)
                            </span>
                            <label className="flex items-center gap-1.5 cursor-pointer font-medium select-none">
                                <input
                                    type="checkbox"
                                    checked={showGrid}
                                    onChange={(e) => setShowGrid(e.target.checked)}
                                />
                                10mm calibration grid
                            </label>
                        </div>

                        <ChequePreviewCanvas
                            payment={preview.data.payment}
                            template={activeTemplate}
                            showGrid={showGrid}
                            activeField={activeField}
                            onSelectField={(f) => setActiveField(f)}
                        />
                    </div>

                    {/* Calibration Adjustment Panel */}
                    <div className="no-print mt-6">
                        <Panel title="Printer Calibration & Alignment Nudge">
                            <p className="mb-4 text-xs text-slate-500">
                                Enter positions in millimeters from the top-left edge. Test print on plain paper, hold it against your bank cheque to check alignment, then save.
                            </p>
                            <div className="space-y-3">
                                {coordinateFields.map(([xKey, yKey, label]) => (
                                    <div
                                        key={label}
                                        className={`grid gap-3 sm:grid-cols-[minmax(0,1fr)_160px_160px] sm:items-end rounded-lg p-2 transition-colors ${
                                            activeField === label.toLowerCase().replace(/\s+/g, '_') ? 'bg-blue-50/60' : ''
                                        }`}
                                    >
                                        <div className="pb-2 text-sm font-semibold text-slate-800">
                                            {label}
                                        </div>
                                        <DecimalInput
                                            label="X (mm)"
                                            value={activeTemplate[xKey] ?? ''}
                                            onFocus={() => setActiveField(label.toLowerCase().replace(/\s+/g, '_'))}
                                            onChange={(event) => changeCoordinate(xKey, event.target.value)}
                                        />
                                        <DecimalInput
                                            label="Y (mm)"
                                            value={activeTemplate[yKey] ?? ''}
                                            onFocus={() => setActiveField(label.toLowerCase().replace(/\s+/g, '_'))}
                                            onChange={(event) => changeCoordinate(yKey, event.target.value)}
                                        />
                                    </div>
                                ))}
                            </div>
                            <div className="mt-5 flex justify-end gap-3 pt-4 border-t border-slate-100">
                                <Button
                                    variant="secondary"
                                    onClick={() => setAdjustedTemplate(null)}
                                    disabled={!adjustedTemplate}
                                >
                                    Reset to saved
                                </Button>
                                <Button
                                    loading={busy}
                                    disabled={!adjustedTemplate}
                                    onClick={() => void saveAlignment()}
                                >
                                    Save alignment to template
                                </Button>
                            </div>
                        </Panel>
                    </div>
                </>
            )}
        </div>
    );
}
