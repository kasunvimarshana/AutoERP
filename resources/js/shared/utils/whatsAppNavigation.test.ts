import { describe, expect, it, vi } from 'vitest';
import { navigateToWhatsApp } from './whatsAppNavigation';

describe('navigateToWhatsApp', () => {
    it('navigates a prepared window only to wa.me', () => {
        const target = {
            opener: window,
            location: { href: '' },
            close: vi.fn(),
        } as unknown as Window;

        expect(navigateToWhatsApp('https://wa.me/94771234567?text=Invoice', target)).toBe(true);
        expect(target.opener).toBeNull();
        expect(target.location.href).toBe('https://wa.me/94771234567?text=Invoice');
        expect(target.close).not.toHaveBeenCalled();
    });

    it('rejects an untrusted external URL and closes the prepared window', () => {
        const target = {
            opener: window,
            location: { href: '' },
            close: vi.fn(),
        } as unknown as Window;

        expect(navigateToWhatsApp('https://example.test/not-whatsapp', target)).toBe(false);
        expect(target.close).toHaveBeenCalledOnce();
    });
});
