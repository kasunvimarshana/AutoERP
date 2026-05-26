import { useMemo } from 'react';
import { useSearchParams } from 'react-router-dom';
import { PageHeader } from '../../../components/layout/PageHeader';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { Input } from '../../../components/forms/Input';
import { Select } from '../../../components/forms/Select';
import { DataTable, SearchFilterToolbar, StatusBadge, TablePagination, TableToolbar, type DataTableColumn } from '../../../components/tables';
import { ContentCard } from '../../../components/ui/ContentCard';
import { useTenant } from '../../auth/context/TenantContext';
import { useAccounts } from '../hooks';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { parseBooleanSearchParam, parsePositiveInteger } from '../../shared/utils';
import type { AccountRecord } from '../types';

export function FinanceAccountsPage() {
    const { tenantId } = useTenant();
    const [searchParams, setSearchParams] = useSearchParams();

    const page = parsePositiveInteger(searchParams.get('page'), 1);
    const search = searchParams.get('search') ?? '';
    const type = searchParams.get('type') ?? '';
    const active = searchParams.get('active') ?? '';

    const accountsQuery = useAccounts({
        tenant_id: tenantId,
        page,
        per_page: 10,
        name: search || undefined,
        type: type ? (type as AccountRecord['type']) : undefined,
        is_active: parseBooleanSearchParam(active),
        sort: 'code',
    });

    function updateParams(updates: Record<string, string | number | undefined>) {
        setSearchParams((current) => {
            const next = new URLSearchParams(current);

            for (const [key, value] of Object.entries(updates)) {
                if (value === undefined || value === '') {
                    next.delete(key);
                } else {
                    next.set(key, String(value));
                }
            }

            if ('search' in updates || 'type' in updates || 'active' in updates) {
                next.set('page', '1');
            }

            return next;
        });
    }

    const columns: DataTableColumn<AccountRecord>[] = useMemo(
        () => [
            {
                key: 'code',
                header: 'Account',
                render: (account) => (
                    <div>
                        <p className="font-medium text-stone-950">
                            {account.code} | {account.name}
                        </p>
                        <p className="mt-1 text-xs text-stone-500">{account.sub_type || account.path || 'No path metadata'}</p>
                    </div>
                ),
            },
            { key: 'type', header: 'Type', render: (account) => <StatusBadge>{account.type}</StatusBadge> },
            { key: 'normal_balance', header: 'Normal Balance', render: (account) => <StatusBadge tone="warning">{account.normal_balance}</StatusBadge> },
            {
                key: 'flags',
                header: 'Flags',
                render: (account) => (
                    <div className="flex flex-wrap gap-2">
                        {account.is_system ? <StatusBadge>System</StatusBadge> : null}
                        {account.is_bank_account ? <StatusBadge tone="success">Bank</StatusBadge> : null}
                        {account.is_credit_card ? <StatusBadge tone="warning">Card</StatusBadge> : null}
                        {!account.is_system && !account.is_bank_account && !account.is_credit_card ? <span className="text-sm text-stone-500">-</span> : null}
                    </div>
                ),
            },
            { key: 'active', header: 'Status', render: (account) => <StatusBadge tone={account.is_active ? 'success' : 'default'}>{account.is_active ? 'Active' : 'Inactive'}</StatusBadge> },
        ],
        [],
    );

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[{ label: 'Finance' }, { label: 'Accounts' }]}
                description="The Finance account route is now connected to the chart-of-accounts API so Phase 5 users can review ledger structure inside the frontend shell."
                title="Accounts"
            />

            <ContentCard className="p-0">
                <TableToolbar description="Inspect chart-of-account records, account types, and operational flags such as bank-account or credit-card designation." title="Chart of accounts">
                    <SearchFilterToolbar
                        filters={
                            <>
                                <Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ type: event.target.value || undefined })} value={type}>
                                    <option value="">All types</option>
                                    <option value="asset">Asset</option>
                                    <option value="liability">Liability</option>
                                    <option value="equity">Equity</option>
                                    <option value="revenue">Revenue</option>
                                    <option value="expense">Expense</option>
                                </Select>
                                <Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ active: event.target.value || undefined })} value={active}>
                                    <option value="">All statuses</option>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </Select>
                            </>
                        }
                        search={<Input className="w-full md:max-w-sm" label={undefined} onChange={(event) => updateParams({ search: event.target.value || undefined })} placeholder="Search account name" value={search} />}
                        trailing={<div className="text-sm text-stone-500">{accountsQuery.data?.meta?.total ?? 0} accounts</div>}
                    />
                </TableToolbar>

                {accountsQuery.isPending ? (
                    <LoadingState className="m-6" lines={8} />
                ) : accountsQuery.isError ? (
                    isForbiddenError(accountsQuery.error) ? (
                        <ProtectedErrorState className="m-6" description={accountsQuery.error.message} />
                    ) : (
                        <ErrorState className="m-6" description={accountsQuery.error.message} title="Unable to load accounts" />
                    )
                ) : (
                    <DataTable
                        columns={columns}
                        emptyState={<EmptyState className="m-6" description="No accounts match the current filters." title="No accounts found" />}
                        footer={<TablePagination meta={accountsQuery.data.meta} onPageChange={(nextPage) => updateParams({ page: nextPage })} />}
                        getRowKey={(account) => account.id}
                        rows={accountsQuery.data.items}
                    />
                )}
            </ContentCard>
        </div>
    );
}
