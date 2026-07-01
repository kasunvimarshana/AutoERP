import { describe, expect, it, vi } from 'vitest';
import { isCentralPlatformHost, workspaceLoginUrl } from './platformHost';

describe('isCentralPlatformHost', () => {
    it('does not block loopback tenant login when local fallback is enabled for a built frontend', () => {
        vi.stubEnv('DEV', false);
        vi.stubEnv('VITE_PLATFORM_PUBLIC_URL', 'http://127.0.0.1:8000');
        vi.stubEnv('VITE_TENANT_LOCAL_FALLBACK_ENABLED', 'true');

        expect(isCentralPlatformHost('127.0.0.1:8000', '127.0.0.1')).toBe(false);

        vi.unstubAllEnvs();
    });

    it('detects the configured platform host when local fallback is not enabled', () => {
        vi.stubEnv('DEV', false);
        vi.stubEnv('VITE_PLATFORM_PUBLIC_URL', 'https://platform.example.test');
        vi.stubEnv('VITE_TENANT_LOCAL_FALLBACK_ENABLED', 'false');

        expect(isCentralPlatformHost('platform.example.test', 'platform.example.test')).toBe(true);

        vi.unstubAllEnvs();
    });
});

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
