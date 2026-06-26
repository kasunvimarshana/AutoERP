import { describe, expect, it, vi } from 'vitest';
import { workspaceLoginUrl } from './platformHost';

describe('workspaceLoginUrl', () => {
    it('creates a focused tenant login URL from a hostname', () => {
        vi.stubEnv('DEV', false);
        expect(workspaceLoginUrl('erp.example.com')).toBe('https://erp.example.com/login');
        vi.unstubAllEnvs();
    });

    it('rejects invalid workspace addresses', () => {
        expect(workspaceLoginUrl('')).toBeNull();
        expect(workspaceLoginUrl('not a host')).toBeNull();
    });
});
