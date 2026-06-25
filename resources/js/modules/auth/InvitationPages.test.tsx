import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { TestRouter } from '@/test/TestRouter';
import InitialAdministratorInvitationPage from './InitialAdministratorInvitationPage';
import PlatformOperatorInvitationPage from './PlatformOperatorInvitationPage';

const mocks = vi.hoisted(() => ({
    inspectInitialAdministratorInvitation: vi.fn(),
    acceptInitialAdministratorInvitation: vi.fn(),
    inspectPlatformOperatorInvitation: vi.fn(),
    acceptPlatformOperatorInvitation: vi.fn(),
}));

vi.mock('./authApi', () => ({
    authApi: mocks,
}));

const initialAdministratorToken = 'a'.repeat(64);
const platformOperatorToken = 'B'.repeat(72);

const getPasswordInput = (name: 'password' | 'password_confirmation'): HTMLInputElement => {
    const input = document.querySelector<HTMLInputElement>(`input[name="${name}"]`);

    if (!input) {
        throw new Error(`Expected password input '${name}' to be rendered.`);
    }

    return input;
};

describe('InitialAdministratorInvitationPage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        window.history.replaceState(null, '', `/register/invitation#token=${initialAdministratorToken}`);
        mocks.inspectInitialAdministratorInvitation.mockResolvedValue({
            tenant_name: 'Acme Workshop',
            email: 'a•••@example.test',
            expires_at: '2026-06-26T10:00:00Z',
        });
        mocks.acceptInitialAdministratorInvitation.mockResolvedValue({
            user_id: 25,
            tenant_id: 7,
            email: 'admin@example.test',
        });
    });

    it('validates the secure link and completes recipient-owned account setup', async () => {
        const user = userEvent.setup();
        render(
            <TestRouter initialEntries={['/register/invitation']}>
                <InitialAdministratorInvitationPage />
            </TestRouter>,
        );

        expect(await screen.findByText('Acme Workshop')).toBeInTheDocument();
        expect(mocks.inspectInitialAdministratorInvitation).toHaveBeenCalledWith(
            initialAdministratorToken,
            expect.any(AbortSignal),
        );

        await user.type(screen.getByLabelText('First name'), 'Kasun');
        await user.type(screen.getByLabelText('Last name'), 'Admin');
        await user.type(getPasswordInput('password'), 'StrongPassword!123');
        await user.type(getPasswordInput('password_confirmation'), 'StrongPassword!123');
        await user.click(screen.getByRole('button', { name: 'Create administrator account' }));

        await waitFor(() => expect(mocks.acceptInitialAdministratorInvitation).toHaveBeenCalledWith({
            token: initialAdministratorToken,
            first_name: 'Kasun',
            last_name: 'Admin',
            password: 'StrongPassword!123',
            password_confirmation: 'StrongPassword!123',
        }));
        expect(await screen.findByText('Administrator account created')).toBeInTheDocument();
        expect(window.location.hash).toBe('');
    });

    it('does not call the API for a malformed link token', async () => {
        window.history.replaceState(null, '', '/register/invitation#token=invalid');
        render(
            <TestRouter initialEntries={['/register/invitation']}>
                <InitialAdministratorInvitationPage />
            </TestRouter>,
        );

        expect(await screen.findByText(/invitation link is incomplete/i)).toBeInTheDocument();
        expect(mocks.inspectInitialAdministratorInvitation).not.toHaveBeenCalled();
    });
});

describe('PlatformOperatorInvitationPage', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        window.history.replaceState(null, '', `/register/platform-operator#token=${platformOperatorToken}`);
        mocks.inspectPlatformOperatorInvitation.mockResolvedValue({
            operator_name: 'Platform Operator',
            email: 'operator@example.test',
            expires_at: '2026-06-26T10:00:00Z',
            delivery_status: 'sent',
        });
        mocks.acceptPlatformOperatorInvitation.mockResolvedValue({
            operator_name: 'Platform Operator',
            email: 'operator@example.test',
            status: 'active',
        });
    });

    it('lets the invited operator choose a password without exposing administrative credentials', async () => {
        const user = userEvent.setup();
        render(
            <TestRouter initialEntries={['/register/platform-operator']}>
                <PlatformOperatorInvitationPage />
            </TestRouter>,
        );

        expect(await screen.findByText('Platform Operator')).toBeInTheDocument();
        expect(mocks.inspectPlatformOperatorInvitation).toHaveBeenCalledWith(
            platformOperatorToken,
            expect.any(AbortSignal),
        );

        await user.type(getPasswordInput('password'), 'AnotherStrongPassword!456');
        await user.type(getPasswordInput('password_confirmation'), 'AnotherStrongPassword!456');
        await user.click(screen.getByRole('button', { name: 'Activate platform operator account' }));

        await waitFor(() => expect(mocks.acceptPlatformOperatorInvitation).toHaveBeenCalledWith({
            token: platformOperatorToken,
            password: 'AnotherStrongPassword!456',
            password_confirmation: 'AnotherStrongPassword!456',
        }));
        expect(await screen.findByText('Platform operator account activated')).toBeInTheDocument();
        expect(window.location.hash).toBe('');
    });
});
