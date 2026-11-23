/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { useEffect, useRef, useState, type ChangeEvent, type FormEvent } from 'react';
import { Drawer } from '@/shared/components/Drawer';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { createInvoiceLayout, updateInvoiceLayout, uploadInvoiceBladeTemplate } from '../invoiceLayoutApi';
import type {
    CreateInvoiceLayoutPayload,
    InvoiceLayout,
    InvoiceLayoutSettings,
    InvoiceLayoutType,
    InvoiceOrientation,
    InvoicePaperSize,
    UpdateInvoiceLayoutPayload,
} from '../invoiceLayoutTypes';

interface InvoiceLayoutEditorDrawerProps {
    open: boolean;
    layout: InvoiceLayout | null; // null for create
    onClose: () => void;
    onSaved: (layout: InvoiceLayout) => void;
}

const PAPER_SIZE_OPTIONS = [
    { value: 'a4', label: 'A4 (210 × 297 mm)' },
    { value: 'a5', label: 'A5 (148 × 210 mm)' },
    { value: 'thermal_80mm', label: 'Thermal POS (80mm Roll)' },
    { value: 'continuous_8_5_by_5_5', label: 'Continuous Dot-Matrix (8.5 × 5.5 in)' },
    { value: 'letter', label: 'US Letter (8.5 × 11 in)' },
];

const ORIENTATION_OPTIONS = [
    { value: 'portrait', label: 'Portrait' },
    { value: 'landscape', label: 'Landscape' },
];

const TEMPLATE_OPTIONS = [
    { value: 'invoice.layouts.standard', label: 'Classic Standard (Traditional corporate bordered format)' },
    { value: 'invoice.layouts.modern', label: 'Modern Clean (Contemporary card-style accent headers)' },
    { value: 'invoice.layouts.compact', label: 'Compact Slip (Condensed half-sheet counter format)' },
    { value: 'invoice.layouts.thermal', label: 'Thermal Receipt (Continuous 80mm POS slip)' },
    { value: 'invoice.layouts.continuous', label: 'Continuous Feed (Pre-printed or carbonized dot-matrix)' },
];

const FONT_FAMILY_OPTIONS = [
    { value: 'sans-serif', label: 'Sans-Serif (Modern / Clean)' },
    { value: 'serif', label: 'Serif (Traditional / Formal)' },
    { value: 'monospace', label: 'Monospace (Receipt / Matrix)' },
];

