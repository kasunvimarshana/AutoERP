/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DecimalInput } from '@/shared/components/DecimalInput';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { createChequeTemplate, getChequeTemplate, updateChequeTemplate } from './chequePrintApi';
import { ChequePreviewCanvas } from './ChequePreviewCanvas';
import type { ChequePrintPayment, ChequeTemplate, ChequeTemplatePayload } from './chequePrintTypes';

const initialTemplate: ChequeTemplatePayload = {
    bank_name: '',
    template_name: '',
    page_width_mm: '210',
    page_height_mm: '99',
    date_x_mm: '155',
    date_y_mm: '12',
    payee_x_mm: '25',
    payee_y_mm: '34',
    amount_x_mm: '160',
    amount_y_mm: '45',
    amount_words_x_mm: '25',
    amount_words_y_mm: '55',
    cheque_number_x_mm: '15',
    cheque_number_y_mm: '12',
    font_size: '12',
    font_family: 'Arial',
    is_default: false,
    is_active: true,
    metadata: {
        date_format: 'd/m/Y',
        date_letter_spacing: '',
        crossing_type: 'ac_payee',
        crossing_x_mm: '15',
        crossing_y_mm: '10',
        bearer_strike_through: true,
        bearer_x_mm: '155',
        bearer_y_mm: '36',
        amount_words_line_2_x_mm: '25',
        amount_words_line_2_y_mm: '62',
        amount_prefix: '**',
        amount_suffix: '**',
        currency_code: 'LKR',
        memo_x_mm: '25',
        memo_y_mm: '75',
    },
};

const samplePayment: ChequePrintPayment = {
    id: 1,
    payment_number: 'PAY-2026-0042',
    payment_method: 'cheque',
    payee_name: 'Acme Logistics Ltd.',
    amount: '125450.00',
    formatted_amount: '**125,450.00**',
    amount_in_words: 'One Hundred Twenty Five Thousand Four Hundred Fifty Rupees Only',
    amount_words_line_1: 'One Hundred Twenty Five Thousand Four Hundred',
    amount_words_line_2: 'Fifty Rupees Only',
    cheque_number: '004128',
    cheque_date: '2026-11-27',
    formatted_cheque_date: '27/11/2026',
    date_digits: ['2', '7', '1', '1', '2', '0', '2', '6'],
    currency_code: 'LKR',
    crossing_type: 'ac_payee',
    crossing_text: 'A/C PAYEE ONLY',
    show_bearer_strike: true,
    memo_text: 'Invoice settlement INV-00912',
    status: 'approved',
};

