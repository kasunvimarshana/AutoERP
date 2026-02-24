<template>
  <span :class="[colorClass, 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize']">
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: { type: String, default: '' },
});

const statusMap = {
    // Generic
    active: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
    inactive: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
    pending: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
    pending_approval: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
    po_raised: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    draft: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
    // Lead / CRM
    new: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    open: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
    converted: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
    lost: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    // Opportunity stages
    prospecting: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    qualified: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
    proposal: 'bg-violet-100 text-violet-800 dark:bg-violet-900/30 dark:text-violet-400',
    negotiation: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
    closed_won: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
    closed_lost: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    // Order / Sales
    confirmed: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    shipped: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
    delivered: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
    cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    // Purchase
    approved: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
    received: 'bg-teal-100 text-teal-800 dark:bg-teal-900/30 dark:text-teal-400',
    partially_received: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
    // HR / Payroll
    processing: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
    completed: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
    // Helpdesk
    in_progress: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    resolved: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
    closed: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
    // Projects
    todo: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
    review: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
    done: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
    // Inventory
    available: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
    low_stock: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
    out_of_stock: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    // Stock Valuation movement types
    receipt: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
    deduction: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    adjustment: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    // Cycle counts
    posted: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
    // Accounting
    paid: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
    unpaid: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    partial: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
    // Bank transactions
    credit: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
    debit: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    unreconciled: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
    reconciled: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
    // Priority
    low: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    medium: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
    high: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
    critical: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    // Logistics
    dispatched: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    in_transit: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
    // Leave / Approval
    rejected: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    // Expense
    submitted: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    reimbursed: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
    // Asset Management
    disposed: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
    // Recruitment
    on_hold: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
    in_review: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    interview: 'bg-violet-100 text-violet-800 dark:bg-violet-900/30 dark:text-violet-400',
    offer: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
    hired: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
    // Fleet
    retired: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
    // Documents
    published: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
    archived: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
    // Contracts
    terminated: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    expired: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
    // Maintenance
    under_maintenance: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
    decommissioned: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
    // Quality Control
    passed: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
    failed: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    // Field Service
    assigned: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    invoiced: 'bg-violet-100 text-violet-800 dark:bg-violet-900/30 dark:text-violet-400',
    // E-Commerce
    placed: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    // Subscriptions
    trial: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
    paused: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
    // Integration
    revoked: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    // Attendance
    present: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
    absent: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    on_leave: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    half_day: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
    // Credit note
    credit_note: 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
    // Lot / serial tracking
    blocked: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    // Salary component types
    earning: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
    // User status
    pending_verification: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
    // Audit log actions
    create: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    update: 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
    delete: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    view: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
    login: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
    // KB Article statuses (reuse existing: draft, published, archived)
    agents_only: 'bg-violet-100 text-violet-800 dark:bg-violet-900/30 dark:text-violet-400',
    customers_only: 'bg-teal-100 text-teal-800 dark:bg-teal-900/30 dark:text-teal-400',
    // Budget variance
    overspent: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    on_budget: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
    // Accounting period
    locked: 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-400',
    // Tenant status
    suspended: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
};

const colorClass = computed(
    () => statusMap[props.status?.toLowerCase()] ?? 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
);

const label = computed(() => (props.status ?? '—').replace(/_/g, ' '));
</script>
