/**
 * AutoERP Enterprise System
 *
 * @author Kasun Vimarshana <kasunvmail@gmail.com>
 *
 * Phone: +94713525253
 */

import type { CSSProperties } from 'react';
import type { ChequePrintPayment, ChequeTemplate } from './chequePrintTypes';

function fieldStyle(
    template: ChequeTemplate,
    x: string | number | null | undefined,
    y: string | number | null | undefined,
    isActive = false,
): CSSProperties {
    return {
        left: `${x ?? 0}mm`,
        top: `${y ?? 0}mm`,
        fontSize: `${template.font_size}pt`,
        fontFamily: template.font_family || 'Arial, sans-serif',
        outline: isActive ? '2px dashed #2563eb' : undefined,
        outlineOffset: isActive ? '2px' : undefined,
        backgroundColor: isActive ? 'rgba(37, 99, 235, 0.08)' : undefined,
    };
}

export interface ChequePreviewCanvasProps {
    payment: ChequePrintPayment;
    template: ChequeTemplate;
    showGrid?: boolean;
    activeField?: string | null;
    onSelectField?: (fieldKey: string) => void;
}

export function ChequePreviewCanvas({
    payment,
    template,
    showGrid = false,
    activeField = null,
    onSelectField,
}: ChequePreviewCanvasProps) {
    const meta = template.metadata ?? {};
    const dateLetterSpacing = Number(meta.date_letter_spacing ?? 0);
    const dateDigits = payment.date_digits ?? [];

    const crossingType = (meta.crossing_type ?? payment.crossing_type ?? 'ac_payee') as string;
    const showCrossing = crossingType !== 'none';
    const crossingText = payment.crossing_text || (crossingType === 'not_negotiable' ? 'NOT NEGOTIABLE' : 'A/C PAYEE ONLY');
    const crossingX = meta.crossing_x_mm ?? 15;
    const crossingY = meta.crossing_y_mm ?? 10;

    const showBearerStrike = meta.bearer_strike_through ?? payment.show_bearer_strike ?? true;
    const bearerX = meta.bearer_x_mm ?? (Number(template.payee_x_mm || 25) + 130);
    const bearerY = meta.bearer_y_mm ?? (Number(template.payee_y_mm || 34) + 2);

    const wordsLine1 = payment.amount_words_line_1 ?? payment.amount_in_words;
    const wordsLine2 = payment.amount_words_line_2 ?? '';
    const line2X = meta.amount_words_line_2_x_mm ?? template.amount_words_x_mm;
    const line2Y = meta.amount_words_line_2_y_mm ?? (Number(template.amount_words_y_mm || 55) + 7);

    const memoX = meta.memo_x_mm;
    const memoY = meta.memo_y_mm;

    return (
        <>
            <style>{`@media print { @page { size: ${template.page_width_mm}mm ${template.page_height_mm}mm; margin: 0; } }`}</style>
            <div
                className={`cheque-page ${showGrid ? 'cheque-grid-active' : ''}`}
                style={{
                    width: `${template.page_width_mm}mm`,
                    height: `${template.page_height_mm}mm`,
                    position: 'relative',
                    backgroundImage: showGrid
                        ? 'linear-gradient(to right, rgba(148, 163, 184, 0.25) 1px, transparent 1px), linear-gradient(to bottom, rgba(148, 163, 184, 0.25) 1px, transparent 1px)'
                        : undefined,
                    backgroundSize: showGrid ? '10mm 10mm' : undefined,
                }}
                aria-label={`Cheque preview for ${payment.payment_number}`}
            >
                {/* Payee Crossing */}
                {showCrossing && (
                    <div
                        className="cheque-field cursor-pointer transition-colors"
                        style={{
                            left: `${crossingX}mm`,
                            top: `${crossingY}mm`,
                            width: '32mm',
                            height: '18mm',
                            borderTop: '1.5px solid #000',
                            borderBottom: '1.5px solid #000',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            textAlign: 'center',
                            fontSize: '8pt',
                            fontWeight: 700,
                            letterSpacing: '0.5px',
                            transform: 'rotate(-12deg)',
                            outline: activeField === 'crossing' ? '2px dashed #2563eb' : undefined,
                        }}
                        onClick={() => onSelectField?.('crossing')}
                    >
                        {crossingText}
                    </div>
                )}

                {/* Date */}
                <div
                    className="cheque-field whitespace-nowrap cursor-pointer"
                    style={fieldStyle(template, template.date_x_mm, template.date_y_mm, activeField === 'date')}
                    onClick={() => onSelectField?.('date')}
                >
                    {dateLetterSpacing > 0 && dateDigits.length > 0 ? (
                        <div className="flex" style={{ gap: `${dateLetterSpacing}mm` }}>
                            {dateDigits.map((digit, idx) => (
                                <span key={idx} className="inline-block w-[4.5mm] text-center font-mono">
                                    {digit}
                                </span>
                            ))}
                        </div>
                    ) : (
                        <span>{payment.formatted_cheque_date ?? payment.cheque_date ?? ''}</span>
                    )}
                </div>

                {/* Payee Name */}
                <div
                    className="cheque-field whitespace-nowrap cursor-pointer font-medium"
                    style={fieldStyle(template, template.payee_x_mm, template.payee_y_mm, activeField === 'payee')}
                    onClick={() => onSelectField?.('payee')}
                >
                    {payment.payee_name}
                </div>

                {/* Bearer Strike */}
                {showBearerStrike && (
                    <div
                        className="cheque-field cursor-pointer"
                        style={{
                            left: `${bearerX}mm`,
                            top: `${bearerY}mm`,
                            width: '25mm',
                            height: '1.5px',
                            backgroundColor: '#000',
                            outline: activeField === 'bearer' ? '2px dashed #2563eb' : undefined,
                        }}
                        onClick={() => onSelectField?.('bearer')}
                    />
                )}

                {/* Numeric Amount */}
                <div
                    className="cheque-field whitespace-nowrap tabular-nums cursor-pointer font-bold"
                    style={fieldStyle(template, template.amount_x_mm, template.amount_y_mm, activeField === 'amount')}
                    onClick={() => onSelectField?.('amount')}
                >
                    {payment.formatted_amount ?? payment.amount}
                </div>

                {/* Amount in words (Line 1) */}
                <div
                    className="cheque-field leading-snug cursor-pointer"
                    style={{
                        ...fieldStyle(template, template.amount_words_x_mm, template.amount_words_y_mm, activeField === 'amount_words'),
                        maxWidth: `calc(${template.page_width_mm}mm - ${template.amount_words_x_mm}mm - 6mm)`,
                    }}
                    onClick={() => onSelectField?.('amount_words')}
                >
                    {wordsLine1}
                </div>

                {/* Amount in words (Line 2 if present) */}
                {wordsLine2 && (
                    <div
                        className="cheque-field leading-snug cursor-pointer"
                        style={{
                            ...fieldStyle(template, line2X, line2Y, activeField === 'amount_words_line_2'),
                            maxWidth: `calc(${template.page_width_mm}mm - ${line2X}mm - 6mm)`,
                        }}
                        onClick={() => onSelectField?.('amount_words_line_2')}
                    >
                        {wordsLine2}
                    </div>
                )}

                {/* Cheque Number */}
                {payment.cheque_number && template.cheque_number_x_mm != null && template.cheque_number_y_mm != null && (
                    <div
                        className="cheque-field whitespace-nowrap cursor-pointer"
                        style={fieldStyle(template, template.cheque_number_x_mm, template.cheque_number_y_mm, activeField === 'cheque_number')}
                        onClick={() => onSelectField?.('cheque_number')}
                    >
                        {payment.cheque_number}
                    </div>
                )}

                {/* Memo */}
                {memoX != null && memoY != null && (
                    <div
                        className="cheque-field whitespace-nowrap cursor-pointer text-xs"
                        style={fieldStyle(template, memoX, memoY, activeField === 'memo')}
                        onClick={() => onSelectField?.('memo')}
                    >
                        {payment.memo_text ?? payment.payment_number}
                    </div>
                )}
            </div>
        </>
    );
}
