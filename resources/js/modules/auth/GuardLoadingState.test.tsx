import { act, render, screen } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { GuardLoadingState, GUARD_LOADING_DELAY_MS } from './GuardLoadingState';

describe('GuardLoadingState', () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('delays the full-page guard message to avoid quick access-check flashes', () => {
        render(<GuardLoadingState label="Checking access..." />);

        expect(screen.queryByRole('status')).not.toBeInTheDocument();

        act(() => {
            vi.advanceTimersByTime(GUARD_LOADING_DELAY_MS - 1);
        });
        expect(screen.queryByRole('status')).not.toBeInTheDocument();

        act(() => {
            vi.advanceTimersByTime(1);
        });
        expect(screen.getByRole('status')).toHaveTextContent('Checking access...');
    });
});
