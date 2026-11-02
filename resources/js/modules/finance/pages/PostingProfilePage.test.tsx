import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { TestRouter } from '@/test/TestRouter';
import PostingProfilePage from './PostingProfilePage';

const authMock = vi.hoisted(() => ({
    useAuth: vi.fn(),
}));

const apiMocks = vi.hoisted(() => ({
    listPostingProfiles: vi.fn(),
    createPostingProfile: vi.fn(),
    updatePostingProfile: vi.fn(),
    listAccountRoles: vi.fn(),
    createAccountRole: vi.fn(),
    updateAccountRole: vi.fn(),
    listAccountAssignments: vi.fn(),
    createAccountAssignment: vi.fn(),
    endAccountAssignment: vi.fn(),
    getFinanceLookups: vi.fn(),
    simulatePostingProfile: vi.fn(),
}));

vi.mock('@/modules/auth/AuthProvider', () => authMock);
vi.mock('../financeApi', () => apiMocks);

describe('PostingProfilePage', () => {
    const mockProfiles = [
        {
            id: 1,
            code: 'sales_invoice',
            name: 'Sales Invoice',
            description: 'Customer sales invoice blueprint',
            is_active: true,
            row_version: 1,
            organization_unit_id: null,
            scope: 'tenant_default',
            rules: [
                {
                    id: 101,
                    line_key: 'receivable',
                    account_role_id: 10,
                    effective_from: '1900-01-01',
                    effective_to: null,
                    is_active: true,
                    role: { id: 10, code: 'receivable', name: 'Receivable', is_active: true },
                },
                {
                    id: 102,
                    line_key: 'revenue',
                    account_role_id: 11,
                    effective_from: '1900-01-01',
                    effective_to: null,
                    is_active: true,
                    role: { id: 11, code: 'sales_revenue', name: 'Sales Revenue', is_active: true },
                },
            ],
        },
    ];

    const mockRoles = [
        { id: 10, code: 'receivable', name: 'Receivable', is_active: true },
        { id: 11, code: 'sales_revenue', name: 'Sales Revenue', is_active: true },
        { id: 12, code: 'unmapped_role', name: 'Unmapped Role', is_active: true },
    ];

    const mockAssignments = [
        {
            id: 501,
            account_role_id: 10,
            account_id: 201,
            organization_unit_id: null,
            effective_from: '1900-01-01',
            effective_to: null,
            is_active: true,
            role: { id: 10, code: 'receivable', name: 'Receivable', is_active: true },
            account: { id: 201, code: '1100', name: 'Accounts Receivable', normal_balance: 'debit', is_posting_account: true, is_active: true },
        },
        {
            id: 502,
            account_role_id: 11,
            account_id: 202,
            organization_unit_id: null,
            effective_from: '1900-01-01',
            effective_to: null,
            is_active: true,
            role: { id: 11, code: 'sales_revenue', name: 'Sales Revenue', is_active: true },
            account: { id: 202, code: '4100', name: 'Sales Revenue', normal_balance: 'credit', is_posting_account: true, is_active: true },
        },
    ];

    const mockLookups = {
        types: [],
        categories: [],
        accounts: [
            { id: 201, code: '1100', name: 'Accounts Receivable', normal_balance: 'debit', is_posting_account: true, is_active: true },
            { id: 202, code: '4100', name: 'Sales Revenue', normal_balance: 'credit', is_posting_account: true, is_active: true },
        ],
        profiles: mockProfiles,
        account_roles: mockRoles,
        account_assignments: mockAssignments,
    };

    beforeEach(() => {
        vi.clearAllMocks();
        authMock.useAuth.mockReturnValue({
            user: { id: 1, name: 'Finance Admin' },
            organizationUnit: null,
            roles: ['super admin'],
            permissions: [],
            permissionsLoaded: true,
        });

        apiMocks.listPostingProfiles.mockResolvedValue({ data: mockProfiles, meta: {} });
        apiMocks.listAccountRoles.mockResolvedValue({ data: mockRoles, meta: {} });
        apiMocks.listAccountAssignments.mockResolvedValue({ data: mockAssignments, meta: {} });
        apiMocks.getFinanceLookups.mockResolvedValue(mockLookups);
    });

    it('renders the header, statistics, and architecture pipeline banner', async () => {
        render(
            <TestRouter initialEntries={['/finance/posting-profiles']}>
                <PostingProfilePage />
            </TestRouter>,
        );

        expect(await screen.findByRole('heading', { name: 'Finance Posting Configuration' })).toBeInTheDocument();
        expect(screen.getByText('The 3-Tier Finance Posting Pipeline')).toBeInTheDocument();
        expect(screen.getByText('Ready for Posting')).toBeInTheDocument();
        expect(screen.getAllByText('Sales Invoice').length).toBeGreaterThan(0);
    });

    it('allows switching between tabs and inspects roles and assignments', async () => {
        const user = userEvent.setup();
        render(
            <TestRouter initialEntries={['/finance/posting-profiles']}>
                <PostingProfilePage />
            </TestRouter>,
        );

        await screen.findByRole('heading', { name: 'Finance Posting Configuration' });

        // Switch to Account Roles tab
        const rolesTab = screen.getByRole('tab', { name: /Account Roles/ });
        await user.click(rolesTab);
        expect(await screen.findByText('Account Roles Catalog')).toBeInTheDocument();
        expect(screen.getAllByText('receivable').length).toBeGreaterThan(0);

        // Switch to Effective Assignments tab
        const assignmentsTab = screen.getByRole('tab', { name: /Effective Assignments/ });
        await user.click(assignmentsTab);
        expect(await screen.findByText('Effective Account Assignments')).toBeInTheDocument();
        expect(screen.getAllByText('1100 - Accounts Receivable').length).toBeGreaterThan(0);

        // Switch to Architecture Guide tab
        const guideTab = screen.getByRole('tab', { name: /Architecture Guide/ });
        await user.click(guideTab);
        expect(await screen.findByText('Finance Posting Architecture & User Guide')).toBeInTheDocument();
    });

    it('loads a canonical preset template when creating a posting profile', async () => {
        const user = userEvent.setup();
        render(
            <TestRouter initialEntries={['/finance/posting-profiles']}>
                <PostingProfilePage />
            </TestRouter>,
        );

        await screen.findByRole('heading', { name: 'Finance Posting Configuration' });

        // Locate template select
        const selects = screen.getAllByRole('combobox');
        const templateSelect = selects.find((s) => s.querySelector('option[value="customer_receipt"]'))!;
        expect(templateSelect).toBeDefined();
        await user.selectOptions(templateSelect, 'customer_receipt');

        // Should populate code and name
        expect(screen.getByDisplayValue('customer_receipt')).toBeInTheDocument();
        expect(screen.getByDisplayValue('Customer Receipt')).toBeInTheDocument();
    });

    it('runs posting profile simulation and displays trace results', async () => {
        const user = userEvent.setup();
        apiMocks.simulatePostingProfile.mockResolvedValue({
            profile_id: 1,
            profile_code: 'sales_invoice',
            profile_name: 'Sales Invoice',
            posting_date: '2026-11-02',
            organization_unit_id: null,
            is_ready: true,
            lines: [
                {
                    line_key: 'receivable',
                    account_role_id: 10,
                    role: { id: 10, code: 'receivable', name: 'Receivable', is_active: true },
                    status: 'resolved',
                    resolved_account: {
                        id: 201,
                        code: '1100',
                        name: 'Accounts Receivable',
                        normal_balance: 'debit',
                        scope: 'tenant_default',
                        organization_unit_id: null,
                    },
                    message: null,
                },
            ],
        });

        render(
            <TestRouter initialEntries={['/finance/posting-profiles']}>
                <PostingProfilePage />
            </TestRouter>,
        );

        await screen.findByRole('heading', { name: 'Finance Posting Configuration' });

        // Switch to Simulator tab
        const simulatorTab = screen.getByRole('tab', { name: /Posting Health & Resolver/ });
        await user.click(simulatorTab);

        expect(await screen.findByText('Live Resolution Simulator & Trace Engine')).toBeInTheDocument();

        const selects = screen.getAllByRole('combobox');
        const profileSelect = selects.find((s) => s.querySelector('option[value="1"]'))!;
        expect(profileSelect).toBeDefined();
        await user.selectOptions(profileSelect, '1');

        const simulateBtn = screen.getByRole('button', { name: 'Simulate Resolution' });
        await user.click(simulateBtn);

        await waitFor(() => {
            expect(apiMocks.simulatePostingProfile).toHaveBeenCalledWith(1, expect.objectContaining({
                posting_date: expect.any(String),
            }));
        });

        expect(await screen.findByText('Ready to Post')).toBeInTheDocument();
        expect(screen.getAllByText('1100 - Accounts Receivable').length).toBeGreaterThan(0);
    });
});
