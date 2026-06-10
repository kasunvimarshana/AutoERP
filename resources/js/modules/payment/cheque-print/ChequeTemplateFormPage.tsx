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
import { createChequeTemplate, getChequeTemplate, updateChequeTemplate } from './chequePrintApi';
import type { ChequeTemplatePayload } from './chequePrintTypes';

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
    metadata: { date_format: 'Y-m-d' },
};

const coordinateInputs = [
    ['date_x_mm', 'Date X'], ['date_y_mm', 'Date Y'],
    ['payee_x_mm', 'Payee X'], ['payee_y_mm', 'Payee Y'],
    ['amount_x_mm', 'Amount X'], ['amount_y_mm', 'Amount Y'],
    ['amount_words_x_mm', 'Words X'], ['amount_words_y_mm', 'Words Y'],
    ['cheque_number_x_mm', 'Cheque number X'], ['cheque_number_y_mm', 'Cheque number Y'],
] as const;

export default function ChequeTemplateFormPage() {
    const templateId = Number(useParams().id);
    const editing = Number.isInteger(templateId) && templateId > 0;
    const navigate = useNavigate();
    const [form, setForm] = useState<ChequeTemplatePayload>(initialTemplate);
    const [loading, setLoading] = useState(editing);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    useEffect(() => {
        if (!editing) return;
        const controller = new AbortController();
        getChequeTemplate(templateId, controller.signal)
            .then((template) => {
                if (controller.signal.aborted) return;
                setForm({
                    bank_name: template.bank_name ?? '',
                    template_name: template.template_name,
                    page_width_mm: template.page_width_mm,
                    page_height_mm: template.page_height_mm,
                    date_x_mm: template.date_x_mm,
                    date_y_mm: template.date_y_mm,
                    payee_x_mm: template.payee_x_mm,
                    payee_y_mm: template.payee_y_mm,
                    amount_x_mm: template.amount_x_mm,
                    amount_y_mm: template.amount_y_mm,
                    amount_words_x_mm: template.amount_words_x_mm,
                    amount_words_y_mm: template.amount_words_y_mm,
                    cheque_number_x_mm: template.cheque_number_x_mm ?? '',
                    cheque_number_y_mm: template.cheque_number_y_mm ?? '',
                    font_size: template.font_size,
                    font_family: template.font_family ?? '',
                    is_default: template.is_default,
                    is_active: template.is_active,
                    metadata: { date_format: template.metadata?.date_format ?? 'Y-m-d' },
                });
            })
            .catch((requestError) => !controller.signal.aborted && setError(toApiError(requestError)))
            .finally(() => !controller.signal.aborted && setLoading(false));
        return () => controller.abort();
    }, [editing, templateId]);

    function change<K extends keyof ChequeTemplatePayload>(key: K, value: ChequeTemplatePayload[K]) {
        setForm((current) => ({ ...current, [key]: value }));
    }

    async function save() {
        setSaving(true);
        setError(null);
        try {
            const payload = {
                ...form,
                bank_name: form.bank_name || null,
                font_family: form.font_family || null,
                cheque_number_x_mm: form.cheque_number_x_mm || null,
                cheque_number_y_mm: form.cheque_number_y_mm || null,
            };
            const saved = editing
                ? await updateChequeTemplate(templateId, payload)
                : await createChequeTemplate(payload);
            navigate(`/payments/cheque-templates/${saved.id}/edit`, { replace: true });
        } catch (requestError) {
            setError(toApiError(requestError));
        } finally {
            setSaving(false);
        }
    }

    if (loading) return <LoadingState />;

    return (
        <>
            <ContentHeader
                title={editing ? 'Edit cheque template' : 'New cheque template'}
                description="Measure from the cheque page's top-left corner and enter positions in millimeters."
            />
            <ErrorAlert error={error} />
            <form className="space-y-5" onSubmit={(event) => { event.preventDefault(); void save(); }}>
                <Panel title="Template">
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <Input label="Template name" value={form.template_name} error={fieldError(error, 'template_name')} onChange={(event) => change('template_name', event.target.value)} />
                        <Input label="Bank name" value={form.bank_name ?? ''} error={fieldError(error, 'bank_name')} onChange={(event) => change('bank_name', event.target.value)} />
                        <DecimalInput label="Page width (mm)" value={form.page_width_mm} error={fieldError(error, 'page_width_mm')} onChange={(event) => change('page_width_mm', event.target.value)} />
                        <DecimalInput label="Page height (mm)" value={form.page_height_mm} error={fieldError(error, 'page_height_mm')} onChange={(event) => change('page_height_mm', event.target.value)} />
                        <DecimalInput label="Font size (pt)" value={form.font_size} error={fieldError(error, 'font_size')} onChange={(event) => change('font_size', event.target.value)} />
                        <Input label="Font family" value={form.font_family ?? ''} error={fieldError(error, 'font_family')} onChange={(event) => change('font_family', event.target.value)} />
                        <Input
                            label="Date format"
                            value={form.metadata?.date_format ?? ''}
                            error={fieldError(error, 'metadata.date_format')}
                            hint="PHP date format, for example d/m/Y"
                            onChange={(event) => change('metadata', { date_format: event.target.value })}
                        />
                    </div>
                    <div className="mt-5 flex flex-wrap gap-6">
                        <label className="flex items-center gap-2 text-sm font-medium text-slate-700">
                            <input type="checkbox" checked={form.is_default} onChange={(event) => change('is_default', event.target.checked)} />
                            Default template
                        </label>
                        <label className="flex items-center gap-2 text-sm font-medium text-slate-700">
                            <input type="checkbox" checked={form.is_active} onChange={(event) => change('is_active', event.target.checked)} />
                            Active
                        </label>
                    </div>
                </Panel>
                <Panel title="Field alignment">
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
                        {coordinateInputs.map(([key, label]) => (
                            <DecimalInput
                                key={key}
                                label={`${label} (mm)`}
                                value={form[key] ?? ''}
                                error={fieldError(error, key)}
                                onChange={(event) => change(key, event.target.value)}
                            />
                        ))}
                    </div>
                </Panel>
                <div className="flex justify-end gap-2">
                    <Button type="button" variant="secondary" onClick={() => navigate('/payments/cheque-templates')}>Back</Button>
                    <Button type="submit" loading={saving}>Save template</Button>
                </div>
            </form>
        </>
    );
}