export default function ChequeTemplateFormPage() {
    const templateId = Number(useParams().id);
    const editing = Number.isInteger(templateId) && templateId > 0;
    const navigate = useNavigate();

    const [form, setForm] = useState<ChequeTemplatePayload>(initialTemplate);
    const [rowVersion, setRowVersion] = useState<number | undefined>(undefined);
    const [loading, setLoading] = useState(editing);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [activeField, setActiveField] = useState<string | null>(null);
    const [showGrid, setShowGrid] = useState(false);

    useEffect(() => {
        if (!editing) return;
        const controller = new AbortController();
        getChequeTemplate(templateId, controller.signal)
            .then((template) => {
                if (controller.signal.aborted) return;
                setRowVersion(template.row_version);
                const meta = template.metadata ?? {};
                setForm({
                    bank_name: template.bank_name ?? '',
                    template_name: template.template_name,
                    page_width_mm: String(template.page_width_mm),
                    page_height_mm: String(template.page_height_mm),
                    date_x_mm: String(template.date_x_mm),
                    date_y_mm: String(template.date_y_mm),
                    payee_x_mm: String(template.payee_x_mm),
                    payee_y_mm: String(template.payee_y_mm),
                    amount_x_mm: String(template.amount_x_mm),
                    amount_y_mm: String(template.amount_y_mm),
                    amount_words_x_mm: String(template.amount_words_x_mm),
                    amount_words_y_mm: String(template.amount_words_y_mm),
                    cheque_number_x_mm: template.cheque_number_x_mm != null ? String(template.cheque_number_x_mm) : '',
                    cheque_number_y_mm: template.cheque_number_y_mm != null ? String(template.cheque_number_y_mm) : '',
                    font_size: String(template.font_size),
                    font_family: template.font_family ?? 'Arial',
                    is_default: template.is_default,
                    is_active: template.is_active,
                    metadata: {
                        date_format: meta.date_format ?? 'd/m/Y',
                        date_letter_spacing: meta.date_letter_spacing != null ? String(meta.date_letter_spacing) : '',
                        crossing_type: (meta.crossing_type as 'none' | 'ac_payee' | 'not_negotiable') ?? 'ac_payee',
                        crossing_x_mm: meta.crossing_x_mm != null ? String(meta.crossing_x_mm) : '15',
                        crossing_y_mm: meta.crossing_y_mm != null ? String(meta.crossing_y_mm) : '10',
                        bearer_strike_through: meta.bearer_strike_through ?? true,
                        bearer_x_mm: meta.bearer_x_mm != null ? String(meta.bearer_x_mm) : '',
                        bearer_y_mm: meta.bearer_y_mm != null ? String(meta.bearer_y_mm) : '',
                        amount_words_line_2_x_mm: meta.amount_words_line_2_x_mm != null ? String(meta.amount_words_line_2_x_mm) : '',
                        amount_words_line_2_y_mm: meta.amount_words_line_2_y_mm != null ? String(meta.amount_words_line_2_y_mm) : '',
                        amount_prefix: meta.amount_prefix ?? '**',
                        amount_suffix: meta.amount_suffix ?? '**',
                        currency_code: meta.currency_code ?? 'LKR',
                        memo_x_mm: meta.memo_x_mm != null ? String(meta.memo_x_mm) : '',
                        memo_y_mm: meta.memo_y_mm != null ? String(meta.memo_y_mm) : '',
                    },
                });
            })
            .catch((requestError) => !controller.signal.aborted && setError(toApiError(requestError)))
            .finally(() => !controller.signal.aborted && setLoading(false));
        return () => controller.abort();
    }, [editing, templateId]);

    function change<K extends keyof ChequeTemplatePayload>(key: K, value: ChequeTemplatePayload[K]) {
        setForm((current) => ({ ...current, [key]: value }));
    }

    function changeMeta(key: string, value: unknown) {
        setForm((current) => ({
            ...current,
            metadata: {
                ...current.metadata,
                [key]: value,
            },
        }));
    }

    async function save() {
        setSaving(true);
        setError(null);
        try {
            const meta = form.metadata ?? {};
            const cleanMeta = {
                ...meta,
                date_letter_spacing: meta.date_letter_spacing ? Number(meta.date_letter_spacing) : null,
                crossing_x_mm: meta.crossing_x_mm ? Number(meta.crossing_x_mm) : null,
                crossing_y_mm: meta.crossing_y_mm ? Number(meta.crossing_y_mm) : null,
                bearer_x_mm: meta.bearer_x_mm ? Number(meta.bearer_x_mm) : null,
                bearer_y_mm: meta.bearer_y_mm ? Number(meta.bearer_y_mm) : null,
                amount_words_line_2_x_mm: meta.amount_words_line_2_x_mm ? Number(meta.amount_words_line_2_x_mm) : null,
                amount_words_line_2_y_mm: meta.amount_words_line_2_y_mm ? Number(meta.amount_words_line_2_y_mm) : null,
                memo_x_mm: meta.memo_x_mm ? Number(meta.memo_x_mm) : null,
                memo_y_mm: meta.memo_y_mm ? Number(meta.memo_y_mm) : null,
            };

            const payload: ChequeTemplatePayload = {
                ...form,
                bank_name: form.bank_name || null,
                font_family: form.font_family || 'Arial',
                cheque_number_x_mm: form.cheque_number_x_mm || null,
                cheque_number_y_mm: form.cheque_number_y_mm || null,
                metadata: cleanMeta,
                expected_version: editing ? rowVersion : undefined,
            };

            const saved = editing
                ? await updateChequeTemplate(templateId, payload)
                : await createChequeTemplate(payload);

            setRowVersion(saved.row_version);
            navigate(`/payments/cheque-templates/${saved.id}/edit`, { replace: true });
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    }

    if (loading) return <LoadingState />;

    // Build temporary template for live canvas
    const previewTemplate: ChequeTemplate = {
        id: templateId || 0,
        row_version: rowVersion ?? 1,
        tenant_id: 1,
        bank_name: form.bank_name,
        template_name: form.template_name || 'Preview Template',
        page_width_mm: form.page_width_mm || '210',
        page_height_mm: form.page_height_mm || '99',
        date_x_mm: form.date_x_mm || '0',
        date_y_mm: form.date_y_mm || '0',
        payee_x_mm: form.payee_x_mm || '0',
        payee_y_mm: form.payee_y_mm || '0',
        amount_x_mm: form.amount_x_mm || '0',
        amount_y_mm: form.amount_y_mm || '0',
        amount_words_x_mm: form.amount_words_x_mm || '0',
        amount_words_y_mm: form.amount_words_y_mm || '0',
        cheque_number_x_mm: form.cheque_number_x_mm || null,
        cheque_number_y_mm: form.cheque_number_y_mm || null,
        font_size: form.font_size || '12',
        font_family: form.font_family || 'Arial',
        is_default: form.is_default,
        is_active: form.is_active,
        metadata: form.metadata,
    };

    return (
        <>
            <ContentHeader
                title={editing ? `Edit cheque template: ${form.template_name}` : 'New cheque template'}
                description="Measure your bank cheque layout in millimeters from the top-left corner and calibrate positioning."
            />
            <ErrorAlert error={error} />

            <form
                className="space-y-6"
                onSubmit={(event) => {
                    event.preventDefault();
                    void save();
                }}
            >
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Left Column: Form Controls */}
                    <div className="space-y-5">
                        <Panel title="Bank & Dimensions">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Input
                                    label="Template name"
                                    required
                                    value={form.template_name}
                                    error={fieldError(error, 'template_name')}
                                    onChange={(e) => change('template_name', e.target.value)}
                                />
                                <Input
                                    label="Bank name"
                                    value={form.bank_name ?? ''}
                                    error={fieldError(error, 'bank_name')}
                                    placeholder="e.g. Commercial Bank, HSBC"
                                    onChange={(e) => change('bank_name', e.target.value)}
                                />
                                <DecimalInput
                                    label="Cheque width (mm)"
                                    required
                                    value={form.page_width_mm}
                                    error={fieldError(error, 'page_width_mm')}
                                    onChange={(e) => change('page_width_mm', e.target.value)}
                                />
                                <DecimalInput
                                    label="Cheque height (mm)"
                                    required
                                    value={form.page_height_mm}
                                    error={fieldError(error, 'page_height_mm')}
                                    onChange={(e) => change('page_height_mm', e.target.value)}
                                />
                                <DecimalInput
                                    label="Font size (pt)"
                                    required
                                    value={form.font_size}
                                    error={fieldError(error, 'font_size')}
                                    onChange={(e) => change('font_size', e.target.value)}
                                />
                                <Input
                                    label="Font family"
                                    value={form.font_family ?? ''}
                                    error={fieldError(error, 'font_family')}
                                    placeholder="Arial, Courier, Helvetica"
                                    onChange={(e) => change('font_family', e.target.value)}
                                />
                            </div>
                            <div className="mt-4 flex flex-wrap gap-6 pt-2 border-t border-slate-100">
                                <label className="flex items-center gap-2 text-sm font-medium text-slate-700 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={form.is_default}
                                        onChange={(e) => change('is_default', e.target.checked)}
                                    />
                                    Default cheque template
                                </label>
                                <label className="flex items-center gap-2 text-sm font-medium text-slate-700 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={form.is_active}
                                        onChange={(e) => change('is_active', e.target.checked)}
                                    />
                                    Active
                                </label>
                            </div>
                        </Panel>

                        <Panel title="Field Alignments (Top-Left Origin)">
                            <div className="space-y-3">
                                {/* Date */}
                                <div className="grid grid-cols-[130px_1fr_1fr] items-center gap-2">
                                    <span className="text-sm font-medium text-slate-700">Date</span>
                                    <DecimalInput
                                        placeholder="X (mm)"
                                        value={form.date_x_mm}
                                        error={fieldError(error, 'date_x_mm')}
                                        onFocus={() => setActiveField('date')}
                                        onChange={(e) => change('date_x_mm', e.target.value)}
                                    />
                                    <DecimalInput
                                        placeholder="Y (mm)"
                                        value={form.date_y_mm}
                                        error={fieldError(error, 'date_y_mm')}
                                        onFocus={() => setActiveField('date')}
                                        onChange={(e) => change('date_y_mm', e.target.value)}
                                    />
                                </div>

                                {/* Payee */}
                                <div className="grid grid-cols-[130px_1fr_1fr] items-center gap-2">
                                    <span className="text-sm font-medium text-slate-700">Payee</span>
                                    <DecimalInput
                                        placeholder="X (mm)"
                                        value={form.payee_x_mm}
                                        error={fieldError(error, 'payee_x_mm')}
                                        onFocus={() => setActiveField('payee')}
                                        onChange={(e) => change('payee_x_mm', e.target.value)}
                                    />
                                    <DecimalInput
                                        placeholder="Y (mm)"
                                        value={form.payee_y_mm}
                                        error={fieldError(error, 'payee_y_mm')}
                                        onFocus={() => setActiveField('payee')}
                                        onChange={(e) => change('payee_y_mm', e.target.value)}
                                    />
                                </div>

                                {/* Amount Figures */}
                                <div className="grid grid-cols-[130px_1fr_1fr] items-center gap-2">
                                    <span className="text-sm font-medium text-slate-700">Amount (figures)</span>
                                    <DecimalInput
                                        placeholder="X (mm)"
                                        value={form.amount_x_mm}
                                        error={fieldError(error, 'amount_x_mm')}
                                        onFocus={() => setActiveField('amount')}
                                        onChange={(e) => change('amount_x_mm', e.target.value)}
                                    />
                                    <DecimalInput
                                        placeholder="Y (mm)"
                                        value={form.amount_y_mm}
                                        error={fieldError(error, 'amount_y_mm')}
                                        onFocus={() => setActiveField('amount')}
                                        onChange={(e) => change('amount_y_mm', e.target.value)}
                                    />
                                </div>

                                {/* Amount Words */}
                                <div className="grid grid-cols-[130px_1fr_1fr] items-center gap-2">
                                    <span className="text-sm font-medium text-slate-700">Words (Line 1)</span>
                                    <DecimalInput
                                        placeholder="X (mm)"
                                        value={form.amount_words_x_mm}
                                        error={fieldError(error, 'amount_words_x_mm')}
                                        onFocus={() => setActiveField('amount_words')}
                                        onChange={(e) => change('amount_words_x_mm', e.target.value)}
                                    />
                                    <DecimalInput
                                        placeholder="Y (mm)"
                                        value={form.amount_words_y_mm}
                                        error={fieldError(error, 'amount_words_y_mm')}
                                        onFocus={() => setActiveField('amount_words')}
                                        onChange={(e) => change('amount_words_y_mm', e.target.value)}
                                    />
                                </div>

                                {/* Cheque Number */}
                                <div className="grid grid-cols-[130px_1fr_1fr] items-center gap-2">
                                    <span className="text-sm font-medium text-slate-700">Cheque number</span>
                                    <DecimalInput
                                        placeholder="X (mm)"
                                        value={form.cheque_number_x_mm ?? ''}
                                        error={fieldError(error, 'cheque_number_x_mm')}
                                        onFocus={() => setActiveField('cheque_number')}
                                        onChange={(e) => change('cheque_number_x_mm', e.target.value)}
                                    />
                                    <DecimalInput
                                        placeholder="Y (mm)"
                                        value={form.cheque_number_y_mm ?? ''}
                                        error={fieldError(error, 'cheque_number_y_mm')}
                                        onFocus={() => setActiveField('cheque_number')}
                                        onChange={(e) => change('cheque_number_y_mm', e.target.value)}
                                    />
                                </div>
                            </div>
                        </Panel>

                        <Panel title="Cheque Security & Extended Settings">
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Select
                                    label="Payee crossing"
                                    value={form.metadata?.crossing_type ?? 'ac_payee'}
                                    options={[
                                        { value: 'none', label: 'None' },
                                        { value: 'ac_payee', label: 'A/C Payee Only' },
                                        { value: 'not_negotiable', label: 'Not Negotiable' },
                                    ]}
                                    onChange={(e) => changeMeta('crossing_type', e.target.value)}
                                />
                                <div className="grid grid-cols-2 gap-2">
                                    <DecimalInput
                                        label="Crossing X (mm)"
                                        value={String(form.metadata?.crossing_x_mm ?? '15')}
                                        onFocus={() => setActiveField('crossing')}
                                        onChange={(e) => changeMeta('crossing_x_mm', e.target.value)}
                                    />
                                    <DecimalInput
                                        label="Crossing Y (mm)"
                                        value={String(form.metadata?.crossing_y_mm ?? '10')}
                                        onFocus={() => setActiveField('crossing')}
                                        onChange={(e) => changeMeta('crossing_y_mm', e.target.value)}
                                    />
                                </div>

                                <Input
                                    label="Date format"
                                    value={form.metadata?.date_format ?? 'd/m/Y'}
                                    hint="PHP date format (e.g. d/m/Y, d-m-Y)"
                                    onChange={(e) => changeMeta('date_format', e.target.value)}
                                />
                                <DecimalInput
                                    label="Date box spacing (mm)"
                                    value={String(form.metadata?.date_letter_spacing ?? '')}
                                    placeholder="e.g. 2.5 for boxed cheques"
                                    hint="Leave empty for continuous string"
                                    onChange={(e) => changeMeta('date_letter_spacing', e.target.value)}
                                />

                                <Input
                                    label="Amount prefix"
                                    value={form.metadata?.amount_prefix ?? '**'}
                                    placeholder="e.g. ** or Rs."
                                    onChange={(e) => changeMeta('amount_prefix', e.target.value)}
                                />
                                <Input
                                    label="Amount suffix"
                                    value={form.metadata?.amount_suffix ?? '**'}
                                    placeholder="e.g. ** or /-"
                                    onChange={(e) => changeMeta('amount_suffix', e.target.value)}
                                />

                                <div className="sm:col-span-2 pt-2 border-t border-slate-100">
                                    <label className="flex items-center gap-2 text-sm font-medium text-slate-700 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            checked={Boolean(form.metadata?.bearer_strike_through ?? true)}
                                            onChange={(e) => changeMeta('bearer_strike_through', e.target.checked)}
                                        />
                                        Strike through "Or Bearer"
                                    </label>
                                </div>
                            </div>
                        </Panel>
                    </div>

                    {/* Right Column: Live Interactive Preview */}
                    <div className="space-y-3">
                        <div className="flex items-center justify-between">
                            <span className="text-sm font-semibold text-slate-800">Live Cheque Layout Preview</span>
                            <div className="flex items-center gap-2">
                                <label className="flex items-center gap-1.5 text-xs font-medium text-slate-600 cursor-pointer select-none">
                                    <input
                                        type="checkbox"
                                        checked={showGrid}
                                        onChange={(e) => setShowGrid(e.target.checked)}
                                    />
                                    10mm calibration grid
                                </label>
                            </div>
                        </div>

                        <div className="rounded-xl border border-slate-300 bg-slate-100 p-4 overflow-auto flex justify-center shadow-inner min-h-[220px]">
                            <ChequePreviewCanvas
                                payment={samplePayment}
                                template={previewTemplate}
                                showGrid={showGrid}
                                activeField={activeField}
                                onSelectField={(f) => setActiveField(f)}
                            />
                        </div>

                        <p className="text-xs text-slate-500 text-center">
                            Fields update live as millimeter coordinates are changed. Click on any field on the preview to inspect.
                        </p>
                    </div>
                </div>

                <div className="flex justify-end gap-3 pt-4 border-t border-slate-200">
                    <Button type="button" variant="secondary" onClick={() => navigate('/payments/cheque-templates')}>
                        Cancel
                    </Button>
                    <Button type="submit" loading={saving}>
                        {editing ? 'Save changes' : 'Create template'}
                    </Button>
                </div>
            </form>
        </>
    );
}
