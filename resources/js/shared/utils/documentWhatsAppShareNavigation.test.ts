import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';

function sourceFile(path: string): string {
    return readFileSync(resolve(process.cwd(), path), 'utf8');
}

describe('document WhatsApp share navigation', () => {
    it.each([
        'resources/js/modules/invoice/pages/InvoiceDetailPage.tsx',
        'resources/js/modules/purchase/pages/PurchaseOrderDetailPage.tsx',
    ])('shows unverified confirmation in the pending WhatsApp tab in %s', (path) => {
        const detail = sourceFile(path);

        expect(detail).toContain('const pendingWindow = openPendingWhatsAppWindow()');
        expect(detail).toContain('!confirmPendingWhatsAppShare(pendingWindow,');
        expect(detail).toContain('closePendingWhatsAppWindow(pendingWindow)');
        expect(detail).toContain('navigateToWhatsApp(share.whatsapp_url, pendingWindow)');
    });
});
