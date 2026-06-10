import type { CSSProperties } from 'react';
import type { ChequePrintPayment, ChequeTemplate } from './chequePrintTypes';

function fieldStyle(template: ChequeTemplate, x: string | null | undefined, y: string | null | undefined): CSSProperties {
    return {
        left: `${x ?? 0}mm`,
        top: `${y ?? 0}mm`,
        fontSize: `${template.font_size}pt`,
        fontFamily: template.font_family || 'Arial, sans-serif',
    };
}

export function ChequePreviewCanvas({ payment, template }: {
    payment: ChequePrintPayment;
    template: ChequeTemplate;
}) {
    return (
        <>
            <style>{`@media print { @page { size: ${template.page_width_mm}mm ${template.page_height_mm}mm; margin: 0; } }`}</style>
            <div
                className="cheque-page"
                style={{
                    width: `${template.page_width_mm}mm`,
                    height: `${template.page_height_mm}mm`,
                }}
                aria-label={`Cheque preview for ${payment.payment_number}`}
            >
                <span className="cheque-field whitespace-nowrap" style={fieldStyle(template, template.date_x_mm, template.date_y_mm)}>
                    {payment.formatted_cheque_date ?? payment.cheque_date ?? ''}
                </span>
                <span className="cheque-field whitespace-nowrap" style={fieldStyle(template, template.payee_x_mm, template.payee_y_mm)}>
                    {payment.payee_name}
                </span>
                <span className="cheque-field whitespace-nowrap tabular-nums" style={fieldStyle(template, template.amount_x_mm, template.amount_y_mm)}>
                    {payment.amount}
                </span>
                <span
                    className="cheque-field leading-snug"
                    style={{
                        ...fieldStyle(template, template.amount_words_x_mm, template.amount_words_y_mm),
                        maxWidth: `calc(${template.page_width_mm}mm - ${template.amount_words_x_mm}mm - 5mm)`,
                    }}
                >
                    {payment.amount_in_words}
                </span>
                {payment.cheque_number && template.cheque_number_x_mm != null && template.cheque_number_y_mm != null && (
                    <span className="cheque-field whitespace-nowrap" style={fieldStyle(template, template.cheque_number_x_mm, template.cheque_number_y_mm)}>
                        {payment.cheque_number}
                    </span>
                )}
            </div>
        </>
    );
}
