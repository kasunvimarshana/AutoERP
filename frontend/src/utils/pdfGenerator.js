/**
 * PDF Generation Utilities
 * Provides functions to generate and download PDFs for various documents
 */

/**
 * Generate PDF invoice
 * @param {Object} invoice - Invoice data
 * @returns {Promise<Blob>} PDF blob
 */
export async function generateInvoicePDF(invoice) {
  // Create HTML template for invoice
  const html = `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <title>Invoice ${invoice.invoice_number}</title>
      <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; padding: 40px; color: #333; }
        .header { display: flex; justify-content: space-between; margin-bottom: 40px; border-bottom: 2px solid #2563eb; padding-bottom: 20px; }
        .company-info { flex: 1; }
        .company-name { font-size: 24px; font-weight: bold; color: #2563eb; margin-bottom: 5px; }
        .invoice-info { text-align: right; }
        .invoice-number { font-size: 20px; font-weight: bold; color: #2563eb; }
        .customer-info { margin-bottom: 30px; }
        .section-title { font-size: 14px; font-weight: bold; color: #666; margin-bottom: 10px; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #f3f4f6; padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e5e7eb; }
        td { padding: 12px; border-bottom: 1px solid #e5e7eb; }
        .text-right { text-align: right; }
        .totals { margin-top: 20px; }
        .totals-row { display: flex; justify-content: flex-end; margin: 8px 0; }
        .totals-label { width: 150px; font-weight: 600; }
        .totals-value { width: 150px; text-align: right; }
        .grand-total { font-size: 18px; color: #2563eb; padding-top: 10px; border-top: 2px solid #2563eb; }
        .footer { margin-top: 50px; padding-top: 20px; border-top: 1px solid #e5e7eb; text-align: center; color: #666; font-size: 12px; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-overdue { background: #fee2e2; color: #991b1b; }
      </style>
    </head>
    <body>
      <div class="header">
        <div class="company-info">
          <div class="company-name">${invoice.company_name || 'AutoERP'}</div>
          <div>${invoice.company_address || ''}</div>
          <div>${invoice.company_phone || ''}</div>
          <div>${invoice.company_email || ''}</div>
        </div>
        <div class="invoice-info">
          <div class="invoice-number">INVOICE</div>
          <div style="font-size: 16px; margin: 5px 0;">#${invoice.invoice_number}</div>
          <div style="margin-top: 10px;">
            <span class="status-badge status-${invoice.status.toLowerCase()}">
              ${invoice.status.toUpperCase()}
            </span>
          </div>
        </div>
      </div>

      <div class="customer-info">
        <div class="section-title">Bill To</div>
        <div style="font-weight: 600; font-size: 16px; margin-bottom: 5px;">${invoice.customer_name || ''}</div>
        <div>${invoice.customer_address || ''}</div>
        <div>${invoice.customer_email || ''}</div>
        <div>${invoice.customer_phone || ''}</div>
      </div>

      <div style="display: flex; justify-content: space-between; margin: 30px 0;">
        <div>
          <div style="color: #666; font-size: 12px;">Invoice Date</div>
          <div style="font-weight: 600;">${formatDate(invoice.invoice_date)}</div>
        </div>
        <div>
          <div style="color: #666; font-size: 12px;">Due Date</div>
          <div style="font-weight: 600;">${formatDate(invoice.due_date)}</div>
        </div>
        <div>
          <div style="color: #666; font-size: 12px;">Payment Terms</div>
          <div style="font-weight: 600;">${invoice.payment_terms || 'Net 30'}</div>
        </div>
      </div>

      <table>
        <thead>
          <tr>
            <th style="width: 50%;">Item</th>
            <th class="text-right">Quantity</th>
            <th class="text-right">Unit Price</th>
            <th class="text-right">Tax</th>
            <th class="text-right">Total</th>
          </tr>
        </thead>
        <tbody>
          ${invoice.items?.map(item => `
            <tr>
              <td>
                <div style="font-weight: 600;">${item.product_name || item.name}</div>
                ${item.description ? `<div style="font-size: 12px; color: #666; margin-top: 4px;">${item.description}</div>` : ''}
              </td>
              <td class="text-right">${item.quantity}</td>
              <td class="text-right">${formatCurrency(item.unit_price)}</td>
              <td class="text-right">${formatCurrency(item.tax_amount || 0)}</td>
              <td class="text-right">${formatCurrency(item.total)}</td>
            </tr>
          `).join('') || ''}
        </tbody>
      </table>

      <div class="totals">
        <div class="totals-row">
          <div class="totals-label">Subtotal:</div>
          <div class="totals-value">${formatCurrency(invoice.subtotal)}</div>
        </div>
        ${invoice.discount_amount ? `
        <div class="totals-row">
          <div class="totals-label">Discount:</div>
          <div class="totals-value">-${formatCurrency(invoice.discount_amount)}</div>
        </div>
        ` : ''}
        <div class="totals-row">
          <div class="totals-label">Tax:</div>
          <div class="totals-value">${formatCurrency(invoice.tax_amount)}</div>
        </div>
        <div class="totals-row grand-total">
          <div class="totals-label">Total:</div>
          <div class="totals-value">${formatCurrency(invoice.total_amount)}</div>
        </div>
        ${invoice.paid_amount ? `
        <div class="totals-row" style="margin-top: 15px;">
          <div class="totals-label">Paid:</div>
          <div class="totals-value" style="color: #059669;">${formatCurrency(invoice.paid_amount)}</div>
        </div>
        <div class="totals-row">
          <div class="totals-label">Balance Due:</div>
          <div class="totals-value" style="color: #dc2626;">${formatCurrency(invoice.total_amount - invoice.paid_amount)}</div>
        </div>
        ` : ''}
      </div>

      ${invoice.notes ? `
      <div style="margin-top: 40px;">
        <div class="section-title">Notes</div>
        <div style="padding: 15px; background: #f9fafb; border-radius: 8px;">
          ${invoice.notes}
        </div>
      </div>
      ` : ''}

      ${invoice.terms ? `
      <div style="margin-top: 30px;">
        <div class="section-title">Terms & Conditions</div>
        <div style="font-size: 12px; color: #666; line-height: 1.6;">
          ${invoice.terms}
        </div>
      </div>
      ` : ''}

      <div class="footer">
        <div>Thank you for your business!</div>
        <div style="margin-top: 5px;">This is a computer-generated invoice and does not require a signature.</div>
      </div>
    </body>
    </html>
  `;

  // Use browser print to generate PDF
  return new Promise((resolve, reject) => {
    try {
      const printWindow = window.open('', '_blank');
      printWindow.document.write(html);
      printWindow.document.close();
      
      printWindow.onload = () => {
        printWindow.focus();
        printWindow.print();
        
        // Note: This doesn't actually return a Blob since we're using window.print()
        // For actual PDF generation, you would need a library like jsPDF or pdfmake
        resolve(null);
      };
    } catch (error) {
      reject(error);
    }
  });
}

