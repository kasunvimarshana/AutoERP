import { useMemo } from 'react';
import { useSearchParams } from 'react-router-dom';
import { PageHeader } from '../../../components/layout/PageHeader';
import { EmptyState } from '../../../components/feedback/EmptyState';
import { ErrorState } from '../../../components/feedback/ErrorState';
import { LoadingState } from '../../../components/feedback/LoadingState';
import { Select } from '../../../components/forms/Select';
import { DataTable, SearchFilterToolbar, StatusBadge, TablePagination, TableToolbar, type DataTableColumn } from '../../../components/tables';
import { ContentCard } from '../../../components/ui/ContentCard';
import { JsonPreview } from '../../../components/ui/JsonPreview';
import { useTenantPlans } from '../hooks';
import { ProtectedErrorState, isForbiddenError } from '../../shared/ProtectedErrorState';
import { formatCurrency, parsePositiveInteger } from '../../shared/utils';
import type { TenantPlanRecord } from '../types';

function countEntries(value: Record<string, unknown> | null) {
    if (!value) {
        return 0;
    }

    return Object.keys(value).length;
}

export function TenantPlansPage() {
    const [searchParams, setSearchParams] = useSearchParams();

    const page = parsePositiveInteger(searchParams.get('page'), 1);
    const billingInterval = searchParams.get('billing_interval') ?? '';
    const selectedPlanId = parsePositiveInteger(searchParams.get('plan_id'), 0);

    const plansQuery = useTenantPlans({
        page,
        per_page: 10,
        billing_interval: billingInterval ? (billingInterval as 'month' | 'year') : undefined,
    });

    const activePlanId = selectedPlanId || plansQuery.data?.items[0]?.id || 0;
    const activePlan = plansQuery.data?.items.find((plan) => plan.id === activePlanId) ?? null;

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

            if ('billing_interval' in updates) {
                next.set('page', '1');
            }

            return next;
        });
    }

    const columns: DataTableColumn<TenantPlanRecord>[] = useMemo(
        () => [
            {
                key: 'name',
                header: 'Plan',
                render: (plan) => (
                    <div>
                        <p className="font-medium text-stone-950">{plan.name}</p>
                        <p className="mt-1 text-xs text-stone-500">{plan.slug}</p>
                    </div>
                ),
            },
            { key: 'billing_interval', header: 'Billing', render: (plan) => <StatusBadge>{plan.billing_interval}</StatusBadge> },
            {
                key: 'price',
                header: 'Price',
                render: (plan) => <span className="text-sm text-stone-700">{`${formatCurrency(plan.price)}${plan.currency_code ? ` ${plan.currency_code}` : ''}`}</span>,
            },
            { key: 'features', header: 'Features', render: (plan) => <span className="text-sm text-stone-700">{countEntries(plan.features)} configured</span> },
            { key: 'limits', header: 'Limits', render: (plan) => <span className="text-sm text-stone-700">{countEntries(plan.limits)} configured</span> },
            { key: 'active', header: 'Status', render: (plan) => <StatusBadge tone={plan.is_active ? 'success' : 'default'}>{plan.is_active ? 'Active' : 'Inactive'}</StatusBadge> },
            {
                key: 'detail',
                header: 'Detail',
                className: 'w-[8rem]',
                render: (plan) => (
                    <button className="text-sm font-medium text-stone-700 transition hover:text-stone-950" onClick={() => updateParams({ plan_id: plan.id })} type="button">
                        Inspect
                    </button>
                ),
            },
        ],
        [],
    );

    return (
        <div className="mx-auto flex w-full max-w-7xl flex-col gap-6">
            <PageHeader
                breadcrumbs={[{ label: 'Tenant Admin' }, { label: 'Plans' }]}
                description="Tenant plan administration is now backed by the existing plan API, including a detail panel for features and limit payloads."
                title="Plans"
            />

            <ContentCard className="p-0">
                <TableToolbar description="Review commercial plans, billing cadence, and available feature/limit definitions." title="Plan catalog">
                    <SearchFilterToolbar
                        filters={
                            <Select className="w-full md:max-w-[12rem]" onChange={(event) => updateParams({ billing_interval: event.target.value || undefined })} value={billingInterval}>
                                <option value="">All billing</option>
                                <option value="month">Monthly</option>
                                <option value="year">Yearly</option>
                            </Select>
                        }
                        trailing={<div className="text-sm text-stone-500">{plansQuery.data?.meta?.total ?? 0} plans</div>}
                    />
                </TableToolbar>

                {plansQuery.isPending ? (
                    <LoadingState className="m-6" lines={8} />
                ) : plansQuery.isError ? (
                    isForbiddenError(plansQuery.error) ? (
                        <ProtectedErrorState className="m-6" description={plansQuery.error.message} />
                    ) : (
                        <ErrorState className="m-6" description={plansQuery.error.message} title="Unable to load tenant plans" />
                    )
                ) : (
                    <DataTable
                        columns={columns}
                        emptyState={<EmptyState className="m-6" description="No tenant plans match the current filters." title="No plans found" />}
                        footer={<TablePagination meta={plansQuery.data.meta} onPageChange={(nextPage) => updateParams({ page: nextPage })} />}
                        getRowKey={(plan) => plan.id}
                        rows={plansQuery.data.items}
                    />
                )}
            </ContentCard>

            <ContentCard className="grid gap-6 lg:grid-cols-2">
                {!activePlan ? (
                    <EmptyState description="Choose a plan from the table above to inspect the configured feature and limit payloads." title="No plan selected" />
                ) : (
                    <>
                        <div>
                            <h3 className="text-lg font-semibold text-stone-950">Features</h3>
                            <JsonPreview className="mt-3" value={activePlan.features} />
                        </div>
                        <div>
                            <h3 className="text-lg font-semibold text-stone-950">Limits</h3>
                            <JsonPreview className="mt-3" value={activePlan.limits} />
                        </div>
                    </>
                )}
            </ContentCard>
        </div>
    );
}
