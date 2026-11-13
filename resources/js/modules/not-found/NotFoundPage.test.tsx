import { render, screen } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import { describe, expect, it, vi } from 'vitest';
import { DASHBOARD_PATH, PLATFORM_HOME_PATH } from '@/app/routePaths';
import NotFoundPage from './NotFoundPage';

const mockAuthState = vi.hoisted(() => ({
    isPlatformOperator: false,
}));

vi.mock('@/modules/auth/AuthProvider', () => ({
    useAuth: () => mockAuthState,
}));

describe('NotFoundPage', () => {
    it('links to Dashboard for standard tenant users', () => {
        mockAuthState.isPlatformOperator = false;

        render(
            <BrowserRouter>
                <NotFoundPage />
            </BrowserRouter>,
        );

        const returnLink = screen.getByRole('link', { name: 'Return to Dashboard' });
        expect(returnLink).toHaveAttribute('href', DASHBOARD_PATH);
    });

    it('links to Platform Administration for platform operators', () => {
        mockAuthState.isPlatformOperator = true;

        render(
            <BrowserRouter>
                <NotFoundPage />
            </BrowserRouter>,
        );

        const returnLink = screen.getByRole('link', { name: 'Return to Platform Administration' });
        expect(returnLink).toHaveAttribute('href', PLATFORM_HOME_PATH);
    });
});