/**
 * Generate receipt PDF
 * @param {Object} payment - Payment data
 * @returns {Promise<Blob>} PDF blob
 */
export async function generateReceiptPDF(payment) {
  const html = `
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <title>Receipt ${payment.receipt_number || payment.id}</title>
      <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; padding: 40px; color: #333; max-width: 600px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #2563eb; }
        .title { font-size: 28px; font-weight: bold; color: #2563eb; margin-bottom: 10px; }
        .receipt-number { font-size: 18px; color: #666; }
        .info-row { display: flex; justify-content: space-between; margin: 15px 0; padding: 15px; background: #f9fafb; border-radius: 8px; }
        .info-label { color: #666; font-size: 14px; }
        .info-value { font-weight: 600; font-size: 16px; }
        .amount-paid { text-align: center; padding: 30px; margin: 30px 0; background: #dbeafe; border-radius: 12px; }
        .amount-label { color: #1e40af; font-size: 16px; margin-bottom: 10px; }
        .amount-value { font-size: 36px; font-weight: bold; color: #1e40af; }
        .footer { margin-top: 50px; padding-top: 20px; border-top: 1px solid #e5e7eb; text-align: center; color: #666; font-size: 12px; }
      </style>
    </head>
    <body>
      <div class="header">
        <div class="title">PAYMENT RECEIPT</div>
        <div class="receipt-number">#${payment.receipt_number || payment.id}</div>
      </div>

      <div class="info-row">
        <div>
          <div class="info-label">Date</div>
          <div class="info-value">${formatDate(payment.payment_date)}</div>
        </div>
        <div>
          <div class="info-label">Payment Method</div>
          <div class="info-value">${payment.payment_method}</div>
        </div>
      </div>

      <div class="info-row">
        <div>
          <div class="info-label">Customer</div>
          <div class="info-value">${payment.customer_name || 'N/A'}</div>
        </div>
      </div>

      ${payment.reference ? `
      <div class="info-row">
        <div>
          <div class="info-label">Reference</div>
          <div class="info-value">${payment.reference}</div>
        </div>
      </div>
      ` : ''}

      <div class="amount-paid">
        <div class="amount-label">Amount Paid</div>
        <div class="amount-value">${formatCurrency(payment.amount)}</div>
      </div>

      ${payment.notes ? `
      <div style="padding: 20px; background: #f9fafb; border-radius: 8px; margin: 20px 0;">
        <div style="font-weight: 600; margin-bottom: 10px;">Notes</div>
        <div style="color: #666;">${payment.notes}</div>
      </div>
      ` : ''}

      <div class="footer">
        <div>Thank you for your payment!</div>
        <div style="margin-top: 5px;">This receipt was generated on ${formatDate(new Date())}</div>
      </div>
    </body>
    </html>
  `;

  return new Promise((resolve, reject) => {
    try {
      const printWindow = window.open('', '_blank');
      printWindow.document.write(html);
      printWindow.document.close();
      
      printWindow.onload = () => {
        printWindow.focus();
        printWindow.print();
        resolve(null);
      };
    } catch (error) {
      reject(error);
    }
  });
}

/**
 * Format date for display
 */
function formatDate(date) {
  if (!date) return 'N/A';
  
  const d = new Date(date);
  if (isNaN(d.getTime())) return 'Invalid Date';
  
  return d.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
}

/**
 * Format currency
 */
function formatCurrency(amount, currency = 'USD') {
  if (amount === null || amount === undefined) return '$0.00';
  
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency,
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount);
}

export default {
  generateInvoicePDF,
  generateReceiptPDF
}
