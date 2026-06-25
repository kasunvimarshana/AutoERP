import { afterEach, describe, expect, it, vi } from 'vitest';
import { openSameOriginUrl, resolveSameOriginUrl } from './safeNavigation';

afterEach(() => vi.restoreAllMocks());

describe('safe navigation', () => {
    it('resolves same-origin relative and absolute URLs', () => {
        expect(resolveSameOriginUrl('/vouchers/10/print')).toBe(`${window.location.origin}/vouchers/10/print`);
        expect(resolveSameOriginUrl(`${window.location.origin}/reports/1`)).toBe(`${window.location.origin}/reports/1`);
    });

    it('rejects external and executable URLs', () => {
        expect(resolveSameOriginUrl('https://example.com/steal')).toBeNull();
        expect(resolveSameOriginUrl('javascript:alert(1)')).toBeNull();
        expect(resolveSameOriginUrl('')).toBeNull();
    });

    it('opens only validated URLs with opener isolation', () => {
        const open = vi.spyOn(window, 'open').mockImplementation(() => null);

        expect(openSameOriginUrl('/reports/1')).toBe(true);
        expect(open).toHaveBeenCalledWith(`${window.location.origin}/reports/1`, '_blank', 'noopener,noreferrer');
        expect(openSameOriginUrl('https://example.com')).toBe(false);
        expect(open).toHaveBeenCalledTimes(1);
    });
});
