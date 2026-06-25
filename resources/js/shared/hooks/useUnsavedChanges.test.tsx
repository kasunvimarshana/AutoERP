import { Link, useLocation } from 'react-router-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { TestRouter } from '@/test/TestRouter';
import { useUnsavedChanges } from './useUnsavedChanges';

describe('useUnsavedChanges', () => {
    it('protects refreshes and internal navigation', async () => {
        const confirm = vi.mocked(window.confirm).mockReturnValue(false);
        render(
            <TestRouter initialEntries={['/edit']}>
                <UnsavedHarness />
            </TestRouter>,
        );

        const unload = new Event('beforeunload', { cancelable: true });
        window.dispatchEvent(unload);
        expect(unload.defaultPrevented).toBe(true);

        await userEvent.click(screen.getByRole('link', { name: 'Leave page' }));
        expect(confirm).toHaveBeenCalledOnce();
        expect(screen.getByText('Editing')).toBeInTheDocument();

    });

    it('does not confirm when only the tab query changes on the same page', async () => {
        const confirm = vi.mocked(window.confirm).mockReturnValue(false);
        render(
            <TestRouter initialEntries={['/purchase/orders/create?tab=details']}>
                <UnsavedTabHarness />
            </TestRouter>,
        );

        await userEvent.click(screen.getByRole('link', { name: 'Lines tab' }));

        expect(confirm).not.toHaveBeenCalled();
        expect(screen.getByText('?tab=lines')).toBeInTheDocument();

    });
});

function UnsavedHarness() {
    useUnsavedChanges(true);
    return (
        <div>
            <p>Editing</p>
            <Link to="/other">Leave page</Link>
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
