import { MemoryRouter } from 'react-router-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { useUnsavedChanges } from './useUnsavedChanges';

describe('useUnsavedChanges', () => {
    it('protects refreshes and internal link navigation', async () => {
        const confirm = vi.spyOn(window, 'confirm').mockReturnValue(false);
        render(
            <MemoryRouter>
                <UnsavedHarness />
            </MemoryRouter>,
        );

        const unload = new Event('beforeunload', { cancelable: true });
        window.dispatchEvent(unload);
        expect(unload.defaultPrevented).toBe(true);

        await userEvent.click(screen.getByRole('link', { name: 'Leave page' }));
        expect(confirm).toHaveBeenCalledOnce();
        expect(screen.getByText('Editing')).toBeInTheDocument();

        confirm.mockRestore();
    });
});

function UnsavedHarness() {
    useUnsavedChanges(true);
    return (
        <div>
            <p>Editing</p>
            <a href="/other">Leave page</a>
        </div>
    );
}
