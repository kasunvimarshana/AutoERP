/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import { useEffect, useState, useRef } from 'react';
import { Modal } from '@/shared/components/Modal';
import { Button } from '@/shared/components/Button';
import { LoadingState } from '@/shared/components/LoadingState';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { toApiError, type ApiError } from '@/shared/api/apiError';
import { previewInvoiceLayout, getInvoiceBladeExportUrl } from '../invoiceLayoutApi';
import type { InvoiceLayout } from '../invoiceLayoutTypes';

interface InvoiceLayoutPreviewModalProps {
    open: boolean;
    layout: InvoiceLayout | null;
    invoiceId?: number;
    onClose: () => void;
}

export function InvoiceLayoutPreviewModal({
    open,
    layout,
    invoiceId,
    onClose,
}: InvoiceLayoutPreviewModalProps) {
    const [html, setHtml] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<ApiError | null>(null);
    const iframeRef = useRef<HTMLIFrameElement>(null);

    useEffect(() => {
        if (!open || !layout) {
            setHtml(null);
            setError(null);
            return;
        }

        const controller = new AbortController();
        setLoading(true);
        setError(null);

        previewInvoiceLayout(layout.code, invoiceId, controller.signal)
            .then((renderedHtml) => {
                setHtml(renderedHtml);
            })
            .catch((caught: unknown) => {
                if (controller.signal.aborted) return;
                setError(toApiError(caught));
            })
            .finally(() => {
                if (!controller.signal.aborted) {
                    setLoading(false);
                }
            });

        return () => {
            controller.abort();
        };
    }, [open, layout, invoiceId]);

    const handlePrint = () => {
        if (iframeRef.current?.contentWindow) {
            iframeRef.current.contentWindow.focus();
            iframeRef.current.contentWindow.print();
        }
    };

    if (!layout) return null;

    return (
        <Modal
            open={open}
            title={`Preview: ${layout.name} (${layout.paper_size_label})`}
            onClose={onClose}
        >
            <div className="space-y-4">
                <div className="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
                    <div className="flex items-center gap-2 text-xs text-slate-500">
                        <span className="font-semibold text-slate-700">Code:</span> {layout.code}
                        <span className="text-slate-300">|</span>
                        <span className="font-semibold text-slate-700">Orientation:</span> {layout.orientation}
                        <span className="text-slate-300">|</span>
                        <span className="font-semibold text-slate-700">Type:</span> {layout.template_type === 'blade' ? 'Custom Blade (.blade.php)' : layout.is_system ? 'System Preset' : 'Custom Preset'}
                    </div>
                    <div className="flex items-center gap-2">
                        {layout.template_type === 'blade' && (
                            <a
                                href={getInvoiceBladeExportUrl(layout.id || layout.code)}
                                download={`${layout.code}.blade.php`}
                                className="inline-flex items-center rounded border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 shadow-xs hover:bg-slate-50"
                            >
                                Download .blade.php
                            </a>
                        )}
                        <Button
                            variant="secondary"
                            size="sm"
                            onClick={handlePrint}
                            disabled={loading || !html}
                        >
                            Print Preview
                        </Button>
                    </div>
                </div>

                <ErrorAlert error={error} />

                {loading ? (
                    <div className="flex min-h-[400px] items-center justify-center">
                        <LoadingState label="Rendering invoice layout preview…" />
                    </div>
                ) : html ? (
                    <div className="relative overflow-hidden rounded-lg border border-slate-200 bg-slate-100 shadow-inner">
                        <iframe
                            ref={iframeRef}
                            title="Invoice Layout Preview"
                            srcDoc={html}
                            sandbox="allow-same-origin allow-modals allow-popups"
                            className="h-[600px] w-full border-0 bg-white"
                        />
                    </div>
                ) : null}

                <div className="flex justify-end pt-2">
                    <Button variant="secondary" onClick={onClose}>
                        Close
                    </Button>
                </div>
            </div>
        </Modal>
    );
}