const STARTER_BLADE_TEMPLATE = `<div class="invoice-container" style="font-family: Arial, sans-serif; color: #111; padding: 12px;">
    <!-- Header -->
    <table style="width: 100%; border-bottom: 2px solid #2563eb; padding-bottom: 12px; margin-bottom: 16px;">
        <tr>
            <td style="vertical-align: top;">
                <h1 style="margin: 0; font-size: 22px; color: #1e3a8a;">{{ $document['supplier']['name'] }}</h1>
                <p style="margin: 4px 0 0; font-size: 11px; color: #475569;">{{ $document['supplier']['address'] }}</p>
                <p style="margin: 2px 0 0; font-size: 11px; color: #64748b;">
                    @if (!empty($document['supplier']['phone'])) Tel: {{ $document['supplier']['phone'] }} @endif
                    @if (!empty($document['supplier']['tin'])) | TIN: {{ $document['supplier']['tin'] }} @endif
                    @if (!empty($document['supplier']['vat_registration_number'])) | VAT: {{ $document['supplier']['vat_registration_number'] }} @endif
                </p>
            </td>
            <td style="text-align: right; vertical-align: top;">
                <h2 style="margin: 0; font-size: 20px; color: #1e3a8a; text-transform: uppercase;">{{ $document['title'] }}</h2>
                <p style="margin: 4px 0 0; font-size: 12px; font-weight: bold;">{{ $document['number_label'] }}: {{ $document['invoice_number'] }}</p>
                <p style="margin: 2px 0 0; font-size: 11px; color: #475569;">Date: {{ $document['invoice_date_display'] ?? $document['invoice_date'] }}</p>
                @if (!empty($document['due_date']))
                    <p style="margin: 2px 0 0; font-size: 11px; color: #dc2626;">Due Date: {{ $document['due_date'] }}</p>
                @endif
            </td>
        </tr>
    </table>

    <!-- Parties Section -->
    <table style="width: 100%; margin-bottom: 16px; border-collapse: separate; border-spacing: 8px 0;">
        <tr>
            <td style="width: 50%; vertical-align: top; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; background: #f8fafc;">
                <strong style="display: block; font-size: 10px; text-transform: uppercase; color: #64748b; margin-bottom: 4px;">Billed To:</strong>
                <div style="font-weight: bold; font-size: 13px;">{{ $document['purchaser']['name'] }}</div>
                <div style="font-size: 11px; color: #475569; margin-top: 2px;">{{ $document['purchaser']['address'] }}</div>
                @if (!empty($document['purchaser']['tin']))
                    <div style="font-size: 11px; color: #64748b; margin-top: 2px;">TIN: {{ $document['purchaser']['tin'] }}</div>
                @endif
            </td>
            <td style="width: 50%; vertical-align: top; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; background: #f8fafc;">
                <strong style="display: block; font-size: 10px; text-transform: uppercase; color: #64748b; margin-bottom: 4px;">Payment & Terms:</strong>
                <div style="font-size: 11px;">Status: <span style="font-weight: bold; text-transform: uppercase;">{{ $document['status'] }}</span></div>
                @if (!empty($document['resolved_payment_mode']))
                    <div style="font-size: 11px; margin-top: 2px;">Payment: {{ $document['resolved_payment_mode'] }}</div>
                @endif
                @if (!empty($document['payment_terms']))
                    <div style="font-size: 11px; margin-top: 2px;">Terms: {{ $document['payment_terms'] }}</div>
                @endif
            </td>
        </tr>
    </table>

    <!-- Line Items Table -->
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 16px;">
        <thead>
            <tr style="background: #f1f5f9; border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1;">
                <th style="padding: 8px; text-align: left; font-size: 11px; text-transform: uppercase;">#</th>
                <th style="padding: 8px; text-align: left; font-size: 11px; text-transform: uppercase;">Item / Description</th>
                <th style="padding: 8px; text-align: right; font-size: 11px; text-transform: uppercase;">Qty</th>
                <th style="padding: 8px; text-align: right; font-size: 11px; text-transform: uppercase;">Unit Price</th>
                <th style="padding: 8px; text-align: right; font-size: 11px; text-transform: uppercase;">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($document['lines'] as $line)
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 8px; font-size: 11px;">{{ $line['line_number'] }}</td>
                    <td style="padding: 8px; font-size: 11px;">
                        <strong>{{ $line['display_name'] }}</strong>
                        @if (!empty($line['description']) && $line['description'] !== $line['display_name'])
                            <div style="color: #64748b; font-size: 10px;">{{ $line['description'] }}</div>
                        @endif
                    </td>
                    <td style="padding: 8px; text-align: right; font-size: 11px;">{{ $line['quantity']['display'] }}</td>
                    <td style="padding: 8px; text-align: right; font-size: 11px;">{{ $line['unit_price']['display'] }}</td>
                    <td style="padding: 8px; text-align: right; font-size: 11px; font-weight: bold;">{{ $line['line_total']['display'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals Table -->
    <table style="width: 100%; margin-bottom: 16px;">
        <tr>
            <td style="vertical-align: top; width: 60%; font-size: 10px; color: #64748b; padding-right: 16px;">
                @if (!empty($document['notes']))
                    <div><strong>Notes:</strong> {{ $document['notes'] }}</div>
                @endif
                @if (!empty($document['amount_in_words']))
                    <div style="margin-top: 4px;"><strong>In Words:</strong> {{ $document['amount_in_words'] }}</div>
                @endif
            </td>
            <td style="vertical-align: top; width: 40%;">
                <div style="border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; background: #f8fafc;">
                    <div style="display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 4px;">
                        <span>Subtotal:</span>
                        <span>{{ $document['amounts']['subtotal']['display'] }}</span>
                    </div>
                    @if ($document['amounts']['discount_total']['raw'] !== '0.000000')
                        <div style="display: flex; justify-content: space-between; font-size: 11px; color: #16a34a; margin-bottom: 4px;">
                            <span>Discounts:</span>
                            <span>-{{ $document['amounts']['discount_total']['display'] }}</span>
                        </div>
                    @endif
                    <div style="display: flex; justify-content: space-between; font-size: 11px; margin-bottom: 4px;">
                        <span>Tax Total:</span>
                        <span>{{ $document['amounts']['tax_total']['display'] }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 13px; font-weight: bold; border-top: 1px solid #cbd5e1; padding-top: 6px; margin-top: 4px;">
                        <span>Grand Total:</span>
                        <span style="color: #1e3a8a;">{{ $document['amounts']['grand_total']['display'] }}</span>
                    </div>
                    @if ($document['amounts']['balance_due']['raw'] !== '0.000000')
                        <div style="display: flex; justify-content: space-between; font-size: 12px; font-weight: bold; color: #dc2626; margin-top: 4px;">
                            <span>Balance Due:</span>
                            <span>{{ $document['amounts']['balance_due']['display'] }}</span>
                        </div>
                    @endif
                </div>
            </td>
        </tr>
    </table>
</div>`;

