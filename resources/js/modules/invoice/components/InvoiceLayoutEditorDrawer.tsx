/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { useEffect, useState, type FormEvent } from 'react';
import { Drawer } from '@/shared/components/Drawer';
import { Button } from '@/shared/components/Button';
import { Input } from '@/shared/components/Input';
import { Select } from '@/shared/components/Select';
import { Textarea } from '@/shared/components/Textarea';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { createInvoiceLayout, updateInvoiceLayout } from '../invoiceLayoutApi';
import type {
    CreateInvoiceLayoutPayload,
    InvoiceLayout,
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
    { value: 'invoice.layouts.standard', label: 'Classic Standard (Traditional bordered invoice)' },
    { value: 'invoice.layouts.modern', label: 'Modern Clean (Contemporary card-style accent headers)' },
    { value: 'invoice.layouts.compact', label: 'Compact Slip (Condensed half-sheet format)' },
    { value: 'invoice.layouts.thermal', label: 'Thermal Receipt (Continuous 80mm POS slip)' },
    { value: 'invoice.layouts.continuous', label: 'Continuous Feed (Pre-printed or carbonized dot-matrix)' },
];

export function InvoiceLayoutEditorDrawer({
    open,
    layout,
    onClose,
    onSaved,
}: InvoiceLayoutEditorDrawerProps) {
    const isEdit = layout !== null;
    const isSystem = layout?.is_system ?? false;

    const [name, setName] = useState('');
    const [code, setCode] = useState('');
    const [description, setDescription] = useState('');
    const [paperSize, setPaperSize] = useState<InvoicePaperSize>('a4');
    const [orientation, setOrientation] = useState<InvoiceOrientation>('portrait');
    const [viewTemplate, setViewTemplate] = useState('invoice.layouts.standard');
    const [isActive, setIsActive] = useState(true);
    const [customCss, setCustomCss] = useState('');
    const [headerContent, setHeaderContent] = useState('');
    const [footerContent, setFooterContent] = useState('');

    // Settings
    const [showLogo, setShowLogo] = useState(true);
    const [showTaxBreakdown, setShowTaxBreakdown] = useState(true);
    const [showPaymentStub, setShowPaymentStub] = useState(true);
    const [showSignatures, setShowSignatures] = useState(true);
    const [showQrCode, setShowQrCode] = useState(false);
    const [notes, setNotes] = useState('');
    const [terms, setTerms] = useState('');

    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);

    useEffect(() => {
        if (!open) return;

        if (layout) {
            setName(layout.name);
            setCode(layout.code);
            setDescription(layout.description ?? '');
            setPaperSize(layout.paper_size);
            setOrientation(layout.orientation);
            setViewTemplate(layout.view_template);
            setIsActive(layout.is_active);
            setCustomCss(layout.custom_css ?? '');
            setHeaderContent(layout.header_content ?? '');
            setFooterContent(layout.footer_content ?? '');
            setShowLogo(layout.settings?.show_logo ?? true);
            setShowTaxBreakdown(layout.settings?.show_tax_breakdown ?? true);
            setShowPaymentStub(layout.settings?.show_payment_stub ?? true);
            setShowSignatures(layout.settings?.show_signatures ?? true);
            setShowQrCode(layout.settings?.show_qr_code ?? false);
            setNotes(layout.settings?.notes ?? '');
            setTerms(layout.settings?.terms ?? '');
        } else {
            setName('');
            setCode('');
            setDescription('');
            setPaperSize('a4');
            setOrientation('portrait');
            setViewTemplate('invoice.layouts.modern');
            setIsActive(true);
            setCustomCss('');
            setHeaderContent('');
            setFooterContent('');
            setShowLogo(true);
            setShowTaxBreakdown(true);
            setShowPaymentStub(true);
            setShowSignatures(true);
            setShowQrCode(false);
            setNotes('');
            setTerms('');
        }
        setError(null);
    }, [open, layout]);

    // Recommend paper size / orientation based on selected template
    const handleTemplateChange = (newTemplate: string) => {
        setViewTemplate(newTemplate);
        if (newTemplate === 'invoice.layouts.thermal') {
            setPaperSize('thermal_80mm');
        } else if (newTemplate === 'invoice.layouts.compact') {
            setPaperSize('a5');
        } else if (newTemplate === 'invoice.layouts.continuous') {
            setPaperSize('continuous_8_5_by_5_5');
        }
    };

    const handleSubmit = async (event: FormEvent) => {
        event.preventDefault();
        setSaving(true);
        setError(null);

        const settingsPayload = {
            show_logo: showLogo,
            show_tax_breakdown: showTaxBreakdown,
            show_payment_stub: showPaymentStub,
            show_signatures: showSignatures,
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
                    view_template: viewTemplate,
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
                    view_template: viewTemplate,
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
                        <strong>System Layout:</strong> Core system templates are managed by the application. Changes to code and core layout identifiers are restricted.
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

                {/* Format & Template Options */}
                <div className="space-y-4 border-t border-slate-100 pt-4">
                    <h3 className="text-sm font-semibold text-slate-800">Format & Template</h3>

                    <Select
                        label="Base Template"
                        value={viewTemplate}
                        onChange={(e) => handleTemplateChange(e.target.value)}
                        options={TEMPLATE_OPTIONS}
                        disabled={isSystem}
                    />

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

                {/* Content Elements & Settings */}
                <div className="space-y-4 border-t border-slate-100 pt-4">
                    <h3 className="text-sm font-semibold text-slate-800">Visual Elements</h3>
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
                            Show Payment Stub / Bank Box
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
                                checked={showQrCode}
                                onChange={(e) => setShowQrCode(e.target.checked)}
                                className="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900"
                            />
                            Show QR Verification Code
                        </label>
                    </div>

                    <Textarea
                        label="Standard Notes / Policy"
                        value={notes}
                        onChange={(e) => setNotes(e.target.value)}
                        placeholder="e.g. Goods once sold will not be accepted back after 7 days..."
                        rows={2}
                    />

                    <Textarea
                        label="Payment Terms"
                        value={terms}
                        onChange={(e) => setTerms(e.target.value)}
                        placeholder="e.g. Net 30 days. Please transfer to account 123456789..."
                        rows={2}
                    />
                </div>

                {/* Custom CSS Styling */}
                <div className="space-y-4 border-t border-slate-100 pt-4">
                    <h3 className="text-sm font-semibold text-slate-800">Advanced Custom Styling</h3>
                    <Textarea
                        label="Custom CSS Overrides"
                        value={customCss}
                        onChange={(e) => setCustomCss(e.target.value)}
                        placeholder={`.sheet { font-family: 'Helvetica', sans-serif; }\n.document-title { color: #1e3a8a; }`}
                        rows={4}
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
