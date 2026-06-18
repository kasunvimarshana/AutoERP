import { Link, MemoryRouter, useLocation } from 'react-router-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { useUnsavedChanges } from './useUnsavedChanges';

describe('useUnsavedChanges', () => {
    it('protects refreshes and internal link navigation', async () => {
        window.history.pushState({}, '', '/edit');
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

    it('does not confirm when only the tab query changes on the same page', async () => {
        window.history.pushState({}, '', '/purchase/orders/create?tab=details');
        const confirm = vi.spyOn(window, 'confirm').mockReturnValue(false);
        render(
            <MemoryRouter initialEntries={['/purchase/orders/create?tab=details']}>
                <UnsavedTabHarness />
            </MemoryRouter>,
        );

        await userEvent.click(screen.getByRole('link', { name: 'Lines tab' }));

        expect(confirm).not.toHaveBeenCalled();
        expect(window.location.pathname).toBe('/purchase/orders/create');
        expect(screen.getByText('?tab=lines')).toBeInTheDocument();

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

function UnsavedTabHarness() {
    const location = useLocation();
    useUnsavedChanges(true);
    return (
        <div>
            <p>{location.search}</p>
            <Link to="/purchase/orders/create?tab=lines">Lines tab</Link>
        </div>
    );
}