export function InvoiceLayoutEditorDrawer({
    open,
    layout,
    onClose,
    onSaved,
}: InvoiceLayoutEditorDrawerProps) {
    const isEdit = layout !== null;
    const isSystem = layout?.is_system ?? false;

    const [templateType, setTemplateType] = useState<InvoiceLayoutType>('preset');
    const [name, setName] = useState('');
    const [code, setCode] = useState('');
    const [description, setDescription] = useState('');
    const [paperSize, setPaperSize] = useState<InvoicePaperSize>('a4');
    const [orientation, setOrientation] = useState<InvoiceOrientation>('portrait');
    const [viewTemplate, setViewTemplate] = useState('invoice.layouts.standard');
    const [bladeContent, setBladeContent] = useState('');
    const [isActive, setIsActive] = useState(true);
    const [customCss, setCustomCss] = useState('');
    const [headerContent, setHeaderContent] = useState('');
    const [footerContent, setFooterContent] = useState('');

    // Settings
    const [primaryColor, setPrimaryColor] = useState('#173956');
    const [fontFamily, setFontFamily] = useState('sans-serif');
    const [showLogo, setShowLogo] = useState(true);
    const [showTaxBreakdown, setShowTaxBreakdown] = useState(true);
    const [showPaymentStub, setShowPaymentStub] = useState(true);
    const [showSignatures, setShowSignatures] = useState(true);
    const [showAmountInWords, setShowAmountInWords] = useState(true);
    const [showNotes, setShowNotes] = useState(true);
    const [showQrCode, setShowQrCode] = useState(false);
    const [notes, setNotes] = useState('');
    const [terms, setTerms] = useState('');

    // Auxiliary UI State
    const [saving, setSaving] = useState(false);
    const [uploading, setUploading] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const [cheatSheetOpen, setCheatSheetOpen] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        if (!open) return;

        if (layout) {
            setTemplateType(layout.template_type ?? 'preset');
            setName(layout.name);
            setCode(layout.code);
            setDescription(layout.description ?? '');
            setPaperSize(layout.paper_size);
            setOrientation(layout.orientation);
            setViewTemplate(layout.view_template ?? 'invoice.layouts.standard');
            setBladeContent(layout.blade_content ?? '');
            setIsActive(layout.is_active);
            setCustomCss(layout.custom_css ?? '');
            setHeaderContent(layout.header_content ?? '');
            setFooterContent(layout.footer_content ?? '');

            const s = layout.settings ?? {};
            setPrimaryColor(s.primary_color ?? '#173956');
            setFontFamily(s.font_family ?? 'sans-serif');
            setShowLogo(s.show_logo ?? s.show_company_logo ?? true);
            setShowTaxBreakdown(s.show_tax_breakdown ?? s.show_tax_summary ?? true);
            setShowPaymentStub(s.show_payment_stub ?? s.show_payment_details ?? true);
            setShowSignatures(s.show_signatures ?? true);
            setShowAmountInWords(s.show_amount_in_words ?? true);
            setShowNotes(s.show_notes ?? true);
            setShowQrCode(s.show_qr_code ?? false);
            setNotes(s.notes ?? '');
            setTerms(s.terms ?? '');
        } else {
            setTemplateType('preset');
            setName('');
            setCode('');
            setDescription('');
            setPaperSize('a4');
            setOrientation('portrait');
            setViewTemplate('invoice.layouts.modern');
            setBladeContent('');
            setIsActive(true);
            setCustomCss('');
            setHeaderContent('');
            setFooterContent('');
            setPrimaryColor('#173956');
            setFontFamily('sans-serif');
            setShowLogo(true);
            setShowTaxBreakdown(true);
            setShowPaymentStub(true);
            setShowSignatures(true);
            setShowAmountInWords(true);
            setShowNotes(true);
            setShowQrCode(false);
            setNotes('');
            setTerms('');
        }
        setError(null);
    }, [open, layout]);

    const handleTemplateChange = (newTemplate: string) => {
        setViewTemplate(newTemplate);
        if (newTemplate === 'invoice.layouts.thermal') {
            setPaperSize('thermal_80mm');
            setFontFamily('monospace');
            setPrimaryColor('#000000');
        } else if (newTemplate === 'invoice.layouts.compact') {
            setPaperSize('a5');
            setFontFamily('sans-serif');
        } else if (newTemplate === 'invoice.layouts.continuous') {
            setPaperSize('continuous_8_5_by_5_5');
            setFontFamily('sans-serif');
        } else if (newTemplate === 'invoice.layouts.standard') {
            setPaperSize('a4');
            setFontFamily('serif');
            setPrimaryColor('#111827');
        }
    };

    const handleSwitchToBlade = () => {
        setTemplateType('blade');
        if (bladeContent.trim() === '') {
            setBladeContent(STARTER_BLADE_TEMPLATE);
        }
    };

    const handleFileUpload = async (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];
        if (!file) return;

        setUploading(true);
        setError(null);
        try {
            const uploaded = await uploadInvoiceBladeTemplate(file);
            setBladeContent(uploaded.content);
            if (!name) {
                const cleanName = uploaded.filename.replace(/\.blade\.php$/i, '').replace(/[_-]+/g, ' ');
                setName(cleanName.charAt(0).toUpperCase() + cleanName.slice(1));
            }
        } catch (caught: unknown) {
            setError(toApiError(caught));
        } finally {
            setUploading(false);
            if (fileInputRef.current) {
                fileInputRef.current.value = '';
            }
        }
    };

    const handleDownloadTemplate = () => {
        if (!bladeContent) return;
        const blob = new Blob([bladeContent], { type: 'text/x-php;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `${code || 'invoice_layout'}.blade.php`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    };

    const handleInsertSnippet = (snippet: string) => {
        setBladeContent((prev) => `${prev}\n${snippet}`);
    };

    const handleSubmit = async (event: FormEvent) => {
        event.preventDefault();
        setSaving(true);
        setError(null);

        const isBlade = templateType === 'blade';

        const settingsPayload: InvoiceLayoutSettings = {
            primary_color: primaryColor,
            font_family: fontFamily,
            show_logo: showLogo,
            show_company_logo: showLogo,
            show_tax_breakdown: showTaxBreakdown,
            show_tax_summary: showTaxBreakdown,
            show_payment_stub: showPaymentStub,
            show_payment_details: showPaymentStub,
            show_signatures: showSignatures,
            show_amount_in_words: showAmountInWords,
            show_notes: showNotes,
            show_qr_code: showQrCode,
            notes: notes.trim() !== '' ? notes.trim() : null,
            terms: terms.trim() !== '' ? terms.trim() : null,
        };

        try {
            if (isEdit) {
                const payload: UpdateInvoiceLayoutPayload = {
                    expected_version: layout.row_version,
                    name: name.trim(),
                    description: description.trim() !== '' ? description.trim() : null,
                    paper_size: paperSize,
                    orientation: orientation,
                    template_type: templateType,
                    view_template: isBlade ? (viewTemplate || 'custom.blade') : viewTemplate,
                    blade_content: isBlade ? bladeContent : null,
                    is_active: isActive,
                    custom_css: customCss.trim() !== '' ? customCss.trim() : null,
                    header_content: headerContent.trim() !== '' ? headerContent.trim() : null,
                    footer_content: footerContent.trim() !== '' ? footerContent.trim() : null,
                    settings: settingsPayload,
                };
                const saved = await updateInvoiceLayout(layout.id, payload);
                onSaved(saved);
                onClose();
            } else {
                const payload: CreateInvoiceLayoutPayload = {
                    code: code.trim() !== '' ? code.trim() : undefined,
                    name: name.trim(),
                    description: description.trim() !== '' ? description.trim() : null,
                    paper_size: paperSize,
                    orientation: orientation,
                    template_type: templateType,
                    view_template: isBlade ? (viewTemplate || 'custom.blade') : viewTemplate,
                    blade_content: isBlade ? bladeContent : null,
                    is_active: isActive,
                    custom_css: customCss.trim() !== '' ? customCss.trim() : null,
                    header_content: headerContent.trim() !== '' ? headerContent.trim() : null,
                    footer_content: footerContent.trim() !== '' ? footerContent.trim() : null,
                    settings: settingsPayload,
                };
                const created = await createInvoiceLayout(payload);
                onSaved(created);
                onClose();
            }
        } catch (caught: unknown) {
            setError(toApiError(caught));
        } finally {
            setSaving(false);
        }
    };

    return (
        <Drawer
            open={open}
            title={isEdit ? `Edit Layout: ${layout.name}` : 'Create New Invoice Layout'}
            onClose={onClose}
        >
            <form onSubmit={(e) => void handleSubmit(e)} className="space-y-6">
                <ErrorAlert error={error} />

                {isSystem && (
                    <div className="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
                        <strong>System Layout:</strong> Core system templates are managed by the application. Changes to code and core layout identifiers are restricted, but visual styles, banners, policies, and settings can be customized.
                    </div>
                )}

                {/* Template Mode Selection */}
                {!isSystem && (
                    <div className="space-y-2">
                        <label className="block text-xs font-semibold text-slate-800 uppercase tracking-wider">
                            Template Architecture
                        </label>
                        <div className="grid grid-cols-2 gap-3">
                            <button
                                type="button"
                                onClick={() => setTemplateType('preset')}
                                className={`flex flex-col items-start rounded-lg border p-3 text-left transition-all ${
                                    templateType === 'preset'
                                        ? 'border-indigo-600 bg-indigo-50/50 ring-1 ring-indigo-600'
                                        : 'border-slate-200 bg-white hover:bg-slate-50'
                                }`}
                            >
                                <span className="font-semibold text-xs text-slate-900">Standard Visual Preset</span>
                                <span className="text-[11px] text-slate-500 mt-1">
                                    Configure established system designs with customizable theme colors and options.
                                </span>
                            </button>

                            <button
                                type="button"
                                onClick={handleSwitchToBlade}
                                className={`flex flex-col items-start rounded-lg border p-3 text-left transition-all ${
                                    templateType === 'blade'
                                        ? 'border-indigo-600 bg-indigo-50/50 ring-1 ring-indigo-600'
                                        : 'border-slate-200 bg-white hover:bg-slate-50'
                                }`}
                            >
                                <span className="flex items-center gap-1.5 font-semibold text-xs text-slate-900">
                                    Custom Blade Template
                                    <span className="rounded bg-indigo-100 px-1.5 py-0.2 text-[10px] font-mono text-indigo-700">.blade.php</span>
                                </span>
                                <span className="text-[11px] text-slate-500 mt-1">
                                    Design completely custom layouts using Blade syntax, HTML, and dynamic invoice variables.
                                </span>
                            </button>
                        </div>
                    </div>
                )}

                {/* Basic Information */}
                <div className="space-y-4">
                    <h3 className="text-sm font-semibold text-slate-800">Basic Information</h3>
                    <Input
                        label="Layout Name"
                        value={name}
                        onChange={(e) => setName(e.target.value)}
                        required
                        placeholder="e.g. Modern Clean Wholesale Invoice"
                    />

                    {!isEdit && (
                        <Input
                            label="Layout Code (Unique identifier)"
                            value={code}
                            onChange={(e) => setCode(e.target.value)}
                            placeholder="e.g. wholesale_modern_a4 (leave blank to auto-generate)"
                            hint="Alphanumeric with underscores or dashes."
                        />
                    )}

                    <Textarea
                        label="Description"
                        value={description}
                        onChange={(e) => setDescription(e.target.value)}
                        placeholder="Describe when this layout should be used..."
                        rows={2}
                    />
                </div>

                {/* Paper & Orientation */}
                <div className="space-y-4 border-t border-slate-100 pt-4">
                    <h3 className="text-sm font-semibold text-slate-800">Paper & Page Dimensions</h3>
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <Select
                            label="Paper Size"
                            value={paperSize}
                            onChange={(e) => setPaperSize(e.target.value as InvoicePaperSize)}
                            options={PAPER_SIZE_OPTIONS}
                            disabled={isSystem}
                        />

                        <Select
                            label="Orientation"
                            value={orientation}
                            onChange={(e) => setOrientation(e.target.value as InvoiceOrientation)}
                            options={ORIENTATION_OPTIONS}
                            disabled={isSystem}
                        />
                    </div>

                    <div className="flex items-center gap-2 pt-2">
                        <input
                            type="checkbox"
                            id="layout_is_active"
                            checked={isActive}
                            onChange={(e) => setIsActive(e.target.checked)}
                            className="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900"
                        />
                        <label htmlFor="layout_is_active" className="text-sm font-medium text-slate-700">
                            Layout is Active (available for printing and selection)
                        </label>
                    </div>
                </div>

                {/* Custom Blade Template Editor Mode */}
                {templateType === 'blade' && (
                    <div className="space-y-4 border-t border-slate-100 pt-4">
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <h3 className="text-sm font-semibold text-slate-800">Blade Template (.blade.php)</h3>
                                <p className="text-xs text-slate-500">
                                    Author custom HTML and Blade tags. Security sandbox automatically sanitizes and compiles the template.
                                </p>
                            </div>
                            <div className="flex items-center gap-2">
                                <input
                                    ref={fileInputRef}
                                    type="file"
                                    accept=".blade.php,.php"
                                    onChange={(e) => void handleFileUpload(e)}
                                    className="hidden"
                                />
                                <Button
                                    type="button"
                                    variant="secondary"
                                    size="sm"
                                    disabled={uploading}
                                    onClick={() => fileInputRef.current?.click()}
                                >
                                    {uploading ? 'Validating…' : 'Upload .blade.php'}
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => setBladeContent(STARTER_BLADE_TEMPLATE)}
                                    title="Replace editor with clean boilerplate template"
                                >
                                    Reset Boilerplate
                                </Button>
                                {bladeContent && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={handleDownloadTemplate}
                                        title="Download template file to your computer"
                                    >
                                        Download
                                    </Button>
                                )}
                            </div>
                        </div>

                        {/* Monospace Code Editor */}
                        <div>
                            <textarea
                                value={bladeContent}
                                onChange={(e) => setBladeContent(e.target.value)}
                                rows={16}
                                spellCheck={false}
                                placeholder="<div>Write your custom Blade template here...</div>"
                                className="w-full rounded-lg border border-slate-300 bg-slate-900 p-3 font-mono text-xs text-slate-100 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                            />
                        </div>

                        {/* Interactive Variable Reference / Cheat Sheet */}
                        <div className="rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <button
                                type="button"
                                onClick={() => setCheatSheetOpen(!cheatSheetOpen)}
                                className="flex w-full items-center justify-between text-left text-xs font-semibold text-slate-700"
                            >
                                <span>📚 Available Blade Template Variables & Directives Cheat Sheet</span>
                                <span>{cheatSheetOpen ? '▲ Hide' : '▼ View Reference'}</span>
                            </button>

                            {cheatSheetOpen && (
                                <div className="mt-3 space-y-3 border-t border-slate-200 pt-3 text-xs text-slate-600">
                                    <div>
                                        <span className="font-semibold text-slate-800">Document Information:</span>
                                        <div className="mt-1 flex flex-wrap gap-1 font-mono text-[11px]">
                                            {[
                                                `{{ $document['invoice_number'] }}`,
                                                `{{ $document['title'] }}`,
                                                `{{ $document['invoice_date_display'] }}`,
                                                `{{ $document['due_date'] }}`,
                                                `{{ $document['status'] }}`,
                                                `{{ $document['amount_in_words'] }}`,
                                            ].map((snippet) => (
                                                <button
                                                    key={snippet}
                                                    type="button"
                                                    onClick={() => handleInsertSnippet(snippet)}
                                                    className="rounded bg-white px-2 py-0.5 border border-slate-200 hover:bg-indigo-50 hover:border-indigo-300 text-slate-700"
                                                    title="Click to insert"
                                                >
                                                    + {snippet}
                                                </button>
                                            ))}
                                        </div>
                                    </div>

                                    <div>
                                        <span className="font-semibold text-slate-800">Supplier & Purchaser:</span>
                                        <div className="mt-1 flex flex-wrap gap-1 font-mono text-[11px]">
                                            {[
                                                `{{ $document['supplier']['name'] }}`,
                                                `{{ $document['supplier']['address'] }}`,
                                                `{{ $document['supplier']['tin'] }}`,
                                                `{{ $document['purchaser']['name'] }}`,
                                                `{{ $document['purchaser']['address'] }}`,
                                                `{{ $document['purchaser']['tin'] }}`,
                                            ].map((snippet) => (
                                                <button
                                                    key={snippet}
                                                    type="button"
                                                    onClick={() => handleInsertSnippet(snippet)}
                                                    className="rounded bg-white px-2 py-0.5 border border-slate-200 hover:bg-indigo-50 hover:border-indigo-300 text-slate-700"
                                                    title="Click to insert"
                                                >
                                                    + {snippet}
                                                </button>
                                            ))}
                                        </div>
                                    </div>

                                    <div>
                                        <span className="font-semibold text-slate-800">Line Items & Totals:</span>
                                        <div className="mt-1 flex flex-wrap gap-1 font-mono text-[11px]">
                                            {[
                                                `{{ $line['display_name'] }}`,
                                                `{{ $line['quantity']['display'] }}`,
                                                `{{ $line['unit_price']['display'] }}`,
                                                `{{ $line['line_total']['display'] }}`,
                                                `{{ $document['amounts']['subtotal']['display'] }}`,
                                                `{{ $document['amounts']['tax_total']['display'] }}`,
                                                `{{ $document['amounts']['grand_total']['display'] }}`,
                                                `{{ $document['amounts']['balance_due']['display'] }}`,
                                            ].map((snippet) => (
                                                <button
                                                    key={snippet}
                                                    type="button"
                                                    onClick={() => handleInsertSnippet(snippet)}
                                                    className="rounded bg-white px-2 py-0.5 border border-slate-200 hover:bg-indigo-50 hover:border-indigo-300 text-slate-700"
                                                    title="Click to insert"
                                                >
                                                    + {snippet}
                                                </button>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {/* Standard Preset Mode Configuration */}
                {templateType === 'preset' && (
                    <>
                        <div className="space-y-4 border-t border-slate-100 pt-4">
                            <h3 className="text-sm font-semibold text-slate-800">Preset Base Format</h3>
                            <Select
                                label="Base Template"
                                value={viewTemplate}
                                onChange={(e) => handleTemplateChange(e.target.value)}
                                options={TEMPLATE_OPTIONS}
                                disabled={isSystem}
                            />
                        </div>

                        {/* Styling & Typography */}
                        <div className="space-y-4 border-t border-slate-100 pt-4">
                            <h3 className="text-sm font-semibold text-slate-800">Styling & Typography</h3>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label className="block text-xs font-medium text-slate-700 mb-1">
                                        Brand Primary Color
                                    </label>
                                    <div className="flex items-center gap-2">
                                        <input
                                            type="color"
                                            value={primaryColor}
                                            onChange={(e) => setPrimaryColor(e.target.value)}
                                            className="h-9 w-12 cursor-pointer rounded border border-slate-300 p-0.5"
                                        />
                                        <Input
                                            value={primaryColor}
                                            onChange={(e) => setPrimaryColor(e.target.value)}
                                            placeholder="#173956"
                                            className="flex-1 font-mono text-xs"
                                        />
                                    </div>
                                </div>

                                <Select
                                    label="Font Family"
                                    value={fontFamily}
                                    onChange={(e) => setFontFamily(e.target.value)}
                                    options={FONT_FAMILY_OPTIONS}
                                />
                            </div>
                        </div>

                        {/* Visual Elements & Sections */}
                        <div className="space-y-4 border-t border-slate-100 pt-4">
                            <h3 className="text-sm font-semibold text-slate-800">Visual Elements & Sections</h3>
                            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <label className="flex items-center gap-2 text-sm text-slate-700">
                                    <input
                                        type="checkbox"
                                        checked={showLogo}
                                        onChange={(e) => setShowLogo(e.target.checked)}
                                        className="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900"
                                    />
                                    Show Company Logo
                                </label>
                                <label className="flex items-center gap-2 text-sm text-slate-700">
                                    <input
                                        type="checkbox"
                                        checked={showTaxBreakdown}
                                        onChange={(e) => setShowTaxBreakdown(e.target.checked)}
                                        className="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900"
                                    />
                                    Show Tax Breakdown
                                </label>
                                <label className="flex items-center gap-2 text-sm text-slate-700">
                                    <input
                                        type="checkbox"
                                        checked={showPaymentStub}
                                        onChange={(e) => setShowPaymentStub(e.target.checked)}
                                        className="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900"
                                    />
                                    Show Payment Details / Stub
                                </label>
                                <label className="flex items-center gap-2 text-sm text-slate-700">
                                    <input
                                        type="checkbox"
                                        checked={showSignatures}
                                        onChange={(e) => setShowSignatures(e.target.checked)}
                                        className="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900"
                                    />
                                    Show Signatures Section
                                </label>
                                <label className="flex items-center gap-2 text-sm text-slate-700">
                                    <input
                                        type="checkbox"
                                        checked={showAmountInWords}
                                        onChange={(e) => setShowAmountInWords(e.target.checked)}
                                        className="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900"
                                    />
                                    Show Amount in Words
                                </label>
                                <label className="flex items-center gap-2 text-sm text-slate-700">
                                    <input
                                        type="checkbox"
                                        checked={showNotes}
                                        onChange={(e) => setShowNotes(e.target.checked)}
                                        className="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900"
                                    />
                                    Show Policy / Notes Section
                                </label>
                                <label className="flex items-center gap-2 text-sm text-slate-700">
                                    <input
                                        type="checkbox"
                                        checked={showQrCode}
                                        onChange={(e) => setShowQrCode(e.target.checked)}
                                        className="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900"
                                    />
                                    Show QR Verification Code
                                </label>
                            </div>
                        </div>

                        {/* Header & Footer Custom Content */}
                        <div className="space-y-4 border-t border-slate-100 pt-4">
                            <h3 className="text-sm font-semibold text-slate-800">Header & Footer Content</h3>
                            <Textarea
                                label="Top Header Announcement / Greeting"
                                value={headerContent}
                                onChange={(e) => setHeaderContent(e.target.value)}
                                placeholder="e.g. Welcome to our flagship store! Seasonal promotions apply..."
                                rows={2}
                                hint="Displayed prominently at the top of the invoice document."
                            />

                            <Textarea
                                label="Standard Policy / Notes Override"
                                value={notes}
                                onChange={(e) => setNotes(e.target.value)}
                                placeholder="e.g. Goods once sold will not be accepted back after 7 days..."
                                rows={2}
                            />

                            <Textarea
                                label="Payment Terms & Bank Details Override"
                                value={terms}
                                onChange={(e) => setTerms(e.target.value)}
                                placeholder="e.g. Net 30 days. Please wire to Bank Account: 123-456-789 (IBAN: ...)"
                                rows={2}
                            />

                            <Textarea
                                label="Bottom Footer Notice / Warranty Clause"
                                value={footerContent}
                                onChange={(e) => setFooterContent(e.target.value)}
                                placeholder="e.g. Certified ISO 9001 Facility. Thank you for your continued patronage."
                                rows={2}
                                hint="Displayed at the bottom of the invoice page."
                            />
                        </div>
                    </>
                )}

                {/* Custom CSS Styling (Applies to both Preset & Blade) */}
                <div className="space-y-4 border-t border-slate-100 pt-4">
                    <h3 className="text-sm font-semibold text-slate-800">Advanced Custom Styling</h3>
                    <Textarea
                        label="Custom CSS Overrides"
                        value={customCss}
                        onChange={(e) => setCustomCss(e.target.value)}
                        placeholder={`.sheet { font-family: 'Helvetica', sans-serif; }\n.document-title { color: #1e3a8a; }`}
                        rows={3}
                        hint="Safe CSS rules applied specifically when this layout renders. Do not include <style> tags."
                    />
                </div>

                <div className="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                    <Button variant="secondary" onClick={onClose} disabled={saving}>
                        Cancel
                    </Button>
                    <Button type="submit" loading={saving}>
                        {isEdit ? 'Save Changes' : 'Create Layout'}
                    </Button>
                </div>
            </form>
        </Drawer>
    );
}
