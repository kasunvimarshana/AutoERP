import { useMemo, useState } from 'react';
import { hasPermission } from '@/modules/auth/accessControl';
import { useAuth } from '@/modules/auth/AuthProvider';
import { fieldError, toApiError, type ApiError } from '@/shared/api/apiError';
import { Button } from '@/shared/components/Button';
import { ContentHeader } from '@/shared/components/ContentHeader';
import { DataTable, type DataColumn } from '@/shared/components/DataTable';
import { ErrorAlert } from '@/shared/components/ErrorAlert';
import { Input } from '@/shared/components/Input';
import { LoadingState } from '@/shared/components/LoadingState';
import { Modal } from '@/shared/components/Modal';
import { Panel } from '@/shared/components/Panel';
import { Select } from '@/shared/components/Select';
import { StatusBadge } from '@/shared/components/StatusBadge';
import { Tabs, type TabItem } from '@/shared/components/Tabs';
import { useApi } from '@/shared/hooks/useApi';
import {
    createAccountAssignment,
    createAccountRole,
    createPostingProfile,
    endAccountAssignment,
    getFinanceLookups,
    listAccountAssignments,
    listAccountRoles,
    listPostingProfiles,
    simulatePostingProfile,
    updateAccountRole,
    updatePostingProfile,
    type AccountAssignmentPayload,
    type AccountRolePayload,
    type FinanceAccountAssignment,
    type FinanceAccountRole,
    type PostingProfile,
    type PostingProfilePayload,
    type PostingProfileSimulationResult,
} from '../financeApi';
import { financePermissions } from '../financePermissions';

type ActiveTab = 'profiles' | 'roles' | 'assignments' | 'simulator' | 'guide';

interface FormRuleItem {
    id?: number;
    line_key: string;
    account_role_id: number;
    effective_from: string;
    effective_to: string | null;
    is_active: boolean;
    description: string | null;
    is_existing?: boolean;
}

interface ProfileFormState {
    code: string;
    name: string;
    description: string | null;
    is_active: boolean;
    rules: FormRuleItem[];
}

interface StandardProfileTemplate {
    code: string;
    name: string;
    description: string;
    rules: Array<{ line_key: string; role_code: string; description: string }>;
}

const CANONICAL_TEMPLATES: StandardProfileTemplate[] = [
    {
        code: 'sales_invoice',
        name: 'Sales Invoice',
        description: 'Customer sales invoice journal mapping (AR, Sales Revenue, Output Tax, Withholding).',
        rules: [
            { line_key: 'receivable', role_code: 'receivable', description: 'Customer Accounts Receivable' },
            { line_key: 'revenue', role_code: 'sales_revenue', description: 'Sales Revenue' },
            { line_key: 'tax_payable', role_code: 'tax_payable', description: 'Tax / VAT / GST Payable' },
            { line_key: 'withholding_receivable', role_code: 'withholding_receivable', description: 'Withholding Tax Deducted at Source' },
        ],
    },
    {
        code: 'purchase_invoice',
        name: 'Purchase Invoice',
        description: 'Supplier purchase invoice journal mapping (Direct Expense, GRNI clearing, AP, Input Tax).',
        rules: [
            { line_key: 'expense', role_code: 'purchase_expense', description: 'Direct Purchase Expense' },
            { line_key: 'goods_received_not_invoiced', role_code: 'goods_received_not_invoiced', description: 'GRNI Accrual Clearing' },
            { line_key: 'payable', role_code: 'payable', description: 'Supplier Accounts Payable' },
            { line_key: 'tax_receivable', role_code: 'tax_receivable', description: 'Input Tax Recoverable' },
            { line_key: 'withholding_payable', role_code: 'withholding_payable', description: 'Withholding Tax Payable' },
        ],
    },
    {
        code: 'customer_receipt',
        name: 'Customer Receipt',
        description: 'Settlement receipt from customer (Cash/Bank, AR, Advances, FX Gain/Loss).',
        rules: [
            { line_key: 'cash', role_code: 'cash', description: 'Cash on Hand Receipt' },
            { line_key: 'bank', role_code: 'bank', description: 'Bank Account Deposit' },
            { line_key: 'receivable', role_code: 'receivable', description: 'Accounts Receivable Settlement' },
            { line_key: 'customer_advance', role_code: 'customer_advance', description: 'Unallocated Customer Advance' },
            { line_key: 'realized_fx_gain', role_code: 'realized_fx_gain', description: 'Realized FX Currency Gain' },
            { line_key: 'realized_fx_loss', role_code: 'realized_fx_loss', description: 'Realized FX Currency Loss' },
        ],
    },
    {
        code: 'supplier_payment',
        name: 'Supplier Payment',
        description: 'Outbound payment to supplier (AP, Cash/Bank, Advances, FX Gain/Loss).',
        rules: [
            { line_key: 'cash', role_code: 'cash', description: 'Cash on Hand Payment' },
            { line_key: 'bank', role_code: 'bank', description: 'Bank Account Disbursal' },
            { line_key: 'payable', role_code: 'payable', description: 'Accounts Payable Settlement' },
            { line_key: 'supplier_advance', role_code: 'supplier_advance', description: 'Supplier Advance Allocation' },
            { line_key: 'realized_fx_gain', role_code: 'realized_fx_gain', description: 'Realized FX Currency Gain' },
            { line_key: 'realized_fx_loss', role_code: 'realized_fx_loss', description: 'Realized FX Currency Loss' },
        ],
    },
    {
        code: 'customer_advance',
        name: 'Customer Advance Receipt',
        description: 'Prepayment receipt received in advance of delivery or invoicing.',
        rules: [
            { line_key: 'cash', role_code: 'cash', description: 'Cash Received' },
            { line_key: 'bank', role_code: 'bank', description: 'Bank Deposit' },
            { line_key: 'receivable', role_code: 'receivable', description: 'Receivable Offset' },
            { line_key: 'customer_advance', role_code: 'customer_advance', description: 'Customer Advance Liability' },
            { line_key: 'realized_fx_gain', role_code: 'realized_fx_gain', description: 'Realized FX Gain' },
            { line_key: 'realized_fx_loss', role_code: 'realized_fx_loss', description: 'Realized FX Loss' },
        ],
    },
    {
        code: 'supplier_advance',
        name: 'Supplier Advance Payment',
        description: 'Prepayment paid to supplier prior to receiving goods or services.',
        rules: [
            { line_key: 'cash', role_code: 'cash', description: 'Cash Paid' },
            { line_key: 'bank', role_code: 'bank', description: 'Bank Disbursed' },
            { line_key: 'payable', role_code: 'payable', description: 'Payable Offset' },
            { line_key: 'supplier_advance', role_code: 'supplier_advance', description: 'Supplier Advance Asset' },
            { line_key: 'realized_fx_gain', role_code: 'realized_fx_gain', description: 'Realized FX Gain' },
            { line_key: 'realized_fx_loss', role_code: 'realized_fx_loss', description: 'Realized FX Loss' },
        ],
    },
    {
        code: 'inventory_receipt',
        name: 'Inventory Goods Receipt',
        description: 'Stock intake posting (Inventory Asset debit vs GRNI accrual credit).',
        rules: [
            { line_key: 'inventory', role_code: 'inventory', description: 'Inventory On-Hand Asset' },
            { line_key: 'goods_received_not_invoiced', role_code: 'goods_received_not_invoiced', description: 'GRNI Accrual Clearing' },
        ],
    },
    {
        code: 'inventory_issue',
        name: 'Inventory Issue / COGS',
        description: 'Cost of goods sold recognition when inventory is shipped to customers.',
        rules: [
            { line_key: 'cost_of_goods_sold', role_code: 'cost_of_goods_sold', description: 'Cost of Goods Sold Expense' },
            { line_key: 'inventory', role_code: 'inventory', description: 'Inventory Asset Reduction' },
        ],
    },
    {
        code: 'internal_consumption',
        name: 'Internal Consumable Usage',
        description: 'Consumable parts or items used internally for operations or maintenance.',
        rules: [
            { line_key: 'operating_expense', role_code: 'operating_expense', description: 'Internal Operating Expense' },
            { line_key: 'inventory', role_code: 'inventory', description: 'Inventory Asset Reduction' },
        ],
    },
    {
        code: 'expense_payment',
        name: 'Expense Direct Payment',
        description: 'Direct operational expense payment from cash or bank.',
        rules: [
            { line_key: 'operating_expense', role_code: 'operating_expense', description: 'Operating Expense Debit' },
            { line_key: 'cash', role_code: 'cash', description: 'Cash on Hand Disbursal' },
            { line_key: 'bank', role_code: 'bank', description: 'Bank Account Disbursal' },
        ],
    },
    {
        code: 'rental_deposit',
        name: 'Rental Security Deposit',
        description: 'Security deposit received from customer or refunded.',
        rules: [
            { line_key: 'cash', role_code: 'cash', description: 'Cash on Hand' },
            { line_key: 'bank', role_code: 'bank', description: 'Bank Account' },
            { line_key: 'receivable', role_code: 'receivable', description: 'Receivable Offset' },
            { line_key: 'customer_deposit', role_code: 'customer_deposit', description: 'Customer Security Deposit Liability' },
            { line_key: 'realized_fx_gain', role_code: 'realized_fx_gain', description: 'Realized FX Gain' },
            { line_key: 'realized_fx_loss', role_code: 'realized_fx_loss', description: 'Realized FX Loss' },
        ],
    },
    {
        code: 'vehicle_service_invoice',
        name: 'Vehicle Service Invoice',
        description: 'Service workshop invoice posting (AR, Service Revenue, Tax).',
        rules: [
            { line_key: 'receivable', role_code: 'receivable', description: 'Customer Accounts Receivable' },
            { line_key: 'service_revenue', role_code: 'service_revenue', description: 'Vehicle Service Revenue' },
            { line_key: 'tax_payable', role_code: 'tax_payable', description: 'Service Tax Payable' },
        ],
    },
    {
        code: 'sales_return',
        name: 'Sales Return',
        description: 'Credit note return journal mapping.',
        rules: [
            { line_key: 'sales_revenue', role_code: 'sales_revenue', description: 'Sales Revenue Reversal' },
            { line_key: 'receivable', role_code: 'receivable', description: 'Accounts Receivable Credit' },
        ],
    },
    {
        code: 'purchase_return',
        name: 'Purchase Return',
        description: 'Debit note return journal mapping to supplier.',
        rules: [
            { line_key: 'payable', role_code: 'payable', description: 'Supplier Accounts Payable Debit' },
            { line_key: 'purchase_expense', role_code: 'purchase_expense', description: 'Purchase Expense Reversal' },
        ],
    },
];

