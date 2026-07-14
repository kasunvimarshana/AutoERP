import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { useApi } from './useApi';

interface CounterResource {
    count: number;
}

function ApiStateHarness({ request }: { request: (signal: AbortSignal) => Promise<CounterResource> }) {
    const result = useApi(request, []);

    return (
        <div>
            <span>{result.loading ? 'Loading' : `Count ${result.data?.count ?? 0}`}</span>
            <button
                type="button"
                onClick={() => result.setData((current) => current
                    ? { ...current, count: current.count + 1 }
                    : current)}
            >
                Increment
            </button>
        </div>
    );
}

describe('useApi data ownership', () => {
    it('supports atomic functional updates against the latest API state', async () => {
        const request = vi.fn(async () => ({ count: 1 }));

        render(<ApiStateHarness request={request} />);

        await waitFor(() => expect(screen.getByText('Count 1')).toBeInTheDocument());
        fireEvent.click(screen.getByRole('button', { name: 'Increment' }));

        expect(screen.getByText('Count 2')).toBeInTheDocument();
        expect(request).toHaveBeenCalledOnce();
    });
});
