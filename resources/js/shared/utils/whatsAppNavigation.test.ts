import { describe, expect, it, vi } from 'vitest';
import { confirmPendingWhatsAppShare, navigateToWhatsApp } from './whatsAppNavigation';

describe('navigateToWhatsApp', () => {
    it('shows confirmation in the prepared WhatsApp tab', () => {
        const target = {
            confirm: vi.fn().mockReturnValue(true),
        } as unknown as Window;

        expect(confirmPendingWhatsAppShare(target, 'Confirm share')).toBe(true);
        expect(target.confirm).toHaveBeenCalledWith('Confirm share');
    });

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