const COMMON_LINE_KEYS = [
    'receivable',
    'revenue',
    'sales_revenue',
    'service_revenue',
    'tax_payable',
    'withholding_receivable',
    'expense',
    'purchase_expense',
    'goods_received_not_invoiced',
    'payable',
    'tax_receivable',
    'withholding_payable',
    'cash',
    'bank',
    'customer_advance',
    'supplier_advance',
    'customer_deposit',
    'realized_fx_gain',
    'realized_fx_loss',
    'inventory',
    'cost_of_goods_sold',
    'operating_expense',
];

const emptyProfile = (): ProfileFormState => ({
    code: '',
    name: '',
    description: null,
    is_active: true,
    rules: [emptyProfileRule(), emptyProfileRule()],
});

const emptyRole = (): AccountRolePayload => ({
    code: '',
    name: '',
    description: null,
    is_active: true,
});

const emptyAssignment = (): AccountAssignmentPayload => ({
    account_role_id: 0,
    account_id: 0,
    effective_from: todayDate(),
    effective_to: null,
});

function emptyProfileRule(): FormRuleItem {
    return {
        line_key: '',
        account_role_id: 0,
        effective_from: '1900-01-01',
        effective_to: null,
        is_active: true,
        description: null,
        is_existing: false,
    };
}

