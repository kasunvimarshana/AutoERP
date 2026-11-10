import { describe, it, expect, vi, beforeEach } from 'vitest';
import { exportReportBlob } from './reportExport';
import { apiClient } from '@/shared/api/apiClient';

vi.mock('@/shared/api/apiClient', () => ({
    apiClient: {
        get: vi.fn(),
    },
}));

describe('exportReportBlob', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        globalThis.URL.createObjectURL = vi.fn().mockReturnValue('blob:http://localhost/test-blob');
        globalThis.URL.revokeObjectURL = vi.fn();
    });

    it('replaces location of preview window when format is print', async () => {
        const fakeBlob = new Blob(['<html><body>Report</body></html>'], { type: 'text/html' });
        vi.mocked(apiClient.get).mockResolvedValue({
            data: fakeBlob,
            headers: { 'content-type': 'text/html' },
        } as never);

        const mockWindow = {
            closed: false,
            opener: null,
            location: { replace: vi.fn() },
            close: vi.fn(),
        } as unknown as Window;

        await exportReportBlob({
            url: '/api/v1/test/export/print',
            format: 'print',
            previewWindow: mockWindow,
        });

        expect(apiClient.get).toHaveBeenCalledWith('/api/v1/test/export/print', {
            params: undefined,
            responseType: 'blob',
        });
        expect(mockWindow.location.replace).toHaveBeenCalledWith('blob:http://localhost/test-blob');
    });

    it('triggers a download for pdf format', async () => {
        const fakeBlob = new Blob(['%PDF-1.4...'], { type: 'application/pdf' });
        vi.mocked(apiClient.get).mockResolvedValue({
            data: fakeBlob,
            headers: { 'content-type': 'application/pdf' },
        } as never);

        const clickSpy = vi.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(() => {});

        await exportReportBlob({
            url: '/api/v1/test/export/pdf',
            format: 'pdf',
            filename: 'custom-report.pdf',
        });

        expect(clickSpy).toHaveBeenCalled();
        clickSpy.mockRestore();
    });

    it('closes preview window and rethrows on api error', async () => {
        const error = new Error('Network error');
        vi.mocked(apiClient.get).mockRejectedValue(error);

        const mockWindow = {
            closed: false,
            opener: null,
            location: { replace: vi.fn() },
            close: vi.fn(),
        } as unknown as Window;

        await expect(exportReportBlob({
            url: '/api/v1/test/export/print',
            format: 'print',
            previewWindow: mockWindow,
        })).rejects.toThrow('Network error');

        expect(mockWindow.close).toHaveBeenCalled();
    });
});