export default function PostingProfilePage() {
    const auth = useAuth();
    const canManage = hasPermission(auth, financePermissions.postingProfilesManage);
    const canViewAccounts = hasPermission(auth, financePermissions.accountsView);
    const hasOrganizationContext = auth.organizationUnit !== null;

    const [activeTab, setActiveTab] = useState<ActiveTab>('profiles');
    const [guideDismissed, setGuideDismissed] = useState(false);

    // Filter states
    const [profileSearch, setProfileSearch] = useState('');
    const [profileScopeFilter, setProfileScopeFilter] = useState<'all' | 'tenant' | 'organization'>('all');
    const [profileStatusFilter, setProfileStatusFilter] = useState<'all' | 'active' | 'inactive'>('all');

    const [roleSearch, setRoleSearch] = useState('');
    const [roleStatusFilter, setRoleStatusFilter] = useState<'all' | 'active' | 'inactive'>('all');

    const [assignmentSearch, setAssignmentSearch] = useState('');
    const [assignmentScopeFilter, setAssignmentScopeFilter] = useState<'all' | 'tenant' | 'organization'>('all');
    const [assignmentStatusFilter, setAssignmentStatusFilter] = useState<'all' | 'active' | 'upcoming' | 'expired'>('all');

    // Selection & Editing
    const [selectedProfile, setSelectedProfile] = useState<PostingProfile | null>(null);
    const [profileForm, setProfileForm] = useState<ProfileFormState>(emptyProfile);
    const [isReadOnlyProfileView, setIsReadOnlyProfileView] = useState(false);

    const [selectedRole, setSelectedRole] = useState<FinanceAccountRole | null>(null);
    const [roleForm, setRoleForm] = useState<AccountRolePayload>(emptyRole);

    const [assignmentForm, setAssignmentForm] = useState<AccountAssignmentPayload>(emptyAssignment);
    const [endingAssignment, setEndingAssignment] = useState<FinanceAccountAssignment | null>(null);
    const [endingDate, setEndingDate] = useState(todayDate());

    // Simulation & Live Testing
    const [simulationProfileId, setSimulationProfileId] = useState<number | null>(null);
    const [simulationDate, setSimulationDate] = useState(todayDate());
    const [simulationResult, setSimulationResult] = useState<PostingProfileSimulationResult | null>(null);
    const [simulating, setSimulating] = useState(false);
    const [simulationError, setSimulationError] = useState<ApiError | null>(null);

    // Saving & Errors
    const [savingProfile, setSavingProfile] = useState(false);
    const [savingRole, setSavingRole] = useState(false);
    const [savingAssignment, setSavingAssignment] = useState(false);
    const [profileError, setProfileError] = useState<ApiError | null>(null);
    const [roleError, setRoleError] = useState<ApiError | null>(null);
    const [assignmentError, setAssignmentError] = useState<ApiError | null>(null);

    // Queries
    const profiles = useApi((signal) => listPostingProfiles({ per_page: 100 }, signal), []);
    const roles = useApi((signal) => listAccountRoles({ per_page: 100 }, signal), []);
    const assignments = useApi((signal) => listAccountAssignments({ per_page: 100 }, signal), []);
    const lookups = useApi((signal) => getFinanceLookups(signal), [], canViewAccounts);

    const activeRoles = useMemo(
        () => (roles.data?.data ?? lookups.data?.account_roles ?? []).filter((role) => role.is_active),
        [lookups.data?.account_roles, roles.data?.data],
    );

    const activeAccounts = useMemo(
        () => (lookups.data?.accounts ?? []).filter((account) => account.is_active && account.is_posting_account),
        [lookups.data?.accounts],
    );

    const allAssignmentsList = useMemo(
        () => assignments.data?.data ?? lookups.data?.account_assignments ?? [],
        [assignments.data?.data, lookups.data?.account_assignments],
    );

    const allProfilesList = useMemo(
        () => profiles.data?.data ?? lookups.data?.profiles ?? [],
        [profiles.data?.data, lookups.data?.profiles],
    );

    const allRolesList = useMemo(
        () => roles.data?.data ?? lookups.data?.account_roles ?? [],
        [roles.data?.data, lookups.data?.account_roles],
    );

    const isInherited = (organizationUnitId?: number | null) => hasOrganizationContext && organizationUnitId == null;

    const scopeLabel = (organizationUnitId?: number | null, orgUnit?: { name: string; code: string } | null) => {
        if (organizationUnitId) {
            return orgUnit ? `${orgUnit.name} (${orgUnit.code})` : `Org #${organizationUnitId}`;
        }
        return hasOrganizationContext ? 'Tenant default (fallback)' : 'Tenant default';
    };

    const getAssignmentStatus = (from: string, to?: string | null): 'active' | 'upcoming' | 'expired' => {
        const today = todayDate();
        if (from > today) return 'upcoming';
        if (to && to < today) return 'expired';
        return 'active';
    };

    // Role usage counts
    const roleUsageStats = useMemo(() => {
        const profileCountMap = new Map<number, number>();
        for (const p of allProfilesList) {
            for (const r of p.rules ?? []) {
                const roleId = Number(r.role?.id ?? r.account_role_id);
                if (roleId > 0) {
                    profileCountMap.set(roleId, (profileCountMap.get(roleId) ?? 0) + 1);
                }
            }
        }

        const assignmentMap = new Map<number, FinanceAccountAssignment>();
        for (const a of allAssignmentsList) {
            if (getAssignmentStatus(a.effective_from, a.effective_to) === 'active') {
                if (!assignmentMap.has(a.account_role_id) || a.organization_unit_id !== null) {
                    assignmentMap.set(a.account_role_id, a);
                }
            }
        }

        return { profileCountMap, assignmentMap };
    }, [allProfilesList, allAssignmentsList]);

    // Health Checklist: which roles in active profiles lack an active assignment?
    const healthAudit = useMemo(() => {
        const issues: Array<{
            profileId: number;
            profileCode: string;
            profileName: string;
            lineKey: string;
            roleId: number;
            roleCode: string;
            roleName: string;
            issue: string;
        }> = [];

        const checkedRoleIds = new Set<number>();

        for (const profile of allProfilesList) {
            if (!profile.is_active) continue;
            for (const rule of profile.rules ?? []) {
                if (!rule.is_active) continue;
                const roleId = Number(rule.role?.id ?? rule.account_role_id);
                if (!roleId) continue;

                const role = allRolesList.find((r) => r.id === roleId) ?? rule.role;
                const activeAssignment = roleUsageStats.assignmentMap.get(roleId);

                if (!role || !role.is_active) {
                    issues.push({
                        profileId: profile.id,
                        profileCode: profile.code,
                        profileName: profile.name,
                        lineKey: rule.line_key,
                        roleId,
                        roleCode: role?.code ?? 'unknown',
                        roleName: role?.name ?? 'Unknown role',
                        issue: 'Target account role is inactive or missing',
                    });
                } else if (!activeAssignment) {
                    issues.push({
                        profileId: profile.id,
                        profileCode: profile.code,
                        profileName: profile.name,
                        lineKey: rule.line_key,
                        roleId,
                        roleCode: role.code,
                        roleName: role.name,
                        issue: 'No active GL account assignment for current scope',
                    });
                }
                checkedRoleIds.add(roleId);
            }
        }

        return {
            totalProfilesChecked: allProfilesList.filter((p) => p.is_active).length,
            totalRolesReferenced: checkedRoleIds.size,
            issues,
            isHealthy: issues.length === 0,
        };
    }, [allProfilesList, allRolesList, roleUsageStats.assignmentMap]);

    // Filtered data sets
    const filteredProfiles = useMemo(() => {
        return allProfilesList.filter((p) => {
            const matchesSearch = !profileSearch.trim() ||
                p.code.toLowerCase().includes(profileSearch.toLowerCase()) ||
                p.name.toLowerCase().includes(profileSearch.toLowerCase()) ||
                (typeof p.description === 'string' && p.description.toLowerCase().includes(profileSearch.toLowerCase()));

            const matchesScope = profileScopeFilter === 'all'
                ? true
                : profileScopeFilter === 'tenant'
                    ? p.organization_unit_id === null
                    : p.organization_unit_id !== null;

            const matchesStatus = profileStatusFilter === 'all'
                ? true
                : profileStatusFilter === 'active'
                    ? Boolean(p.is_active)
                    : !p.is_active;

            return matchesSearch && matchesScope && matchesStatus;
        });
    }, [allProfilesList, profileSearch, profileScopeFilter, profileStatusFilter]);

    const filteredRoles = useMemo(() => {
        return allRolesList.filter((r) => {
            const matchesSearch = !roleSearch.trim() ||
                r.code.toLowerCase().includes(roleSearch.toLowerCase()) ||
                r.name.toLowerCase().includes(roleSearch.toLowerCase()) ||
                (typeof r.description === 'string' && r.description.toLowerCase().includes(roleSearch.toLowerCase()));

            const matchesStatus = roleStatusFilter === 'all'
                ? true
                : roleStatusFilter === 'active'
                    ? Boolean(r.is_active)
                    : !r.is_active;

            return matchesSearch && matchesStatus;
        });
    }, [allRolesList, roleSearch, roleStatusFilter]);

    const filteredAssignments = useMemo(() => {
        return allAssignmentsList.filter((a) => {
            const roleText = a.role ? `${a.role.code} ${a.role.name}` : String(a.account_role_id);
            const accountText = a.account ? `${a.account.code} ${a.account.name}` : String(a.account_id);
            const matchesSearch = !assignmentSearch.trim() ||
                roleText.toLowerCase().includes(assignmentSearch.toLowerCase()) ||
                accountText.toLowerCase().includes(assignmentSearch.toLowerCase());

            const matchesScope = assignmentScopeFilter === 'all'
                ? true
                : assignmentScopeFilter === 'tenant'
                    ? a.organization_unit_id === null
                    : a.organization_unit_id !== null;

            const status = getAssignmentStatus(a.effective_from, a.effective_to);
            const matchesStatus = assignmentStatusFilter === 'all'
                ? true
                : assignmentStatusFilter === status;

            return matchesSearch && matchesScope && matchesStatus;
        });
    }, [allAssignmentsList, assignmentSearch, assignmentScopeFilter, assignmentStatusFilter]);

    // Data table columns
    const profileColumns: DataColumn<PostingProfile>[] = [
        {
            key: 'code',
            header: 'Profile Blueprint',
            render: (row) => {
                const inherited = isInherited(row.organization_unit_id);
                return (
                    <div className="space-y-0.5">
                        <button
                            type="button"
                            className="text-left font-semibold text-sky-700 hover:underline"
                            onClick={() => viewOrEditProfile(row)}
                        >
                            {row.name}
                        </button>
                        <div className="flex items-center gap-1.5 font-mono text-xs text-slate-500">
                            <span>{row.code}</span>
                            {inherited && (
                                <span className="rounded bg-amber-50 px-1 text-[11px] font-medium text-amber-700 border border-amber-200">
                                    Fallback
                                </span>
                            )}
                        </div>
                    </div>
                );
            },
        },
        {
            key: 'scope',
            header: 'Scope',
            render: (row) => (
                <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${
                    row.organization_unit_id
                        ? 'bg-purple-100 text-purple-800'
                        : 'bg-slate-100 text-slate-700'
                }`}>
                    {scopeLabel(row.organization_unit_id, row.organization_unit)}
                </span>
            ),
        },
        {
            key: 'lines',
            header: 'Rule Lines',
            render: (row) => {
                const count = row.rules?.length ?? 0;
                const activeCount = row.rules?.filter((r) => r.is_active).length ?? 0;
                return (
                    <div className="text-xs text-slate-600">
                        <span className="font-semibold text-slate-800">{activeCount} active</span>
                        {count !== activeCount && <span className="text-slate-400"> ({count} total)</span>}
                    </div>
                );
            },
        },
        {
            key: 'status',
            header: 'Status',
            render: (row) => <StatusBadge status={row.is_active ? 'active' : 'inactive'} />,
        },
        {
            key: 'actions',
            header: 'Actions',
            className: 'text-right',
            render: (row) => {
                const inherited = isInherited(row.organization_unit_id);
                return (
                    <div className="flex justify-end gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={() => {
                                setSimulationProfileId(row.id);
                                setActiveTab('simulator');
                                void runSimulation(row.id, todayDate());
                            }}
                        >
                            Test
                        </Button>
                        <Button
                            type="button"
                            variant={inherited ? 'secondary' : 'primary'}
                            onClick={() => viewOrEditProfile(row)}
                        >
                            {inherited || !canManage ? 'Inspect' : 'Edit'}
                        </Button>
                    </div>
                );
            },
        },
    ];

    const roleColumns: DataColumn<FinanceAccountRole>[] = [
        {
            key: 'code',
            header: 'Account Role',
            render: (row) => (
                <div className="space-y-0.5">
                    {canManage ? (
                        <button type="button" className="text-left font-semibold text-sky-700 hover:underline" onClick={() => editRole(row)}>
                            {row.name}
                        </button>
                    ) : (
                        <span className="font-semibold text-slate-800">{row.name}</span>
                    )}
                    <div className="font-mono text-xs text-slate-500">{row.code}</div>
                    {row.description && <p className="text-xs text-slate-500 line-clamp-1">{row.description}</p>}
                </div>
            ),
        },
        {
            key: 'effective_account',
            header: 'Currently Assigned GL Account',
            render: (row) => {
                const assignment = roleUsageStats.assignmentMap.get(row.id);
                if (!assignment) {
                    return (
                        <span className="inline-flex items-center gap-1 text-xs font-medium text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                            Unassigned
                        </span>
                    );
                }
                const account = assignment.account;
                return (
                    <div className="text-xs">
                        <span className="font-semibold text-slate-800">{account ? `${account.code} - ${account.name}` : `Account #${assignment.account_id}`}</span>
                        <div className="text-[11px] text-slate-500">
                            via {scopeLabel(assignment.organization_unit_id, assignment.organization_unit)}
                        </div>
                    </div>
                );
            },
        },
        {
            key: 'usage',
            header: 'Profiles Using Role',
            render: (row) => {
                const count = roleUsageStats.profileCountMap.get(row.id) ?? 0;
                return (
                    <span className="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">
                        {count} {count === 1 ? 'profile' : 'profiles'}
                    </span>
                );
            },
        },
        {
            key: 'status',
            header: 'Status',
            render: (row) => <StatusBadge status={row.is_active ? 'active' : 'inactive'} />,
        },
        {
            key: 'actions',
            header: 'Actions',
            className: 'text-right',
            render: (row) => canManage ? (
                <Button type="button" variant="secondary" onClick={() => editRole(row)}>Edit</Button>
            ) : null,
        },
    ];

    const assignmentColumns: DataColumn<FinanceAccountAssignment>[] = [
        {
            key: 'role',
            header: 'Account Role',
            render: (row) => (
                <div className="space-y-0.5">
                    <div className="font-semibold text-slate-800">
                        {row.role ? row.role.name : `Role #${row.account_role_id}`}
                    </div>
                    <div className="font-mono text-xs text-slate-500">
                        {row.role ? row.role.code : String(row.account_role_id)}
                    </div>
                </div>
            ),
        },
        {
            key: 'account',
            header: 'Target GL Account',
            render: (row) => (
                <div className="space-y-0.5">
                    <div className="font-semibold text-slate-800">
                        {row.account ? `${row.account.code} - ${row.account.name}` : `Account #${row.account_id}`}
                    </div>
                    {row.account?.normal_balance && (
                        <span className="inline-block rounded bg-slate-100 px-1.5 py-0.2 text-[10px] font-medium uppercase text-slate-600">
                            {row.account.normal_balance}
                        </span>
                    )}
                </div>
            ),
        },
        {
            key: 'scope',
            header: 'Scope',
            render: (row) => (
                <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${
                    row.organization_unit_id
                        ? 'bg-purple-100 text-purple-800'
                        : 'bg-slate-100 text-slate-700'
                }`}>
                    {scopeLabel(row.organization_unit_id, row.organization_unit)}
                </span>
            ),
        },
        {
            key: 'effective_period',
            header: 'Effective Period',
            render: (row) => (
                <div className="text-xs tabular-nums text-slate-700">
                    <div>From: <span className="font-medium">{row.effective_from}</span></div>
                    <div>To: <span className="font-medium text-slate-500">{row.effective_to ?? 'Open ended'}</span></div>
                </div>
            ),
        },
        {
            key: 'status',
            header: 'State',
            render: (row) => {
                const status = getAssignmentStatus(row.effective_from, row.effective_to);
                return (
                    <span className={`inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold ${
                        status === 'active'
                            ? 'bg-emerald-100 text-emerald-800'
                            : status === 'upcoming'
                                ? 'bg-blue-100 text-blue-800'
                                : 'bg-slate-100 text-slate-600'
                    }`}>
                        {status === 'active' ? 'Active Now' : status === 'upcoming' ? 'Scheduled' : 'Expired'}
                    </span>
                );
            },
        },
        {
            key: 'actions',
            header: 'Actions',
            className: 'text-right',
            render: (row) => {
                const inherited = isInherited(row.organization_unit_id);
                const status = getAssignmentStatus(row.effective_from, row.effective_to);
                if (!canManage || inherited || status === 'expired') {
                    return null;
                }
                return (
                    <Button
                        type="button"
                        variant="secondary"
                        onClick={() => {
                            setEndingAssignment(row);
                            setEndingDate(todayDate());
                        }}
                    >
                        End
                    </Button>
                );
            },
        },
    ];

    // Actions & Handlers
    function viewOrEditProfile(profile: PostingProfile) {
        const inherited = isInherited(profile.organization_unit_id);
        setSelectedProfile(profile);
        setIsReadOnlyProfileView(inherited || !canManage);
        setProfileForm({
            code: profile.code,
            name: profile.name,
            description: typeof profile.description === 'string' ? profile.description : null,
            is_active: Boolean(profile.is_active),
            rules: (profile.rules ?? []).map((rule) => ({
                id: rule.id,
                line_key: rule.line_key,
                account_role_id: Number(rule.role?.id ?? rule.account_role_id),
                effective_from: rule.effective_from || '1900-01-01',
                effective_to: rule.effective_to ?? null,
                is_active: Boolean(rule.is_active),
                description: typeof rule.description === 'string' ? rule.description : null,
                is_existing: true,
            })),
        });
        setProfileError(null);
    }

    function editRole(role: FinanceAccountRole) {
        setSelectedRole(role);
        setRoleForm({
            code: role.code,
            name: role.name,
            description: typeof role.description === 'string' ? role.description : null,
            is_active: role.is_active,
        });
        setRoleError(null);
    }

    function resetProfile() {
        setSelectedProfile(null);
        setIsReadOnlyProfileView(false);
        setProfileForm(emptyProfile());
        setProfileError(null);
    }

    function resetRole() {
        setSelectedRole(null);
        setRoleForm(emptyRole());
        setRoleError(null);
    }

    function loadTemplate(template: StandardProfileTemplate) {
        // Map template rules to existing active roles where possible
        const mappedRules: FormRuleItem[] = template.rules.map((rule) => {
            const matchedRole = activeRoles.find((r) => r.code.toLowerCase() === rule.role_code.toLowerCase())
                ?? activeRoles.find((r) => r.code.toLowerCase() === rule.line_key.toLowerCase());
            return {
                line_key: rule.line_key,
                account_role_id: matchedRole ? matchedRole.id : 0,
                effective_from: '1900-01-01',
                effective_to: null,
                is_active: true,
                description: rule.description,
                is_existing: false,
            };
        });

        setProfileForm({
            code: template.code,
            name: template.name,
            description: template.description,
            is_active: true,
            rules: mappedRules,
        });
        setProfileError(null);
    }

    function updateRule(index: number, patch: Partial<FormRuleItem>) {
        setProfileForm((current) => ({
            ...current,
            rules: current.rules.map((rule, ruleIndex) => ruleIndex === index ? { ...rule, ...patch } : rule),
        }));
    }

    function handleRemoveRule(index: number) {
        setProfileForm((current) => {
            const target = current.rules[index];
            if (target?.is_existing) {
                // To maintain accounting audit trail and prevent omission rejection:
                // Existing rules in database must be submitted with is_active=false rather than deleted.
                return {
                    ...current,
                    rules: current.rules.map((r, i) => i === index ? { ...r, is_active: false } : r),
                };
            }
            return {
                ...current,
                rules: current.rules.filter((_, i) => i !== index),
            };
        });
    }

    async function saveProfile() {
        setSavingProfile(true);
        setProfileError(null);
        try {
            // Keep both active rules and deactivated existing rules to comply with audit requirements
            const rules = profileForm.rules
                .filter((rule) => rule.line_key.trim() && rule.account_role_id > 0)
                .map((rule) => ({
                    line_key: rule.line_key.trim(),
                    account_role_id: rule.account_role_id,
                    effective_from: rule.effective_from || '1900-01-01',
                    effective_to: rule.effective_to || null,
                    is_active: Boolean(rule.is_active),
                    description: rule.description || null,
                }));

            const payload: PostingProfilePayload = {
                code: profileForm.code.trim(),
                name: profileForm.name.trim(),
                description: profileForm.description || null,
                is_active: profileForm.is_active,
                rules,
            };

            if (selectedProfile) {
                const versionedPayload = {
                    ...payload,
                    expected_version: selectedProfile.row_version,
                };
                await updatePostingProfile(selectedProfile.id, versionedPayload);
            } else {
                await createPostingProfile(payload);
            }
            resetProfile();
            profiles.reload();
            lookups.reload();
        } catch (requestError) {
            setProfileError(toApiError(requestError));
        } finally {
            setSavingProfile(false);
        }
    }

    async function saveRole() {
        setSavingRole(true);
        setRoleError(null);
        try {
            const payload: AccountRolePayload = {
                code: roleForm.code.trim().toLowerCase(),
                name: roleForm.name.trim(),
                description: roleForm.description || null,
                is_active: roleForm.is_active,
            };
            if (selectedRole) {
                await updateAccountRole(selectedRole.id, payload);
            } else {
                await createAccountRole(payload);
            }
            resetRole();
            roles.reload();
            lookups.reload();
        } catch (requestError) {
            setRoleError(toApiError(requestError));
        } finally {
            setSavingRole(false);
        }
    }

    async function saveAssignment() {
        setSavingAssignment(true);
        setAssignmentError(null);
        try {
            await createAccountAssignment({
                ...assignmentForm,
                effective_to: assignmentForm.effective_to || null,
            });
            setAssignmentForm(emptyAssignment());
            assignments.reload();
            lookups.reload();
        } catch (requestError) {
            setAssignmentError(toApiError(requestError));
        } finally {
            setSavingAssignment(false);
        }
    }

    async function submitEndAssignment() {
        if (!endingAssignment) return;
        setSavingAssignment(true);
        setAssignmentError(null);
        try {
            await endAccountAssignment(endingAssignment.id, endingDate);
            setEndingAssignment(null);
            assignments.reload();
            lookups.reload();
        } catch (requestError) {
            setAssignmentError(toApiError(requestError));
        } finally {
            setSavingAssignment(false);
        }
    }

    async function runSimulation(profileId: number, date: string) {
        setSimulating(true);
        setSimulationError(null);
        try {
            const result = await simulatePostingProfile(profileId, { posting_date: date });
            setSimulationResult(result);
        } catch (err) {
            setSimulationError(toApiError(err));
            setSimulationResult(null);
        } finally {
            setSimulating(false);
        }
    }

    const tabs: TabItem<ActiveTab>[] = [
        {
            id: 'profiles',
            label: (
                <div className="flex items-center gap-2">
                    <span>Posting Profiles</span>
                    <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">
                        {allProfilesList.length}
                    </span>
                </div>
            ),
        },
        {
            id: 'roles',
            label: (
                <div className="flex items-center gap-2">
                    <span>Account Roles</span>
                    <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">
                        {allRolesList.length}
                    </span>
                </div>
            ),
        },
        {
            id: 'assignments',
            label: (
                <div className="flex items-center gap-2">
                    <span>Effective Assignments</span>
                    <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">
                        {allAssignmentsList.length}
                    </span>
                </div>
            ),
        },
        {
            id: 'simulator',
            label: (
                <div className="flex items-center gap-2">
                    <span>Posting Health & Resolver</span>
                    {!healthAudit.isHealthy ? (
                        <span className="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">
                            {healthAudit.issues.length} issue{healthAudit.issues.length === 1 ? '' : 's'}
                        </span>
                    ) : (
                        <span className="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800">
                            Healthy
                        </span>
                    )}
                </div>
            ),
        },
        {
            id: 'guide',
            label: <span>Architecture Guide</span>,
        },
    ];

    return (
        <>
            <ContentHeader
                title="Finance Posting Configuration"
                description="Map business transaction line keys to semantic account roles, then bind roles to effective General Ledger accounts by scope and date."
                actions={
                    <div className="flex gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={() => setGuideDismissed(!guideDismissed)}
                        >
                            {guideDismissed ? 'Show Architecture Guide' : 'Hide Guide'}
                        </Button>
                    </div>
                }
            />

            {/* Quick KPI & Health Bar */}
            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div className="text-xs font-medium uppercase tracking-wider text-slate-500">Active Profiles</div>
                    <div className="mt-1 flex items-baseline justify-between">
                        <span className="text-2xl font-bold text-slate-900">
                            {allProfilesList.filter((p) => p.is_active).length}
                        </span>
                        <span className="text-xs text-slate-500">of {allProfilesList.length} total</span>
                    </div>
                </div>
                <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div className="text-xs font-medium uppercase tracking-wider text-slate-500">Defined Roles</div>
                    <div className="mt-1 flex items-baseline justify-between">
                        <span className="text-2xl font-bold text-slate-900">
                            {allRolesList.filter((r) => r.is_active).length}
                        </span>
                        <span className="text-xs text-slate-500">roles in catalog</span>
                    </div>
                </div>
                <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div className="text-xs font-medium uppercase tracking-wider text-slate-500">Active Assignments</div>
                    <div className="mt-1 flex items-baseline justify-between">
                        <span className="text-2xl font-bold text-slate-900">
                            {allAssignmentsList.filter((a) => getAssignmentStatus(a.effective_from, a.effective_to) === 'active').length}
                        </span>
                        <span className="text-xs text-slate-500">active for today</span>
                    </div>
                </div>
                <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div className="text-xs font-medium uppercase tracking-wider text-slate-500">Configuration Status</div>
                    <div className="mt-1 flex items-center justify-between">
                        {healthAudit.isHealthy ? (
                            <span className="inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-700">
                                <span className="h-2.5 w-2.5 rounded-full bg-emerald-500" />
                                Ready for Posting
                            </span>
                        ) : (
                            <span className="inline-flex items-center gap-1.5 text-sm font-semibold text-amber-700">
                                <span className="h-2.5 w-2.5 rounded-full bg-amber-500 animate-pulse" />
                                {healthAudit.issues.length} Unmapped Line{healthAudit.issues.length === 1 ? '' : 's'}
                            </span>
                        )}
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={() => setActiveTab('simulator')}
                        >
                            Inspect
                        </Button>
                    </div>
                </div>
            </div>

            {/* Architecture Explainer Banner (Collapsible) */}
            {!guideDismissed && (
                <div className="rounded-xl border border-sky-100 bg-gradient-to-r from-sky-50 via-white to-blue-50 p-5 shadow-sm">
                    <div className="flex items-start justify-between">
                        <div>
                            <h3 className="text-base font-semibold text-sky-950">
                                The 3-Tier Finance Posting Pipeline
                            </h3>
                            <p className="mt-1 text-sm text-slate-600">
                                AutoERP strictly decouples operational business transactions from chart-of-accounts bookkeeping to ensure flexibility, audit compliance, and clean multi-branch operations.
                            </p>
                        </div>
                        <Button type="button" variant="ghost" onClick={() => setGuideDismissed(true)}>
                            Dismiss
                        </Button>
                    </div>

                    <div className="mt-4 grid gap-4 md:grid-cols-3">
                        <div className="rounded-lg border border-slate-200 bg-white p-3.5 shadow-xs">
                            <div className="flex items-center gap-2">
                                <span className="flex h-6 w-6 items-center justify-center rounded-full bg-sky-600 text-xs font-bold text-white">1</span>
                                <h4 className="font-semibold text-slate-800">Posting Profiles</h4>
                            </div>
                            <p className="mt-1.5 text-xs text-slate-600">
                                <strong>What:</strong> Blueprints for transaction events (e.g. Sales Invoice).
                            </p>
                            <p className="mt-1 text-xs text-slate-600">
                                <strong>Role:</strong> Maps operational event line keys (e.g. <code className="text-sky-700">receivable</code>, <code className="text-sky-700">revenue</code>) to semantic Account Roles.
                            </p>
                        </div>

                        <div className="rounded-lg border border-slate-200 bg-white p-3.5 shadow-xs">
                            <div className="flex items-center gap-2">
                                <span className="flex h-6 w-6 items-center justify-center rounded-full bg-sky-600 text-xs font-bold text-white">2</span>
                                <h4 className="font-semibold text-slate-800">Account Roles</h4>
                            </div>
                            <p className="mt-1.5 text-xs text-slate-600">
                                <strong>What:</strong> Abstract financial concepts (e.g. <code className="text-sky-700">Receivable</code>, <code className="text-sky-700">Sales Revenue</code>, <code className="text-sky-700">Cash</code>).
                            </p>
                            <p className="mt-1 text-xs text-slate-600">
                                <strong>Role:</strong> The semantic bridge. Ensures invoicing and payments don't hardcode General Ledger account codes.
                            </p>
                        </div>

                        <div className="rounded-lg border border-slate-200 bg-white p-3.5 shadow-xs">
                            <div className="flex items-center gap-2">
                                <span className="flex h-6 w-6 items-center justify-center rounded-full bg-sky-600 text-xs font-bold text-white">3</span>
                                <h4 className="font-semibold text-slate-800">Effective Assignments</h4>
                            </div>
                            <p className="mt-1.5 text-xs text-slate-600">
                                <strong>What:</strong> Direct links between Account Roles and actual General Ledger accounts (e.g. <code className="text-sky-700">1100 - Accounts Receivable</code>).
                            </p>
                            <p className="mt-1 text-xs text-slate-600">
                                <strong>Role:</strong> Scope-specific (Branch vs Tenant Default) and time-bounded (<code className="text-sky-700">effective_from</code> to <code className="text-sky-700">effective_to</code>).
                            </p>
                        </div>
                    </div>
                </div>
            )}

            {/* Tabbed Navigation */}
            <div className="space-y-6">
                <Tabs tabs={tabs} active={activeTab} onChange={setActiveTab} />

                {/* TAB 1: POSTING PROFILES */}
                {activeTab === 'profiles' && (
                    <div className="space-y-6">
                        <div className={canManage ? 'grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(28rem,34rem)]' : ''}>
                            {/* Profile List Panel */}
                            <Panel title="Posting Profiles">
                                <div className="mb-4 space-y-3">
                                    <div className="flex flex-wrap gap-3">
                                        <div className="min-w-[14rem] flex-1">
                                            <Input
                                                type="search"
                                                placeholder="Search profile code, name, or description..."
                                                value={profileSearch}
                                                onChange={(e) => setProfileSearch(e.target.value)}
                                            />
                                        </div>
                                        <div className="w-36">
                                            <Select
                                                value={profileScopeFilter}
                                                onChange={(e) => setProfileScopeFilter(e.target.value as 'all' | 'tenant' | 'organization')}
                                                options={[
                                                    { value: 'all', label: 'All scopes' },
                                                    { value: 'tenant', label: 'Tenant default' },
                                                    { value: 'organization', label: 'Organization' },
                                                ]}
                                            />
                                        </div>
                                        <div className="w-32">
                                            <Select
                                                value={profileStatusFilter}
                                                onChange={(e) => setProfileStatusFilter(e.target.value as 'all' | 'active' | 'inactive')}
                                                options={[
                                                    { value: 'all', label: 'All status' },
                                                    { value: 'active', label: 'Active only' },
                                                    { value: 'inactive', label: 'Inactive' },
                                                ]}
                                            />
                                        </div>
                                    </div>
                                    <p className="text-xs text-slate-500">
                                        Profiles define the blueprint of journal entries produced by transactions. When operating in an Organization context, Tenant Default profiles act as fallback.
                                    </p>
                                </div>

                                <ErrorAlert error={profiles.error} />
                                {profiles.loading ? (
                                    <LoadingState />
                                ) : (
                                    <DataTable
                                        rows={filteredProfiles}
                                        rowKey={(row) => row.id}
                                        columns={profileColumns}
                                    />
                                )}
                            </Panel>

                            {/* Create / Edit / Inspect Profile Panel */}
                            {(canManage || selectedProfile) && (
                                <Panel
                                    title={
                                        isReadOnlyProfileView
                                            ? `Inspect Profile: ${selectedProfile?.name ?? ''}`
                                            : selectedProfile
                                                ? `Edit Profile: ${selectedProfile.name}`
                                                : 'Create Posting Profile'
                                    }
                                >
                                    <ErrorAlert error={profileError} />

                                    {isReadOnlyProfileView && (
                                        <div className="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800">
                                            <strong>Tenant Fallback Profile (Read-Only):</strong> This profile is defined at the Tenant Default scope. When an organization unit is selected, tenant default profiles are inherited and cannot be modified directly from this branch.
                                        </div>
                                    )}

                                    {!selectedProfile && !isReadOnlyProfileView && (
                                        <div className="mb-4 rounded-lg border border-sky-100 bg-sky-50/60 p-3">
                                            <label className="block text-xs font-semibold uppercase tracking-wider text-sky-900">
                                                Quick-Fill with Standard Preset Template
                                            </label>
                                            <p className="mt-0.5 text-xs text-sky-700">
                                                Select a canonical transaction event to auto-populate standard line keys and matching roles.
                                            </p>
                                            <div className="mt-2">
                                                <Select
                                                    value=""
                                                    onChange={(e) => {
                                                        const tmpl = CANONICAL_TEMPLATES.find((t) => t.code === e.target.value);
                                                        if (tmpl) loadTemplate(tmpl);
                                                    }}
                                                    options={[
                                                        { value: '', label: 'Select a template...' },
                                                        ...CANONICAL_TEMPLATES.map((t) => ({ value: t.code, label: `${t.name} (${t.code})` })),
                                                    ]}
                                                />
                                            </div>
                                        </div>
                                    )}

                                    <form
                                        className="space-y-4"
                                        onSubmit={(e) => {
                                            e.preventDefault();
                                            if (!isReadOnlyProfileView) void saveProfile();
                                        }}
                                    >
                                        <div className="grid gap-4 sm:grid-cols-2">
                                            <Input
                                                label="Profile Code"
                                                value={profileForm.code}
                                                onChange={(e) => setProfileForm({ ...profileForm, code: e.target.value })}
                                                error={fieldError(profileError, 'code')}
                                                disabled={isReadOnlyProfileView || selectedProfile !== null}
                                                required
                                            />
                                            <Input
                                                label="Profile Name"
                                                value={profileForm.name}
                                                onChange={(e) => setProfileForm({ ...profileForm, name: e.target.value })}
                                                error={fieldError(profileError, 'name')}
                                                disabled={isReadOnlyProfileView}
                                                required
                                            />
                                        </div>

                                        <Input
                                            label="Description"
                                            value={profileForm.description ?? ''}
                                            onChange={(e) => setProfileForm({ ...profileForm, description: e.target.value || null })}
                                            error={fieldError(profileError, 'description')}
                                            disabled={isReadOnlyProfileView}
                                        />

                                        <label className="flex items-center gap-2 text-sm font-medium text-slate-700">
                                            <input
                                                type="checkbox"
                                                checked={profileForm.is_active}
                                                disabled={isReadOnlyProfileView}
                                                onChange={(e) => setProfileForm({ ...profileForm, is_active: e.target.checked })}
                                            />
                                            Active for transaction posting
                                        </label>

                                        {/* Rules Section */}
                                        <div className="space-y-3 pt-2">
                                            <div className="flex items-center justify-between border-b border-slate-200 pb-2">
                                                <div>
                                                    <h4 className="text-sm font-semibold text-slate-800">
                                                        Semantic Rule Lines ({profileForm.rules.filter((r) => r.is_active).length} Active)
                                                    </h4>
                                                    <p className="text-xs text-slate-500">
                                                        Each transaction line key is mapped to an Account Role.
                                                    </p>
                                                </div>
                                                {!isReadOnlyProfileView && (
                                                    <Button
                                                        type="button"
                                                        variant="secondary"
                                                        onClick={() => setProfileForm({ ...profileForm, rules: [...profileForm.rules, emptyProfileRule()] })}
                                                    >
                                                        + Add Line
                                                    </Button>
                                                )}
                                            </div>

                                            <datalist id="common-line-keys">
                                                {COMMON_LINE_KEYS.map((k) => (
                                                    <option key={k} value={k} />
                                                ))}
                                            </datalist>

                                            <div className="space-y-3">
                                                {profileForm.rules.map((rule, index) => {
                                                    const isInactiveExisting = rule.is_existing && !rule.is_active;

                                                    return (
                                                        <div
                                                            key={rule.id ?? index}
                                                            className={`rounded-lg border p-3 transition-colors ${
                                                                isInactiveExisting
                                                                    ? 'border-slate-200 bg-slate-50 opacity-75'
                                                                    : 'border-slate-200 bg-white'
                                                            }`}
                                                        >
                                                            <div className="grid gap-3 sm:grid-cols-[1.2fr_1.5fr_auto]">
                                                                <div>
                                                                    <Input
                                                                        label="Line Key"
                                                                        list="common-line-keys"
                                                                        value={rule.line_key}
                                                                        disabled={isReadOnlyProfileView || isInactiveExisting}
                                                                        onChange={(e) => updateRule(index, { line_key: e.target.value })}
                                                                        error={fieldError(profileError, `rules.${index}.line_key`)}
                                                                        placeholder="e.g. receivable"
                                                                        required
                                                                    />
                                                                </div>
                                                                <div>
                                                                    <Select
                                                                        label="Target Account Role"
                                                                        value={rule.account_role_id || ''}
                                                                        disabled={isReadOnlyProfileView || isInactiveExisting}
                                                                        onChange={(e) => updateRule(index, { account_role_id: Number(e.target.value) || 0 })}
                                                                        options={activeRoles.map((role) => ({
                                                                            value: String(role.id),
                                                                            label: `${role.code} - ${role.name}`,
                                                                        }))}
                                                                        error={fieldError(profileError, `rules.${index}.account_role_id`)}
                                                                    />
                                                                </div>
                                                                <div className="flex items-end">
                                                                    {!isReadOnlyProfileView && (
                                                                        isInactiveExisting ? (
                                                                            <Button
                                                                                type="button"
                                                                                variant="secondary"
                                                                                onClick={() => updateRule(index, { is_active: true })}
                                                                            >
                                                                                Reactivate
                                                                            </Button>
                                                                        ) : (
                                                                            <Button
                                                                                type="button"
                                                                                variant="danger"
                                                                                disabled={profileForm.rules.filter((r) => r.is_active).length <= 1 && !rule.is_existing}
                                                                                onClick={() => handleRemoveRule(index)}
                                                                            >
                                                                                {rule.is_existing ? 'Deactivate' : 'Remove'}
                                                                            </Button>
                                                                        )
                                                                    )}
                                                                </div>
                                                            </div>

                                                            {/* Rule Validity & Description Row */}
                                                            <div className="mt-2 grid gap-3 sm:grid-cols-2 text-xs">
                                                                <Input
                                                                    label="Effective From"
                                                                    type="date"
                                                                    value={rule.effective_from}
                                                                    disabled={isReadOnlyProfileView || isInactiveExisting}
                                                                    onChange={(e) => updateRule(index, { effective_from: e.target.value })}
                                                                />
                                                                <Input
                                                                    label="Effective To (Optional)"
                                                                    type="date"
                                                                    value={rule.effective_to ?? ''}
                                                                    disabled={isReadOnlyProfileView || isInactiveExisting}
                                                                    onChange={(e) => updateRule(index, { effective_to: e.target.value || null })}
                                                                />
                                                            </div>

                                                            {isInactiveExisting && (
                                                                <p className="mt-1 text-xs text-amber-700 font-medium">
                                                                    Marked for deactivation. Financial audit trail remains preserved.
                                                                </p>
                                                            )}
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        </div>

                                        <div className="flex flex-wrap justify-between gap-3 pt-3 border-t border-slate-200">
                                            {selectedProfile && (
                                                <Button type="button" variant="secondary" onClick={resetProfile}>
                                                    New Profile
                                                </Button>
                                            )}
                                            <div className="flex gap-2">
                                                {!isReadOnlyProfileView && (
                                                    <Button type="submit" loading={savingProfile}>
                                                        {selectedProfile ? 'Update Profile' : 'Create Profile'}
                                                    </Button>
                                                )}
                                            </div>
                                        </div>
                                    </form>
                                </Panel>
                            )}
                        </div>
                    </div>
                )}

                {/* TAB 2: ACCOUNT ROLES */}
                {activeTab === 'roles' && (
                    <div className="space-y-6">
                        <div className={canManage ? 'grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(25rem,30rem)]' : ''}>
                            {/* Roles List Panel */}
                            <Panel title="Account Roles Catalog">
                                <div className="mb-4 space-y-3">
                                    <div className="flex flex-wrap gap-3">
                                        <div className="min-w-[14rem] flex-1">
                                            <Input
                                                type="search"
                                                placeholder="Search role code or name..."
                                                value={roleSearch}
                                                onChange={(e) => setRoleSearch(e.target.value)}
                                            />
                                        </div>
                                        <div className="w-36">
                                            <Select
                                                value={roleStatusFilter}
                                                onChange={(e) => setRoleStatusFilter(e.target.value as 'all' | 'active' | 'inactive')}
                                                options={[
                                                    { value: 'all', label: 'All status' },
                                                    { value: 'active', label: 'Active only' },
                                                    { value: 'inactive', label: 'Inactive' },
                                                ]}
                                            />
                                        </div>
                                    </div>
                                    <p className="text-xs text-slate-500">
                                        Account roles represent abstract financial obligations and revenue/expense classifications. They are shared across business modules and assigned to chart-of-accounts entries.
                                    </p>
                                </div>

                                <ErrorAlert error={roles.error} />
                                {roles.loading ? (
                                    <LoadingState />
                                ) : (
                                    <DataTable
                                        rows={filteredRoles}
                                        rowKey={(row) => row.id}
                                        columns={roleColumns}
                                    />
                                )}
                            </Panel>

                            {/* Create / Edit Role Panel */}
                            {canManage && (
                                <Panel title={selectedRole ? `Edit Role: ${selectedRole.name}` : 'New Account Role'}>
                                    <ErrorAlert error={roleError} />
                                    <form className="space-y-4" onSubmit={(e) => { e.preventDefault(); void saveRole(); }}>
                                        <div className="space-y-1">
                                            <Input
                                                label="Role Code"
                                                value={roleForm.code}
                                                onChange={(e) => setRoleForm({ ...roleForm, code: e.target.value })}
                                                error={fieldError(roleError, 'code')}
                                                placeholder="e.g. receivable"
                                                required
                                            />
                                            <p className="text-[11px] text-slate-500">
                                                Normalized to lowercased snake_case (e.g. <code className="text-sky-700">receivable</code>, <code className="text-sky-700">tax_payable</code>).
                                            </p>
                                        </div>

                                        <Input
                                            label="Role Name"
                                            value={roleForm.name}
                                            onChange={(e) => setRoleForm({ ...roleForm, name: e.target.value })}
                                            error={fieldError(roleError, 'name')}
                                            placeholder="e.g. Accounts Receivable"
                                            required
                                        />

                                        <Input
                                            label="Description (Optional)"
                                            value={roleForm.description ?? ''}
                                            onChange={(e) => setRoleForm({ ...roleForm, description: e.target.value || null })}
                                            error={fieldError(roleError, 'description')}
                                        />

                                        <label className="flex items-center gap-2 text-sm font-medium text-slate-700">
                                            <input
                                                type="checkbox"
                                                checked={roleForm.is_active}
                                                onChange={(e) => setRoleForm({ ...roleForm, is_active: e.target.checked })}
                                            />
                                            Active for assignment & resolution
                                        </label>

                                        <div className="flex justify-end gap-2 pt-2 border-t border-slate-200">
                                            {selectedRole && (
                                                <Button type="button" variant="secondary" onClick={resetRole}>
                                                    New Role
                                                </Button>
                                            )}
                                            <Button type="submit" loading={savingRole}>
                                                {selectedRole ? 'Update Role' : 'Create Role'}
                                            </Button>
                                        </div>
                                    </form>
                                </Panel>
                            )}
                        </div>
                    </div>
                )}

                {/* TAB 3: EFFECTIVE ASSIGNMENTS */}
                {activeTab === 'assignments' && (
                    <div className="space-y-6">
                        <div className={canManage ? 'grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(25rem,30rem)]' : ''}>
                            {/* Assignments List Panel */}
                            <Panel title="Effective Account Assignments">
                                <div className="mb-4 space-y-3">
                                    <div className="flex flex-wrap gap-3">
                                        <div className="min-w-[14rem] flex-1">
                                            <Input
                                                type="search"
                                                placeholder="Search by role or account name/code..."
                                                value={assignmentSearch}
                                                onChange={(e) => setAssignmentSearch(e.target.value)}
                                            />
                                        </div>
                                        <div className="w-36">
                                            <Select
                                                value={assignmentScopeFilter}
                                                onChange={(e) => setAssignmentScopeFilter(e.target.value as 'all' | 'tenant' | 'organization')}
                                                options={[
                                                    { value: 'all', label: 'All scopes' },
                                                    { value: 'tenant', label: 'Tenant default' },
                                                    { value: 'organization', label: 'Organization' },
                                                ]}
                                            />
                                        </div>
                                        <div className="w-32">
                                            <Select
                                                value={assignmentStatusFilter}
                                                onChange={(e) => setAssignmentStatusFilter(e.target.value as 'all' | 'active' | 'upcoming' | 'expired')}
                                                options={[
                                                    { value: 'all', label: 'All state' },
                                                    { value: 'active', label: 'Active now' },
                                                    { value: 'upcoming', label: 'Scheduled' },
                                                    { value: 'expired', label: 'Expired' },
                                                ]}
                                            />
                                        </div>
                                    </div>
                                    <p className="text-xs text-slate-500">
                                        Assignments determine which concrete Chart of Accounts record receives debits and credits when a transaction references an Account Role on a given date.
                                    </p>
                                </div>

                                <ErrorAlert error={assignments.error ?? assignmentError} />
                                {assignments.loading ? (
                                    <LoadingState />
                                ) : (
                                    <DataTable
                                        rows={filteredAssignments}
                                        rowKey={(row) => row.id}
                                        columns={assignmentColumns}
                                    />
                                )}
                            </Panel>

                            {/* Create Assignment Form */}
                            {canManage && (
                                <Panel title="New Effective Assignment">
                                    {!canViewAccounts ? (
                                        <p className="text-sm text-slate-600">
                                            Finance account view permission is required before account assignments can be created or changed.
                                        </p>
                                    ) : (
                                        <form className="space-y-4" onSubmit={(e) => { e.preventDefault(); void saveAssignment(); }}>
                                            <Select
                                                label="Account Role"
                                                value={assignmentForm.account_role_id || ''}
                                                onChange={(e) => setAssignmentForm({ ...assignmentForm, account_role_id: Number(e.target.value) || 0 })}
                                                options={activeRoles.map((role) => ({
                                                    value: String(role.id),
                                                    label: `${role.code} - ${role.name}`,
                                                }))}
                                                error={fieldError(assignmentError, 'account_role_id')}
                                                required
                                            />

                                            <Select
                                                label="Target Finance GL Account"
                                                value={assignmentForm.account_id || ''}
                                                onChange={(e) => setAssignmentForm({ ...assignmentForm, account_id: Number(e.target.value) || 0 })}
                                                options={activeAccounts.map((account) => ({
                                                    value: String(account.id),
                                                    label: `${account.code} - ${account.name} (${account.normal_balance ?? 'posting'})`,
                                                }))}
                                                error={fieldError(assignmentError, 'account_id')}
                                                required
                                            />

                                            <div className="grid gap-4 sm:grid-cols-2">
                                                <Input
                                                    label="Effective From"
                                                    type="date"
                                                    value={assignmentForm.effective_from}
                                                    onChange={(e) => setAssignmentForm({ ...assignmentForm, effective_from: e.target.value })}
                                                    error={fieldError(assignmentError, 'effective_from')}
                                                    required
                                                />
                                                <Input
                                                    label="Effective To (Optional)"
                                                    type="date"
                                                    value={assignmentForm.effective_to ?? ''}
                                                    onChange={(e) => setAssignmentForm({ ...assignmentForm, effective_to: e.target.value || null })}
                                                    error={fieldError(assignmentError, 'effective_to')}
                                                />
                                            </div>

                                            <div className="rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                                                <strong>Effective Dating Rule:</strong> Assignments cannot overlap for the same role and scope. Leave <em>Effective To</em> empty for open-ended assignments.
                                            </div>

                                            <div className="flex justify-end pt-2">
                                                <Button type="submit" loading={savingAssignment}>
                                                    Create Assignment
                                                </Button>
                                            </div>
                                        </form>
                                    )}
                                </Panel>
                            )}
                        </div>
                    </div>
                )}

                {/* TAB 4: POSTING HEALTH & RESOLUTION SIMULATOR */}
                {activeTab === 'simulator' && (
                    <div className="space-y-6">
                        {/* Health Audit Card */}
                        <Panel title="Configuration Health & Audit">
                            <div className="space-y-4">
                                {healthAudit.isHealthy ? (
                                    <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">
                                        <div className="flex items-center gap-2">
                                            <span className="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-600 text-xs font-bold text-white">✓</span>
                                            <h4 className="font-semibold text-emerald-950">
                                                All Active Posting Profiles Are Production-Ready
                                            </h4>
                                        </div>
                                        <p className="mt-1 text-xs text-emerald-700">
                                            All {healthAudit.totalProfilesChecked} active posting profiles with {healthAudit.totalRolesReferenced} semantic role mappings have valid, active GL account assignments for today's date.
                                        </p>
                                    </div>
                                ) : (
                                    <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-amber-800">
                                        <div className="flex items-center gap-2">
                                            <span className="flex h-6 w-6 items-center justify-center rounded-full bg-amber-600 text-xs font-bold text-white">!</span>
                                            <h4 className="font-semibold text-amber-950">
                                                Action Required: {healthAudit.issues.length} Unmapped Line Mappings Detected
                                            </h4>
                                        </div>
                                        <p className="mt-1 text-xs text-amber-700">
                                            The following posting lines cannot post today because their target account roles have no active GL account assignment or the role is inactive.
                                        </p>
                                    </div>
                                )}

                                {healthAudit.issues.length > 0 && (
                                    <div className="overflow-x-auto rounded-lg border border-slate-200">
                                        <table className="min-w-full divide-y divide-slate-200 text-left text-sm">
                                            <thead className="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                                <tr>
                                                    <th className="px-4 py-2.5">Profile</th>
                                                    <th className="px-4 py-2.5">Line Key</th>
                                                    <th className="px-4 py-2.5">Target Role</th>
                                                    <th className="px-4 py-2.5">Diagnostic Issue</th>
                                                    <th className="px-4 py-2.5 text-right">Remedy</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-slate-100 bg-white">
                                                {healthAudit.issues.map((issue, idx) => (
                                                    <tr key={idx} className="transition-colors hover:bg-blue-50/40">
                                                        <td className="px-4 py-2.5 font-medium text-slate-800">
                                                            {issue.profileName} (<code className="text-xs text-slate-600">{issue.profileCode}</code>)
                                                        </td>
                                                        <td className="px-4 py-2.5 font-mono text-xs text-slate-700">
                                                            {issue.lineKey}
                                                        </td>
                                                        <td className="px-4 py-2.5 text-slate-800">
                                                            {issue.roleName} (<code className="text-xs text-slate-600">{issue.roleCode}</code>)
                                                        </td>
                                                        <td className="px-4 py-2.5 text-xs font-medium text-amber-800">
                                                            {issue.issue}
                                                        </td>
                                                        <td className="px-4 py-2.5 text-right">
                                                            {canManage && (
                                                                <Button
                                                                    type="button"
                                                                    variant="secondary"
                                                                    onClick={() => {
                                                                        setAssignmentForm({
                                                                            account_role_id: issue.roleId,
                                                                            account_id: 0,
                                                                            effective_from: todayDate(),
                                                                            effective_to: null,
                                                                        });
                                                                        setActiveTab('assignments');
                                                                    }}
                                                                >
                                                                    Assign Account
                                                                </Button>
                                                            )}
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                )}
                            </div>
                        </Panel>

                        {/* Interactive Live Posting Simulator */}
                        <Panel title="Live Resolution Simulator & Trace Engine">
                            <div className="space-y-4">
                                <p className="text-sm text-slate-600">
                                    Simulate posting resolution against the live accounting rules engine. Select any profile and effective date to preview exactly which GL account will receive transaction debits and credits.
                                </p>

                                <div className="grid gap-4 sm:grid-cols-[1.5fr_1fr_auto]">
                                    <Select
                                        label="Select Profile to Test"
                                        value={simulationProfileId ? String(simulationProfileId) : ''}
                                        onChange={(e) => setSimulationProfileId(Number(e.target.value) || null)}
                                        options={[
                                            { value: '', label: 'Select posting profile...' },
                                            ...allProfilesList.map((p) => ({ value: String(p.id), label: `${p.name} (${p.code})` })),
                                        ]}
                                    />
                                    <Input
                                        label="Posting Date"
                                        type="date"
                                        value={simulationDate}
                                        onChange={(e) => setSimulationDate(e.target.value)}
                                    />
                                    <div className="flex items-end">
                                        <Button
                                            type="button"
                                            variant="primary"
                                            loading={simulating}
                                            disabled={!simulationProfileId}
                                            onClick={() => simulationProfileId && void runSimulation(simulationProfileId, simulationDate)}
                                        >
                                            Simulate Resolution
                                        </Button>
                                    </div>
                                </div>

                                <ErrorAlert error={simulationError} />

                                {simulationResult && (
                                    <div className="space-y-3 pt-3 border-t border-slate-200">
                                        <div className="flex items-center justify-between">
                                            <div>
                                                <h4 className="text-sm font-semibold text-slate-800">
                                                    Resolution Trace for: {simulationResult.profile_name} ({simulationResult.profile_code})
                                                </h4>
                                                <p className="text-xs text-slate-500">
                                                    Evaluated on posting date: <span className="font-semibold text-slate-700">{simulationResult.posting_date}</span>
                                                </p>
                                            </div>
                                            <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${
                                                simulationResult.is_ready
                                                    ? 'bg-emerald-100 text-emerald-800'
                                                    : 'bg-rose-100 text-rose-800'
                                            }`}>
                                                {simulationResult.is_ready ? 'Ready to Post' : 'Resolution Errors'}
                                            </span>
                                        </div>

                                        <div className="overflow-x-auto rounded-lg border border-slate-200">
                                            <table className="min-w-full divide-y divide-slate-200 text-left text-sm">
                                                <thead className="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                                                    <tr>
                                                        <th className="px-4 py-2.5">Line Key</th>
                                                        <th className="px-4 py-2.5">Account Role</th>
                                                        <th className="px-4 py-2.5">Resolved GL Account</th>
                                                        <th className="px-4 py-2.5">Scope Source</th>
                                                        <th className="px-4 py-2.5">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-slate-100 bg-white">
                                                    {simulationResult.lines.map((line, idx) => (
                                                        <tr key={idx} className="transition-colors hover:bg-blue-50/40">
                                                            <td className="px-4 py-2.5 font-mono text-xs font-semibold text-slate-800">
                                                                {line.line_key}
                                                            </td>
                                                            <td className="px-4 py-2.5 text-slate-700">
                                                                {line.role ? `${line.role.name} (${line.role.code})` : `Account role ${line.account_role_id}`}
                                                            </td>
                                                            <td className="px-4 py-2.5">
                                                                {line.resolved_account ? (
                                                                    <div>
                                                                        <span className="font-semibold text-slate-900">
                                                                            {line.resolved_account.code} - {line.resolved_account.name}
                                                                        </span>
                                                                        <span className="ml-2 rounded bg-slate-100 px-1.5 py-0.2 text-[10px] font-medium uppercase text-slate-600">
                                                                            {line.resolved_account.normal_balance}
                                                                        </span>
                                                                    </div>
                                                                ) : (
                                                                    <span className="text-xs font-medium text-rose-700">
                                                                        {line.message ?? 'Unresolved'}
                                                                    </span>
                                                                )}
                                                            </td>
                                                            <td className="px-4 py-2.5 text-xs text-slate-600">
                                                                {line.resolved_account?.scope === 'tenant_default' ? (
                                                                    <span className="rounded bg-slate-100 px-2 py-0.5 text-slate-700">Tenant Default</span>
                                                                ) : line.resolved_account?.scope === 'organization' ? (
                                                                    <span className="rounded bg-purple-100 px-2 py-0.5 text-purple-800">Branch Override</span>
                                                                ) : (
                                                                    '-'
                                                                )}
                                                            </td>
                                                            <td className="px-4 py-2.5">
                                                                <span className={`inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ${
                                                                    line.status === 'resolved'
                                                                        ? 'bg-emerald-100 text-emerald-800'
                                                                        : 'bg-rose-100 text-rose-800'
                                                                }`}>
                                                                    {line.status === 'resolved' ? 'Resolved' : 'Failed'}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </Panel>
                    </div>
                )}

                {/* TAB 5: ARCHITECTURE & CONCEPT GUIDE */}
                {activeTab === 'guide' && (
                    <div className="space-y-6">
                        <Panel title="Finance Posting Architecture & User Guide">
                            <div className="prose prose-slate max-w-none space-y-6 text-sm text-slate-700">
                                <div>
                                    <h3 className="text-lg font-bold text-slate-900">1. Why does AutoERP use a 3-tier posting architecture?</h3>
                                    <p className="mt-1">
                                        Traditional accounting systems hardcode specific General Ledger account numbers (such as <code className="text-sky-700">1100</code> or <code className="text-sky-700">4100</code>) directly into billing and checkout code. This causes severe limitations:
                                    </p>
                                    <ul className="list-disc pl-5 space-y-1">
                                        <li>Multi-branch enterprises cannot have different branches post to branch-specific cash or inventory accounts without duplicating transaction code.</li>
                                        <li>Changing an account at year-end or fiscal period end corrupts past audit trails or breaks historical reconciliations.</li>
                                        <li>Third-party business modules (Sales, Purchasing, POS, Workshop Service, Rental) shouldn't need to know the chart of accounts structure.</li>
                                    </ul>
                                </div>

                                <div className="rounded-xl border border-sky-100 bg-sky-50/50 p-4">
                                    <h4 className="font-semibold text-sky-950">The Solution: 3-Tier Semantic Abstraction</h4>
                                    <div className="mt-3 grid gap-3 sm:grid-cols-3">
                                        <div className="rounded-lg bg-white p-3 border border-sky-200">
                                            <div className="font-semibold text-slate-900">Tier 1: Posting Profile</div>
                                            <div className="mt-1 text-xs text-slate-600">The blueprint. Answers: <em>"What lines does a Sales Invoice contain?"</em> (receivable, revenue, tax).</div>
                                        </div>
                                        <div className="rounded-lg bg-white p-3 border border-sky-200">
                                            <div className="font-semibold text-slate-900">Tier 2: Account Role</div>
                                            <div className="mt-1 text-xs text-slate-600">The semantic meaning. Answers: <em>"What role does this line play?"</em> (<code className="text-sky-700">Receivable</code>, <code className="text-sky-700">Sales Revenue</code>).</div>
                                        </div>
                                        <div className="rounded-lg bg-white p-3 border border-sky-200">
                                            <div className="font-semibold text-slate-900">Tier 3: Effective Assignment</div>
                                            <div className="mt-1 text-xs text-slate-600">The GL account binding. Answers: <em>"For this branch and date, which GL account is Receivable?"</em> (<code className="text-sky-700">1100</code>).</div>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <h3 className="text-lg font-bold text-slate-900">2. Scope Hierarchy: Tenant Default vs Organization Unit Override</h3>
                                    <p className="mt-1">
                                        AutoERP supports hierarchical inheritance:
                                    </p>
                                    <ul className="list-disc pl-5 space-y-1">
                                        <li><strong>Tenant Default:</strong> Fallback configurations that apply across all branches unless overridden. Created once by central administration.</li>
                                        <li><strong>Organization Unit:</strong> Branch or location-specific overrides. For instance, Branch Colombo can assign Account Role <code className="text-sky-700">cash</code> to <code className="text-sky-700">1011 - Cash Colombo</code>, while sharing the global Tenant Default for <code className="text-sky-700">tax_payable</code>.</li>
                                    </ul>
                                </div>

                                <div>
                                    <h3 className="text-lg font-bold text-slate-900">3. Effective Dating & Audit Safety</h3>
                                    <p className="mt-1">
                                        Both rules and assignments support <code className="text-sky-700">effective_from</code> and <code className="text-sky-700">effective_to</code> dates:
                                    </p>
                                    <ul className="list-disc pl-5 space-y-1">
                                        <li>Backdated invoice postings resolve through the rules and assignments that were valid on that posting date, maintaining true financial history.</li>
                                        <li>Never delete historical rules or assignments. Always set an <code className="text-sky-700">effective_to</code> end date or deactivate with audit safety.</li>
                                    </ul>
                                </div>
                            </div>
                        </Panel>
                    </div>
                )}
            </div>

            {/* End Assignment Modal Dialog */}
            <Modal
                open={endingAssignment !== null}
                title="End Account Assignment"
                onClose={() => setEndingAssignment(null)}
                closeDisabled={savingAssignment}
            >
                {endingAssignment && (
                    <form className="space-y-4" onSubmit={(e) => { e.preventDefault(); void submitEndAssignment(); }}>
                        <div className="rounded-lg border border-slate-200 bg-slate-50 p-4 space-y-2 text-sm">
                            <div>
                                <span className="text-xs font-semibold text-slate-500 uppercase">Role:</span>{' '}
                                <span className="font-semibold text-slate-900">
                                    {endingAssignment.role ? `${endingAssignment.role.name} (${endingAssignment.role.code})` : `#${endingAssignment.account_role_id}`}
                                </span>
                            </div>
                            <div>
                                <span className="text-xs font-semibold text-slate-500 uppercase">Assigned Account:</span>{' '}
                                <span className="font-semibold text-slate-900">
                                    {endingAssignment.account ? `${endingAssignment.account.code} - ${endingAssignment.account.name}` : `#${endingAssignment.account_id}`}
                                </span>
                            </div>
                            <div>
                                <span className="text-xs font-semibold text-slate-500 uppercase">Effective From:</span>{' '}
                                <span className="tabular-nums text-slate-700">{endingAssignment.effective_from}</span>
                            </div>
                        </div>

                        <p className="text-xs text-slate-600">
                            Setting an effective end date retires this assignment on the specified date. Historical transactions dated before this end date will continue resolving to this account.
                        </p>

                        <Input
                            label="Effective End Date"
                            type="date"
                            value={endingDate}
                            onChange={(e) => setEndingDate(e.target.value)}
                            min={endingAssignment.effective_from}
                            error={fieldError(assignmentError, 'effective_to')}
                            required
                        />

                        <div className="flex justify-end gap-2 pt-2 border-t border-slate-200">
                            <Button type="button" variant="secondary" onClick={() => setEndingAssignment(null)} disabled={savingAssignment}>
                                Cancel
                            </Button>
                            <Button type="submit" variant="danger" loading={savingAssignment}>
                                End Assignment
                            </Button>
                        </div>
                    </form>
                )}
            </Modal>
        </>
    );
}

function todayDate(): string {
    return new Date().toISOString().slice(0, 10);
}
