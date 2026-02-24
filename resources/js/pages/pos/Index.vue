<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Page heading -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Point of Sale</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage terminals, cashier sessions, and orders.</p>
        </div>
        <div>
          <button
            v-if="activeTab === 'terminals'"
            type="button"
            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
            @click="openTerminalModal(null)"
          >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
            Add Terminal
          </button>
          <button
            v-else-if="activeTab === 'sessions'"
            type="button"
            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
            @click="showOpenSessionModal = true"
          >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
            Open Session
          </button>
          <button
            v-else-if="activeTab === 'orders'"
            type="button"
            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
            @click="openPlaceOrderModal"
          >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
            Place Order
          </button>
          <button
            v-else-if="activeTab === 'loyalty'"
            type="button"
            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
            @click="openLoyaltyProgramModal(null)"
          >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
            Create Program
          </button>
          <button
            v-else-if="activeTab === 'discounts'"
            type="button"
            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
            @click="openDiscountModal(null)"
          >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
            Create Discount
          </button>
        </div>
      </div>

      <!-- Summary stats -->
      <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <StatCard
          label="Terminals"
          :value="pos.terminalsMeta.total ?? 0"
          icon='<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0H3" /></svg>'
          color="bg-indigo-600"
        />
        <StatCard
          label="Sessions"
          :value="pos.sessionsMeta.total ?? 0"
          icon='<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" /></svg>'
          color="bg-teal-600"
        />
        <StatCard
          label="Total Orders"
          :value="pos.ordersMeta.total ?? 0"
          icon='<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" /></svg>'
          color="bg-amber-600"
        />
        <StatCard
          label="Discount Codes"
          :value="pos.discountsMeta.total ?? 0"
          icon='<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.169.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" /></svg>'
          color="bg-violet-600"
        />
      </div>

      <!-- Tabs -->
      <div class="border-b border-gray-200 dark:border-gray-800">
        <nav class="-mb-px flex gap-6" aria-label="POS tabs">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            type="button"
            :class="[
              activeTab === tab.key
                ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 dark:border-indigo-400'
                : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600',
              'whitespace-nowrap border-b-2 pb-3 text-sm font-medium transition-colors',
            ]"
            @click="switchTab(tab.key)"
          >
            {{ tab.label }}
            <span
              v-if="tab.count !== null"
              :class="activeTab === tab.key ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'"
              class="ml-2 rounded-full px-2 py-0.5 text-xs font-semibold"
            >{{ tab.count }}</span>
          </button>
        </nav>
      </div>

      <!-- Error alert -->
      <div
        v-if="pos.error"
        class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400"
        role="alert"
      >
        {{ pos.error }}
      </div>

      <!-- Terminals tab -->
      <div v-if="activeTab === 'terminals'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Terminals table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Location</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Last Active</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="pos.loading">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="pos.terminals.length === 0">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No terminals found.</td>
                </tr>
                <tr
                  v-for="terminal in pos.terminals"
                  v-else
                  :key="terminal.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ terminal.name }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ terminal.location ?? '—' }}</td>
                  <td class="px-4 py-3">
                    <StatusBadge :status="terminal.status ?? (terminal.is_active ? 'active' : 'inactive')" />
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(terminal.last_active_at ?? terminal.updated_at) }}</td>
                  <td class="px-4 py-3 text-right">
                    <div class="inline-flex items-center gap-2">
                      <button
                        type="button"
                        class="rounded px-2 py-1 text-xs font-medium text-indigo-600 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-indigo-900/30 transition-colors"
                        @click="openTerminalModal(terminal)"
                      >Edit</button>
                      <button
                        type="button"
                        class="rounded px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/30 transition-colors"
                        @click="confirmDeleteTerminal(terminal)"
                      >Delete</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="pos.terminalsMeta" @change="pos.fetchTerminals" />
        </div>
      </div>

      <!-- Sessions tab -->
      <div v-if="activeTab === 'sessions'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Sessions table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Terminal</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Cashier</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Opening Cash</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Sales</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Orders</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Opened At</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Closed At</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="pos.loading">
                  <td colspan="9" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="pos.sessions.length === 0">
                  <td colspan="9" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No sessions found.</td>
                </tr>
                <tr
                  v-for="session in pos.sessions"
                  v-else
                  :key="session.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ session.terminal_name ?? session.terminal_id }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ session.cashier_name ?? session.cashier_id ?? '—' }}</td>
                  <td class="px-4 py-3">
                    <StatusBadge :status="session.status" />
                  </td>
                  <td class="px-4 py-3 text-sm text-right font-medium text-gray-900 dark:text-white">{{ formatCurrency(session.opening_cash ?? session.opening_balance) }}</td>
                  <td class="px-4 py-3 text-sm text-right font-semibold text-emerald-700 dark:text-emerald-400">{{ formatCurrency(session.total_sales) }}</td>
                  <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">{{ session.order_count ?? 0 }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDateTime(session.opened_at ?? session.created_at) }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDateTime(session.closed_at) }}</td>
                  <td class="px-4 py-3 text-right">
                    <div v-if="session.status === 'open'" class="inline-flex items-center gap-2">
                      <RouterLink
                        :to="{ name: 'pos-terminal', params: { sessionId: session.id } }"
                        class="rounded px-2 py-1 text-xs font-medium text-indigo-600 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-indigo-900/30 transition-colors"
                      >Terminal</RouterLink>
                      <button
                        type="button"
                        class="rounded px-2 py-1 text-xs font-medium text-amber-600 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-900/30 transition-colors"
                        @click="openCloseSessionModal(session)"
                      >Close</button>
                    </div>
                    <span v-else class="text-xs text-gray-400 dark:text-gray-600">—</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="pos.sessionsMeta" @change="pos.fetchSessions" />
        </div>
      </div>

      <!-- Orders tab -->
      <div v-if="activeTab === 'orders'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="POS Orders table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Order #</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Payment</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Discount</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Total</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Date</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="pos.loading">
                  <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="pos.orders.length === 0">
                  <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No orders found.</td>
                </tr>
                <template
                  v-for="order in pos.orders"
                  v-else
                  :key="order.id"
                >
                  <tr
                    class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                    :class="expandedOrderId === order.id ? 'bg-indigo-50 dark:bg-indigo-900/10' : ''"
                  >
                    <td class="px-4 py-3 text-sm font-mono font-medium text-gray-900 dark:text-white">{{ order.order_number ?? order.number ?? order.id }}</td>
                    <td class="px-4 py-3">
                      <StatusBadge :status="order.status" />
                    </td>
                    <td class="px-4 py-3 text-sm">
                      <span
                        v-if="order.payment_method === 'split'"
                        class="inline-flex items-center gap-1 rounded-full bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300 px-2.5 py-0.5 text-xs font-semibold cursor-pointer"
                        :title="'Split payment — click to view breakdown'"
                        @click="togglePaymentBreakdown(order.id)"
                      >
                        Split
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                      </span>
                      <span v-else class="text-gray-500 dark:text-gray-400 capitalize">{{ order.payment_method ?? '—' }}</span>
                    </td>
                    <td class="px-4 py-3 text-sm text-right">
                      <span
                        v-if="order.discount_amount && Number(order.discount_amount) > 0"
                        class="font-medium text-emerald-600 dark:text-emerald-400"
                      >-{{ formatCurrency(order.discount_amount) }}</span>
                      <span v-else class="text-gray-400 dark:text-gray-600">—</span>
                    </td>
                    <td class="px-4 py-3 text-sm text-right font-medium text-gray-900 dark:text-white">{{ formatCurrency(order.total_amount ?? order.total) }}</td>
                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(order.created_at) }}</td>
                    <td class="px-4 py-3 text-right">
                      <div class="inline-flex items-center gap-2">
                        <button
                          v-if="order.lines && order.lines.length"
                          type="button"
                          :class="expandedOrderId === order.id ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-800'"
                          class="rounded px-2 py-1 text-xs font-medium transition-colors"
                          @click="toggleOrderLines(order.id)"
                        >Lines</button>
                        <button
                          v-if="order.status !== 'refunded' && order.status !== 'cancelled'"
                          type="button"
                          class="rounded px-2 py-1 text-xs font-medium text-amber-600 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-900/30 transition-colors"
                          @click="confirmRefundOrder(order)"
                        >Refund</button>
                        <span v-else-if="!order.lines || !order.lines.length" class="text-xs text-gray-400 dark:text-gray-600">—</span>
                      </div>
                    </td>
                  </tr>
                  <!-- Line items expansion row -->
                  <tr v-if="expandedOrderId === order.id && order.lines && order.lines.length" class="bg-indigo-50 dark:bg-indigo-900/10">
                    <td colspan="7" class="px-6 py-3">
                      <p class="text-xs font-semibold text-indigo-700 dark:text-indigo-300 mb-2">Line Items</p>
                      <table class="min-w-full text-xs">
                        <thead>
                          <tr class="text-gray-500 dark:text-gray-400">
                            <th class="text-left pr-4 pb-1 font-medium">Product</th>
                            <th class="text-right pr-4 pb-1 font-medium">Qty</th>
                            <th class="text-right pr-4 pb-1 font-medium">Unit Price</th>
                            <th class="text-right pr-4 pb-1 font-medium">Disc%</th>
                            <th class="text-right pr-4 pb-1 font-medium">Tax%</th>
                            <th class="text-right pb-1 font-medium">Line Total</th>
                          </tr>
                        </thead>
                        <tbody class="divide-y divide-indigo-100 dark:divide-indigo-800">
                          <tr v-for="line in order.lines" :key="line.id">
                            <td class="pr-4 py-1 text-gray-900 dark:text-white font-medium">{{ line.product_name }}</td>
                            <td class="pr-4 py-1 text-right text-gray-700 dark:text-gray-300">{{ line.quantity }}</td>
                            <td class="pr-4 py-1 text-right text-gray-700 dark:text-gray-300">{{ formatCurrency(line.unit_price) }}</td>
                            <td class="pr-4 py-1 text-right text-gray-500 dark:text-gray-400">{{ Number(line.discount ?? 0).toFixed(2) }}%</td>
                            <td class="pr-4 py-1 text-right text-gray-500 dark:text-gray-400">{{ Number(line.tax_rate ?? 0).toFixed(2) }}%</td>
                            <td class="py-1 text-right font-semibold text-gray-900 dark:text-white">{{ formatCurrency(line.line_total) }}</td>
                          </tr>
                        </tbody>
                      </table>
                      <!-- Subtotals -->
                      <div class="mt-2 flex flex-col items-end gap-0.5 text-xs">
                        <span class="text-gray-500 dark:text-gray-400">Subtotal: <span class="font-medium text-gray-900 dark:text-white">{{ formatCurrency(order.subtotal) }}</span></span>
                        <span class="text-gray-500 dark:text-gray-400">Tax: <span class="font-medium text-gray-900 dark:text-white">{{ formatCurrency(order.tax_total) }}</span></span>
                        <span v-if="order.discount_amount && Number(order.discount_amount) > 0" class="text-emerald-600 dark:text-emerald-400">Discount: <span class="font-medium">-{{ formatCurrency(order.discount_amount) }}</span></span>
                        <span class="text-gray-700 dark:text-gray-200 font-semibold">Total: {{ formatCurrency(order.total_amount ?? order.total) }}</span>
                        <span v-if="order.change_amount && Number(order.change_amount) > 0" class="text-gray-500 dark:text-gray-400">Change: <span class="font-medium text-gray-900 dark:text-white">{{ formatCurrency(order.change_amount) }}</span></span>
                      </div>
                    </td>
                  </tr>
                  <!-- Split payment breakdown row -->
                  <tr v-if="expandedPaymentOrderId === order.id && order.payment_method === 'split'" class="bg-purple-50 dark:bg-purple-900/10">
                    <td colspan="7" class="px-6 py-3">
                      <div v-if="pos.orderPaymentsLoading" class="text-xs text-gray-400 dark:text-gray-500">Loading payment breakdown…</div>
                      <div v-else-if="pos.orderPayments.length === 0" class="text-xs text-gray-400 dark:text-gray-500">No payment records found.</div>
                      <div v-else class="flex flex-wrap gap-3">
                        <div
                          v-for="(payment, i) in pos.orderPayments"
                          :key="i"
                          class="flex items-center gap-2 rounded-lg bg-white dark:bg-gray-900 border border-purple-200 dark:border-purple-800 px-3 py-2 text-xs shadow-sm"
                        >
                          <span class="font-semibold text-purple-700 dark:text-purple-300 capitalize">{{ payment.payment_method?.replace('_', ' ') }}</span>
                          <span class="text-gray-400 dark:text-gray-500">·</span>
                          <span class="font-medium text-gray-900 dark:text-white">{{ formatCurrency(payment.amount) }}</span>
                          <span v-if="payment.reference" class="font-mono text-gray-400 dark:text-gray-500">{{ payment.reference }}</span>
                        </div>
                      </div>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
          <Pagination :meta="pos.ordersMeta" @change="pos.fetchOrders" />
        </div>
      </div>

      <!-- Loyalty Programs tab -->
      <div v-if="activeTab === 'loyalty'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Loyalty programs table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Program Name</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Points / Currency Unit</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Redemption Rate</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Description</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="pos.loyaltyProgramsLoading">
                  <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="pos.loyaltyPrograms.length === 0">
                  <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No loyalty programs found.</td>
                </tr>
                <tr
                  v-for="program in pos.loyaltyPrograms"
                  v-else
                  :key="program.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ program.name }}</td>
                  <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">{{ parseFloat(program.points_per_currency_unit).toFixed(2) }}</td>
                  <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">{{ parseFloat(program.redemption_rate).toFixed(0) }} pts</td>
                  <td class="px-4 py-3"><StatusBadge :status="program.is_active ? 'active' : 'inactive'" /></td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 truncate max-w-xs">{{ program.description ?? '—' }}</td>
                  <td class="px-4 py-3 text-right">
                    <div class="inline-flex items-center gap-2">
                      <button
                        type="button"
                        class="rounded px-2 py-1 text-xs font-medium text-indigo-600 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-indigo-900/30 transition-colors"
                        @click="openLoyaltyProgramModal(program)"
                      >Edit</button>
                      <button
                        type="button"
                        class="rounded px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/30 transition-colors"
                        @click="confirmDeleteLoyaltyProgram(program)"
                      >Delete</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="pos.loyaltyProgramsMeta" @change="pos.fetchLoyaltyPrograms" />
        </div>
      </div>

      <!-- Loyalty Cards tab -->
      <div v-if="activeTab === 'loyalty-cards'">
        <div class="mb-4 flex items-center justify-between">
          <p class="text-sm text-gray-500 dark:text-gray-400">View and manage customer loyalty card balances. Use Accrue to add points after a purchase and Redeem to process a points redemption.</p>
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
            @click="openAccrueModal(null)"
          >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
            Accrue Points
          </button>
        </div>
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Loyalty cards table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Card ID</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Customer ID</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Program ID</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Points Balance</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="pos.loyaltyCardsLoading">
                  <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="pos.loyaltyCards.length === 0">
                  <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No loyalty cards found.</td>
                </tr>
                <tr
                  v-for="card in pos.loyaltyCards"
                  v-else
                  :key="card.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-mono text-indigo-700 dark:text-indigo-400">{{ card.id?.slice(0, 8) }}…</td>
                  <td class="px-4 py-3 text-sm font-mono text-gray-500 dark:text-gray-400">{{ card.customer_id?.slice(0, 8) }}…</td>
                  <td class="px-4 py-3 text-sm font-mono text-gray-500 dark:text-gray-400">{{ card.program_id?.slice(0, 8) }}…</td>
                  <td class="px-4 py-3 text-sm text-right font-semibold text-indigo-700 dark:text-indigo-400">{{ Number(card.points_balance).toLocaleString() }}</td>
                  <td class="px-4 py-3"><StatusBadge :status="card.is_active ? 'active' : 'inactive'" /></td>
                  <td class="px-4 py-3 text-right">
                    <div class="inline-flex items-center gap-2">
                      <button
                        type="button"
                        class="rounded px-2 py-1 text-xs font-medium text-emerald-600 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-900/30 transition-colors"
                        @click="openAccrueModal(card)"
                      >Accrue</button>
                      <button
                        type="button"
                        class="rounded px-2 py-1 text-xs font-medium text-indigo-600 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-indigo-900/30 transition-colors"
                        @click="openRedeemModal(card)"
                      >Redeem</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="pos.loyaltyCardsMeta" @change="pos.fetchLoyaltyCards" />
        </div>
      </div>

      <!-- Discounts tab -->
      <div v-if="activeTab === 'discounts'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Discounts table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Code</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Name</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Type</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Value</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Usage</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Expires</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="pos.discountsLoading">
                  <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="pos.discounts.length === 0">
                  <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No discount codes found.</td>
                </tr>
                <tr
                  v-for="discount in pos.discounts"
                  v-else
                  :key="discount.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-mono font-bold text-indigo-700 dark:text-indigo-400">{{ discount.code }}</td>
                  <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ discount.name }}</td>
                  <td class="px-4 py-3">
                    <span :class="discount.type === 'percentage' ? 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize">
                      {{ discount.type === 'percentage' ? 'Percentage' : 'Fixed Amount' }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900 dark:text-white">
                    <span v-if="discount.type === 'percentage'">{{ Number(discount.value).toFixed(2) }}%</span>
                    <span v-else>{{ formatCurrency(discount.value) }}</span>
                  </td>
                  <td class="px-4 py-3 text-sm text-right text-gray-500 dark:text-gray-400">
                    {{ discount.times_used }}{{ discount.usage_limit ? ' / ' + discount.usage_limit : '' }}
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ formatDate(discount.expires_at) }}</td>
                  <td class="px-4 py-3"><StatusBadge :status="discount.is_active ? 'active' : 'inactive'" /></td>
                  <td class="px-4 py-3 text-right">
                    <div class="inline-flex items-center gap-2">
                      <button
                        type="button"
                        class="rounded px-2 py-1 text-xs font-medium text-indigo-600 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-indigo-900/30 transition-colors"
                        @click="openDiscountModal(discount)"
                      >Edit</button>
                      <button
                        type="button"
                        class="rounded px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/30 transition-colors"
                        @click="confirmDeleteDiscount(discount)"
                      >Delete</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="pos.discountsMeta" @change="pos.fetchDiscounts" />
        </div>
      </div>
    </div>
  </AppLayout>

  <!-- ════════════════════════════════════════════════════════════════════
       MODALS
  ════════════════════════════════════════════════════════════════════ -->

  <!-- Terminal create / edit modal -->
  <Teleport to="body">
    <div
      v-if="showTerminalModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4"
      role="dialog"
      aria-modal="true"
    >
      <div class="absolute inset-0 bg-black/50" @click="showTerminalModal = false" />
      <div class="relative w-full max-w-md rounded-xl bg-white dark:bg-gray-900 shadow-2xl border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
          <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ terminalForm.id ? 'Edit Terminal' : 'Add Terminal' }}</h2>
          <button type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" aria-label="Close" @click="showTerminalModal = false">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>
        <form class="px-6 py-5 space-y-4" @submit.prevent="submitTerminalForm">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="terminal-name">Name <span class="text-red-500">*</span></label>
            <input id="terminal-name" v-model="terminalForm.name" type="text" required maxlength="255" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="e.g. Checkout 1" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="terminal-location">Location</label>
            <input id="terminal-location" v-model="terminalForm.location" type="text" maxlength="255" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="e.g. Main Floor" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="terminal-balance">Opening Balance</label>
            <input id="terminal-balance" v-model="terminalForm.opening_balance" type="number" min="0" step="0.01" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="0.00" />
          </div>
          <div class="flex items-center gap-2">
            <input id="terminal-active" v-model="terminalForm.is_active" type="checkbox" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500" />
            <label for="terminal-active" class="text-sm font-medium text-gray-700 dark:text-gray-300">Active</label>
          </div>
          <div v-if="modalError" class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-3 py-2 text-sm text-red-700 dark:text-red-400">{{ modalError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" class="rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors" @click="showTerminalModal = false">Cancel</button>
            <button type="submit" :disabled="pos.loading" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60 transition-colors">{{ pos.loading ? 'Saving…' : (terminalForm.id ? 'Save Changes' : 'Create Terminal') }}</button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>

  <!-- Delete terminal confirmation -->
  <Teleport to="body">
    <div v-if="showDeleteTerminalConfirm" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="alertdialog" aria-modal="true">
      <div class="absolute inset-0 bg-black/50" @click="showDeleteTerminalConfirm = false" />
      <div class="relative w-full max-w-sm rounded-xl bg-white dark:bg-gray-900 shadow-2xl border border-gray-200 dark:border-gray-700 p-6 space-y-4">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Delete Terminal</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">Are you sure you want to delete <strong class="text-gray-900 dark:text-white">{{ terminalToDelete?.name }}</strong>? This action cannot be undone.</p>
        <div v-if="modalError" class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-3 py-2 text-sm text-red-700 dark:text-red-400">{{ modalError }}</div>
        <div class="flex justify-end gap-3">
          <button type="button" class="rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors" @click="showDeleteTerminalConfirm = false">Cancel</button>
          <button type="button" :disabled="pos.loading" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-60 transition-colors" @click="executeDeleteTerminal">{{ pos.loading ? 'Deleting…' : 'Delete' }}</button>
        </div>
      </div>
    </div>
  </Teleport>

  <!-- Open Session modal -->
  <Teleport to="body">
    <div v-if="showOpenSessionModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
      <div class="absolute inset-0 bg-black/50" @click="showOpenSessionModal = false" />
      <div class="relative w-full max-w-md rounded-xl bg-white dark:bg-gray-900 shadow-2xl border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
          <h2 class="text-base font-semibold text-gray-900 dark:text-white">Open Session</h2>
          <button type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" aria-label="Close" @click="showOpenSessionModal = false">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>
        <form class="px-6 py-5 space-y-4" @submit.prevent="submitOpenSession">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="session-terminal">Terminal <span class="text-red-500">*</span></label>
            <select
              id="session-terminal"
              v-model="sessionForm.terminal_id"
              required
              class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
              <option value="">Select a terminal…</option>
              <option
                v-for="terminal in activeTerminals"
                :key="terminal.id"
                :value="terminal.id"
              >{{ terminal.name }}{{ terminal.location ? ' — ' + terminal.location : '' }}</option>
            </select>
            <p v-if="activeTerminals.length === 0" class="mt-1 text-xs text-amber-600 dark:text-amber-400">No active terminals available. Create a terminal first.</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="session-opening-cash">Opening Cash</label>
            <input id="session-opening-cash" v-model="sessionForm.opening_cash" type="number" min="0" step="0.01" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="0.00" />
          </div>
          <div v-if="modalError" class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-3 py-2 text-sm text-red-700 dark:text-red-400">{{ modalError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" class="rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors" @click="showOpenSessionModal = false">Cancel</button>
            <button type="submit" :disabled="pos.loading" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60 transition-colors">{{ pos.loading ? 'Opening…' : 'Open Session' }}</button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>

  <!-- Close Session modal -->
  <Teleport to="body">
    <div v-if="showCloseSessionModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
      <div class="absolute inset-0 bg-black/50" @click="showCloseSessionModal = false" />
      <div class="relative w-full max-w-sm rounded-xl bg-white dark:bg-gray-900 shadow-2xl border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
          <h2 class="text-base font-semibold text-gray-900 dark:text-white">Close Session</h2>
          <button type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" aria-label="Close" @click="showCloseSessionModal = false">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>
        <form class="px-6 py-5 space-y-4" @submit.prevent="submitCloseSession">
          <p class="text-sm text-gray-600 dark:text-gray-400">Close session for terminal <strong class="text-gray-900 dark:text-white">{{ sessionToClose?.terminal_name ?? sessionToClose?.terminal_id }}</strong>.</p>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="session-closing-cash">Closing Cash</label>
            <input id="session-closing-cash" v-model="closeSessionForm.closing_cash" type="number" min="0" step="0.01" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="0.00" />
          </div>
          <div v-if="modalError" class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-3 py-2 text-sm text-red-700 dark:text-red-400">{{ modalError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" class="rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors" @click="showCloseSessionModal = false">Cancel</button>
            <button type="submit" :disabled="pos.loading" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700 disabled:opacity-60 transition-colors">{{ pos.loading ? 'Closing…' : 'Close Session' }}</button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>

  <!-- Place Order modal -->
  <Teleport to="body">
    <div v-if="showPlaceOrderModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
      <div class="absolute inset-0 bg-black/50" @click="showPlaceOrderModal = false" />
      <div class="relative w-full max-w-2xl rounded-xl bg-white dark:bg-gray-900 shadow-2xl border border-gray-200 dark:border-gray-700 max-h-[90vh] flex flex-col">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between shrink-0">
          <h2 class="text-base font-semibold text-gray-900 dark:text-white">Place Order</h2>
          <button type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" aria-label="Close" @click="showPlaceOrderModal = false">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>
        <form class="px-6 py-5 space-y-5 overflow-y-auto flex-1" @submit.prevent="submitPlaceOrder">
          <!-- Session & Customer -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="order-session">Session ID <span class="text-red-500">*</span></label>
              <input id="order-session" v-model="orderForm.session_id" type="text" required class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono" placeholder="Session UUID" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="order-customer">Customer ID</label>
              <input id="order-customer" v-model="orderForm.customer_id" type="text" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono" placeholder="UUID (optional)" />
            </div>
          </div>
          <!-- Payment method -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="order-payment">Payment Method</label>
              <select id="order-payment" v-model="orderForm.payment_method" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="cash">Cash</option>
                <option value="card">Card</option>
                <option value="digital_wallet">Digital Wallet</option>
                <option value="credit">Credit</option>
              </select>
            </div>
            <div v-if="orderForm.payment_method === 'cash'">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="order-cash-tendered">Cash Tendered <span class="text-red-500">*</span></label>
              <input id="order-cash-tendered" v-model="orderForm.cash_tendered" type="number" min="0" step="0.01" :required="orderForm.payment_method === 'cash'" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="0.00" />
            </div>
          </div>
          <!-- Discount code -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="order-discount-code">Discount Code</label>
            <div class="flex gap-2">
              <input
                id="order-discount-code"
                :value="orderForm.discount_code"
                type="text"
                maxlength="50"
                class="flex-1 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono"
                placeholder="SAVE10 (optional)"
                @input="e => { orderForm.discount_code = e.target.value.toUpperCase(); pos.clearDiscountValidation(); }"
              />
              <button
                type="button"
                :disabled="!orderForm.discount_code || pos.discountValidationLoading"
                class="rounded-lg border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 disabled:opacity-40 transition-colors"
                @click="handleValidateDiscount"
              >{{ pos.discountValidationLoading ? '…' : 'Validate' }}</button>
            </div>
            <div v-if="pos.discountValidation" class="mt-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 px-3 py-2 text-xs text-emerald-700 dark:text-emerald-400">
              ✓ <strong>{{ pos.discountValidation.name }}</strong> — saves {{ pos.discountValidation.type === 'percentage' ? Number(pos.discountValidation.value).toFixed(2) + '%' : formatCurrency(pos.discountValidation.value) }}
              <span v-if="pos.discountValidation.discount_amount"> ({{ formatCurrency(pos.discountValidation.discount_amount) }} off your current total)</span>
            </div>
          </div>
          <!-- Line items -->
          <div>
            <div class="flex items-center justify-between mb-2">
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Line Items <span class="text-red-500">*</span></label>
              <button type="button" class="text-xs font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 transition-colors" @click="addOrderLine">+ Add Line</button>
            </div>
            <div class="space-y-2">
              <div v-for="(line, i) in orderForm.lines" :key="i" class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 bg-gray-50 dark:bg-gray-800/50">
                <div class="grid grid-cols-12 gap-2 items-end">
                  <div class="col-span-5">
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Product Name <span class="text-red-500">*</span></label>
                    <input v-model="line.product_name" type="text" required maxlength="255" class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-2 py-1 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-500" placeholder="Product name" />
                  </div>
                  <div class="col-span-2">
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Qty <span class="text-red-500">*</span></label>
                    <input v-model="line.quantity" type="number" min="0.01" step="any" required class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-2 py-1 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-500" placeholder="1" />
                  </div>
                  <div class="col-span-2">
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Unit Price <span class="text-red-500">*</span></label>
                    <input v-model="line.unit_price" type="number" min="0" step="0.01" required class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-2 py-1 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-500" placeholder="0.00" />
                  </div>
                  <div class="col-span-1">
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Disc%</label>
                    <input v-model="line.discount" type="number" min="0" max="100" step="0.01" class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-2 py-1 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-500" placeholder="0" />
                  </div>
                  <div class="col-span-1">
                    <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Tax%</label>
                    <input v-model="line.tax_rate" type="number" min="0" max="100" step="0.01" class="w-full rounded border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-2 py-1 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-500" placeholder="0" />
                  </div>
                  <div class="col-span-1 flex justify-center">
                    <button v-if="orderForm.lines.length > 1" type="button" class="text-red-500 hover:text-red-700 dark:hover:text-red-400 transition-colors mt-4" aria-label="Remove line" @click="removeOrderLine(i)">
                      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>
            <div class="mt-3 flex justify-end">
              <span class="text-sm font-semibold text-gray-900 dark:text-white">Estimated Total: {{ formatCurrency(orderTotal) }}</span>
            </div>
          </div>
          <div v-if="modalError" class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-3 py-2 text-sm text-red-700 dark:text-red-400">{{ modalError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" class="rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors" @click="showPlaceOrderModal = false">Cancel</button>
            <button type="submit" :disabled="pos.loading" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60 transition-colors">{{ pos.loading ? 'Placing…' : 'Place Order' }}</button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>

  <!-- Refund Order confirmation -->
  <Teleport to="body">
    <div v-if="showRefundConfirm" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="alertdialog" aria-modal="true">
      <div class="absolute inset-0 bg-black/50" @click="showRefundConfirm = false" />
      <div class="relative w-full max-w-sm rounded-xl bg-white dark:bg-gray-900 shadow-2xl border border-gray-200 dark:border-gray-700 p-6 space-y-4">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Refund Order</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">Are you sure you want to refund order <strong class="text-gray-900 dark:text-white font-mono">{{ orderToRefund?.order_number ?? orderToRefund?.id }}</strong>? This action cannot be undone.</p>
        <div v-if="modalError" class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-3 py-2 text-sm text-red-700 dark:text-red-400">{{ modalError }}</div>
        <div class="flex justify-end gap-3">
          <button type="button" class="rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors" @click="showRefundConfirm = false">Cancel</button>
          <button type="button" :disabled="pos.loading" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700 disabled:opacity-60 transition-colors" @click="executeRefundOrder">{{ pos.loading ? 'Refunding…' : 'Confirm Refund' }}</button>
        </div>
      </div>
    </div>
  </Teleport>

  <!-- Create / Edit Loyalty Program modal -->
  <Teleport to="body">
    <div v-if="showLoyaltyProgramModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
      <div class="absolute inset-0 bg-black/50" @click="showLoyaltyProgramModal = false" />
      <div class="relative w-full max-w-md rounded-xl bg-white dark:bg-gray-900 shadow-2xl border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
          <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ loyaltyForm.id ? 'Edit Loyalty Program' : 'Create Loyalty Program' }}</h2>
          <button type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" aria-label="Close" @click="showLoyaltyProgramModal = false">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>
        <form class="px-6 py-5 space-y-4" @submit.prevent="submitLoyaltyProgram">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="loyalty-name">Program Name <span class="text-red-500">*</span></label>
            <input id="loyalty-name" v-model="loyaltyForm.name" type="text" required maxlength="150" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="e.g. Gold Rewards" />
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="loyalty-points">Points / Currency Unit</label>
              <input id="loyalty-points" v-model="loyaltyForm.points_per_currency_unit" type="number" min="0.01" step="any" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="1.00" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="loyalty-rate">Redemption Rate (pts)</label>
              <input id="loyalty-rate" v-model="loyaltyForm.redemption_rate" type="number" min="0.01" step="any" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="100" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="loyalty-desc">Description</label>
            <textarea id="loyalty-desc" v-model="loyaltyForm.description" rows="2" maxlength="2000" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none" placeholder="Optional description…" />
          </div>
          <div class="flex items-center gap-2">
            <input id="loyalty-active" v-model="loyaltyForm.is_active" type="checkbox" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500" />
            <label for="loyalty-active" class="text-sm font-medium text-gray-700 dark:text-gray-300">Active</label>
          </div>
          <div v-if="modalError" class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-3 py-2 text-sm text-red-700 dark:text-red-400">{{ modalError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" class="rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors" @click="showLoyaltyProgramModal = false">Cancel</button>
            <button type="submit" :disabled="pos.loyaltyProgramsLoading" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60 transition-colors">{{ pos.loyaltyProgramsLoading ? 'Saving…' : (loyaltyForm.id ? 'Save Changes' : 'Create Program') }}</button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>

  <!-- Delete Loyalty Program confirmation -->
  <Teleport to="body">
    <div v-if="showDeleteLoyaltyConfirm" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="alertdialog" aria-modal="true">
      <div class="absolute inset-0 bg-black/50" @click="showDeleteLoyaltyConfirm = false" />
      <div class="relative w-full max-w-sm rounded-xl bg-white dark:bg-gray-900 shadow-2xl border border-gray-200 dark:border-gray-700 p-6 space-y-4">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Delete Loyalty Program</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">Are you sure you want to delete <strong class="text-gray-900 dark:text-white">{{ loyaltyProgramToDelete?.name }}</strong>? This action cannot be undone.</p>
        <div v-if="modalError" class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-3 py-2 text-sm text-red-700 dark:text-red-400">{{ modalError }}</div>
        <div class="flex justify-end gap-3">
          <button type="button" class="rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors" @click="showDeleteLoyaltyConfirm = false">Cancel</button>
          <button type="button" :disabled="pos.loyaltyProgramsLoading" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-60 transition-colors" @click="executeDeleteLoyaltyProgram">{{ pos.loyaltyProgramsLoading ? 'Deleting…' : 'Delete' }}</button>
        </div>
      </div>
    </div>
  </Teleport>

  <!-- Create / Edit Discount modal -->
  <Teleport to="body">
    <div v-if="showDiscountModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
      <div class="absolute inset-0 bg-black/50" @click="showDiscountModal = false" />
      <div class="relative w-full max-w-md rounded-xl bg-white dark:bg-gray-900 shadow-2xl border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
          <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ discountForm.id ? 'Edit Discount' : 'Create Discount' }}</h2>
          <button type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" aria-label="Close" @click="showDiscountModal = false">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>
        <form class="px-6 py-5 space-y-4" @submit.prevent="submitDiscount">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="discount-code">Code <span class="text-red-500">*</span></label>
              <input id="discount-code" v-model="discountForm.code" type="text" required maxlength="50" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono uppercase" placeholder="SAVE10" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="discount-name">Name <span class="text-red-500">*</span></label>
              <input id="discount-name" v-model="discountForm.name" type="text" required maxlength="150" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Summer Sale" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="discount-type">Type <span class="text-red-500">*</span></label>
              <select id="discount-type" v-model="discountForm.type" required class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="percentage">Percentage</option>
                <option value="fixed_amount">Fixed Amount</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="discount-value">
                Value <span class="text-red-500">*</span>
                <span class="text-gray-400 dark:text-gray-500 font-normal ml-1">{{ discountForm.type === 'percentage' ? '(%)' : '($)' }}</span>
              </label>
              <input id="discount-value" v-model="discountForm.value" type="number" min="0.01" :max="discountForm.type === 'percentage' ? 100 : undefined" step="any" required class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="10" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="discount-limit">Usage Limit</label>
              <input id="discount-limit" v-model="discountForm.usage_limit" type="number" min="1" step="1" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Unlimited" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="discount-expires">Expires At</label>
              <input id="discount-expires" v-model="discountForm.expires_at" type="datetime-local" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="discount-desc">Description</label>
            <textarea id="discount-desc" v-model="discountForm.description" rows="2" maxlength="2000" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none" placeholder="Optional description…" />
          </div>
          <div class="flex items-center gap-2">
            <input id="discount-active" v-model="discountForm.is_active" type="checkbox" class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500" />
            <label for="discount-active" class="text-sm font-medium text-gray-700 dark:text-gray-300">Active</label>
          </div>
          <div v-if="modalError" class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-3 py-2 text-sm text-red-700 dark:text-red-400">{{ modalError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" class="rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors" @click="showDiscountModal = false">Cancel</button>
            <button type="submit" :disabled="pos.discountsLoading" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60 transition-colors">{{ pos.discountsLoading ? 'Saving…' : (discountForm.id ? 'Save Changes' : 'Create Discount') }}</button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>

  <!-- Delete Discount confirmation -->
  <Teleport to="body">
    <div v-if="showDeleteDiscountConfirm" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="alertdialog" aria-modal="true">
      <div class="absolute inset-0 bg-black/50" @click="showDeleteDiscountConfirm = false" />
      <div class="relative w-full max-w-sm rounded-xl bg-white dark:bg-gray-900 shadow-2xl border border-gray-200 dark:border-gray-700 p-6 space-y-4">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Delete Discount</h2>
        <p class="text-sm text-gray-600 dark:text-gray-400">Are you sure you want to delete discount <strong class="text-gray-900 dark:text-white font-mono">{{ discountToDelete?.code }}</strong>? This action cannot be undone.</p>
        <div v-if="modalError" class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-3 py-2 text-sm text-red-700 dark:text-red-400">{{ modalError }}</div>
        <div class="flex justify-end gap-3">
          <button type="button" class="rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors" @click="showDeleteDiscountConfirm = false">Cancel</button>
          <button type="button" :disabled="pos.discountsLoading" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-60 transition-colors" @click="executeDeleteDiscount">{{ pos.discountsLoading ? 'Deleting…' : 'Delete' }}</button>
        </div>
      </div>
    </div>
  </Teleport>

  <!-- Accrue Loyalty Points modal -->
  <Teleport to="body">
    <div v-if="showAccrueModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
      <div class="absolute inset-0 bg-black/50" @click="showAccrueModal = false" />
      <div class="relative w-full max-w-md rounded-xl bg-white dark:bg-gray-900 shadow-2xl border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
          <h2 class="text-base font-semibold text-gray-900 dark:text-white">Accrue Loyalty Points</h2>
          <button type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" aria-label="Close" @click="showAccrueModal = false">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>
        <form class="px-6 py-5 space-y-4" @submit.prevent="submitAccruePoints">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="accrue-program">Loyalty Program <span class="text-red-500">*</span></label>
            <select id="accrue-program" v-model="accrueForm.program_id" required class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
              <option value="">Select a program…</option>
              <option v-for="prog in pos.loyaltyPrograms" :key="prog.id" :value="prog.id">{{ prog.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="accrue-customer">Customer ID <span class="text-red-500">*</span></label>
            <input id="accrue-customer" v-model="accrueForm.customer_id" type="text" required class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono" placeholder="Customer UUID" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="accrue-amount">Order Amount <span class="text-red-500">*</span></label>
            <input id="accrue-amount" v-model="accrueForm.order_amount" type="number" min="0.01" step="0.01" required class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="0.00" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="accrue-ref">Reference</label>
            <input id="accrue-ref" v-model="accrueForm.reference" type="text" maxlength="100" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Order # or reference (optional)" />
          </div>
          <div v-if="modalError" class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-3 py-2 text-sm text-red-700 dark:text-red-400">{{ modalError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" class="rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors" @click="showAccrueModal = false">Cancel</button>
            <button type="submit" :disabled="pos.loyaltyCardsActionLoading" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60 transition-colors">{{ pos.loyaltyCardsActionLoading ? 'Accruing…' : 'Accrue Points' }}</button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>

  <!-- Redeem Loyalty Points modal -->
  <Teleport to="body">
    <div v-if="showRedeemModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
      <div class="absolute inset-0 bg-black/50" @click="showRedeemModal = false" />
      <div class="relative w-full max-w-md rounded-xl bg-white dark:bg-gray-900 shadow-2xl border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
          <h2 class="text-base font-semibold text-gray-900 dark:text-white">Redeem Loyalty Points</h2>
          <button type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" aria-label="Close" @click="showRedeemModal = false">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </div>
        <form class="px-6 py-5 space-y-4" @submit.prevent="submitRedeemPoints">
          <div>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
              Card: <span class="font-mono text-gray-900 dark:text-white">{{ redeemForm.card_id ? redeemForm.card_id.slice(0, 8) + '…' : '—' }}</span>
              · Balance: <span class="font-semibold text-indigo-700 dark:text-indigo-400">{{ redeemTargetCard?.points_balance ? Number(redeemTargetCard.points_balance).toLocaleString() : 0 }} pts</span>
            </p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="redeem-points">Points to Redeem <span class="text-red-500">*</span></label>
            <input id="redeem-points" v-model="redeemForm.points_to_redeem" type="number" min="1" step="1" required class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500" :max="redeemTargetCard?.points_balance ?? undefined" placeholder="0" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1" for="redeem-ref">Reference</label>
            <input id="redeem-ref" v-model="redeemForm.reference" type="text" maxlength="100" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Order # or reference (optional)" />
          </div>
          <div v-if="modalError" class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-3 py-2 text-sm text-red-700 dark:text-red-400">{{ modalError }}</div>
          <div class="flex justify-end gap-3 pt-2">
            <button type="button" class="rounded-lg border border-gray-300 dark:border-gray-700 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors" @click="showRedeemModal = false">Cancel</button>
            <button type="submit" :disabled="pos.loyaltyCardsActionLoading" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60 transition-colors">{{ pos.loyaltyCardsActionLoading ? 'Redeeming…' : 'Redeem Points' }}</button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import StatCard from '@/components/StatCard.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import Pagination from '@/components/Pagination.vue';
import { usePosStore } from '@/stores/pos';
import { useFormatters } from '@/composables/useFormatters';

const { formatDate, formatCurrency, formatDateTime } = useFormatters();
const pos = usePosStore();

// ── Tab state ──────────────────────────────────────────────────────────────

const activeTab = ref('terminals');
const expandedOrderId = ref(null);      // for line items expansion
const expandedPaymentOrderId = ref(null); // for split payment expansion

const tabs = computed(() => [
    { key: 'terminals',     label: 'Terminals',        count: pos.terminalsMeta.total || null },
    { key: 'sessions',      label: 'Sessions',         count: pos.sessionsMeta.total || null },
    { key: 'orders',        label: 'Orders',           count: pos.ordersMeta.total || null },
    { key: 'loyalty',       label: 'Loyalty Programs', count: null },
    { key: 'loyalty-cards', label: 'Loyalty Cards',    count: null },
    { key: 'discounts',     label: 'Discounts',        count: null },
]);

// Active (non-deleted) terminals for use in dropdowns
const activeTerminals = computed(() => pos.terminals.filter(t => t.is_active !== false));

function switchTab(key) {
    activeTab.value = key;
    if (key === 'terminals') pos.fetchTerminals();
    else if (key === 'sessions') pos.fetchSessions();
    else if (key === 'orders') pos.fetchOrders();
    else if (key === 'loyalty' && pos.loyaltyPrograms.length === 0) pos.fetchLoyaltyPrograms();
    else if (key === 'loyalty-cards' && pos.loyaltyCards.length === 0) pos.fetchLoyaltyCards();
    else if (key === 'discounts' && pos.discounts.length === 0) pos.fetchDiscounts();
}

function toggleOrderLines(orderId) {
    expandedPaymentOrderId.value = null;
    expandedOrderId.value = expandedOrderId.value === orderId ? null : orderId;
}

function togglePaymentBreakdown(orderId) {
    expandedOrderId.value = null;
    if (expandedPaymentOrderId.value === orderId) {
        expandedPaymentOrderId.value = null;
    } else {
        expandedPaymentOrderId.value = orderId;
        pos.fetchOrderPayments(orderId);
    }
}

// ── Shared modal error ─────────────────────────────────────────────────────

const modalError = ref(null);

function clearModalError() {
    modalError.value = null;
}

// ── Terminal modal ─────────────────────────────────────────────────────────

const showTerminalModal = ref(false);
const terminalForm = ref({ id: null, name: '', location: '', opening_balance: '', is_active: true });

function openTerminalModal(terminal) {
    clearModalError();
    if (terminal) {
        terminalForm.value = {
            id: terminal.id,
            name: terminal.name ?? '',
            location: terminal.location ?? '',
            opening_balance: terminal.opening_balance ?? '',
            is_active: terminal.is_active ?? true,
        };
    } else {
        terminalForm.value = { id: null, name: '', location: '', opening_balance: '', is_active: true };
    }
    showTerminalModal.value = true;
}

async function submitTerminalForm() {
    clearModalError();
    const payload = { name: terminalForm.value.name, is_active: terminalForm.value.is_active };
    if (terminalForm.value.location) payload.location = terminalForm.value.location;
    if (terminalForm.value.opening_balance !== '') payload.opening_balance = terminalForm.value.opening_balance;
    try {
        if (terminalForm.value.id) {
            await pos.updateTerminal(terminalForm.value.id, payload);
        } else {
            await pos.createTerminal(payload);
        }
        showTerminalModal.value = false;
    } catch (e) {
        modalError.value = e.response?.data?.message ?? 'An error occurred.';
    }
}

// ── Delete terminal ────────────────────────────────────────────────────────

const showDeleteTerminalConfirm = ref(false);
const terminalToDelete = ref(null);

function confirmDeleteTerminal(terminal) {
    clearModalError();
    terminalToDelete.value = terminal;
    showDeleteTerminalConfirm.value = true;
}

async function executeDeleteTerminal() {
    clearModalError();
    try {
        await pos.deleteTerminal(terminalToDelete.value.id);
        showDeleteTerminalConfirm.value = false;
        terminalToDelete.value = null;
    } catch (e) {
        modalError.value = e.response?.data?.message ?? 'An error occurred.';
    }
}

// ── Open session modal ─────────────────────────────────────────────────────

const showOpenSessionModal = ref(false);
const sessionForm = ref({ terminal_id: '', cashier_id: '', opening_cash: '' });

async function submitOpenSession() {
    clearModalError();
    const payload = { terminal_id: sessionForm.value.terminal_id };
    if (sessionForm.value.cashier_id) payload.cashier_id = sessionForm.value.cashier_id;
    if (sessionForm.value.opening_cash !== '') payload.opening_cash = sessionForm.value.opening_cash;
    try {
        await pos.openSession(payload);
        showOpenSessionModal.value = false;
        sessionForm.value = { terminal_id: '', cashier_id: '', opening_cash: '' };
    } catch (e) {
        modalError.value = e.response?.data?.message ?? 'An error occurred.';
    }
}

// ── Close session modal ────────────────────────────────────────────────────

const showCloseSessionModal = ref(false);
const sessionToClose = ref(null);
const closeSessionForm = ref({ closing_cash: '' });

function openCloseSessionModal(session) {
    clearModalError();
    sessionToClose.value = session;
    closeSessionForm.value = { closing_cash: '' };
    showCloseSessionModal.value = true;
}

async function submitCloseSession() {
    clearModalError();
    const payload = {};
    if (closeSessionForm.value.closing_cash !== '') payload.closing_cash = closeSessionForm.value.closing_cash;
    try {
        await pos.closeSession(sessionToClose.value.id, payload);
        showCloseSessionModal.value = false;
        sessionToClose.value = null;
    } catch (e) {
        modalError.value = e.response?.data?.message ?? 'An error occurred.';
    }
}

// ── Place Order modal ──────────────────────────────────────────────────────

const showPlaceOrderModal = ref(false);

function defaultOrderForm() {
    return {
        session_id: '',
        customer_id: '',
        payment_method: 'cash',
        cash_tendered: '',
        discount_code: '',
        lines: [{ product_name: '', quantity: 1, unit_price: '', discount: '', tax_rate: '' }],
    };
}

const orderForm = ref(defaultOrderForm());

const orderTotal = computed(() => {
    return orderForm.value.lines.reduce((sum, line) => {
        const qty = parseFloat(line.quantity) || 0;
        const price = parseFloat(line.unit_price) || 0;
        const disc = parseFloat(line.discount) || 0;
        const tax = parseFloat(line.tax_rate) || 0;
        return sum + qty * price * (1 - disc / 100) * (1 + tax / 100);
    }, 0);
});

function openPlaceOrderModal() {
    clearModalError();
    pos.clearDiscountValidation();
    orderForm.value = defaultOrderForm();
    showPlaceOrderModal.value = true;
}

function addOrderLine() {
    orderForm.value.lines.push({ product_name: '', quantity: 1, unit_price: '', discount: '', tax_rate: '' });
}

function removeOrderLine(index) {
    orderForm.value.lines.splice(index, 1);
}

async function handleValidateDiscount() {
    if (!orderForm.value.discount_code) return;
    try {
        await pos.validateDiscountCode(orderForm.value.discount_code.toUpperCase(), orderTotal.value);
    } catch (e) {
        // Error is surfaced via pos.error; no additional handling needed here
    }
}

async function submitPlaceOrder() {
    clearModalError();
    const payload = {
        session_id: orderForm.value.session_id,
        payment_method: orderForm.value.payment_method,
        lines: orderForm.value.lines.map(l => {
            const line = { product_name: l.product_name, quantity: l.quantity, unit_price: l.unit_price };
            if (l.discount !== '' && l.discount !== null) line.discount = l.discount;
            if (l.tax_rate !== '' && l.tax_rate !== null) line.tax_rate = l.tax_rate;
            return line;
        }),
    };
    if (orderForm.value.customer_id) payload.customer_id = orderForm.value.customer_id;
    if (orderForm.value.payment_method === 'cash' && orderForm.value.cash_tendered !== '') {
        payload.cash_tendered = orderForm.value.cash_tendered;
    }
    if (orderForm.value.discount_code) {
        payload.discount_code = orderForm.value.discount_code.toUpperCase();
    }
    try {
        await pos.placeOrder(payload);
        showPlaceOrderModal.value = false;
        pos.clearDiscountValidation();
    } catch (e) {
        modalError.value = e.response?.data?.message ?? 'An error occurred.';
    }
}

// ── Refund Order ───────────────────────────────────────────────────────────

const showRefundConfirm = ref(false);
const orderToRefund = ref(null);

function confirmRefundOrder(order) {
    clearModalError();
    orderToRefund.value = order;
    showRefundConfirm.value = true;
}

async function executeRefundOrder() {
    clearModalError();
    try {
        await pos.refundOrder(orderToRefund.value.id);
        showRefundConfirm.value = false;
        orderToRefund.value = null;
    } catch (e) {
        modalError.value = e.response?.data?.message ?? 'An error occurred.';
    }
}

// ── Loyalty Program modal ──────────────────────────────────────────────────

const showLoyaltyProgramModal = ref(false);
const loyaltyForm = ref({ id: null, name: '', points_per_currency_unit: '', redemption_rate: '', description: '', is_active: true });

function openLoyaltyProgramModal(program) {
    clearModalError();
    if (program) {
        loyaltyForm.value = {
            id: program.id,
            name: program.name ?? '',
            points_per_currency_unit: program.points_per_currency_unit ?? '',
            redemption_rate: program.redemption_rate ?? '',
            description: program.description ?? '',
            is_active: program.is_active ?? true,
        };
    } else {
        loyaltyForm.value = { id: null, name: '', points_per_currency_unit: '', redemption_rate: '', description: '', is_active: true };
    }
    showLoyaltyProgramModal.value = true;
}

async function submitLoyaltyProgram() {
    clearModalError();
    const payload = { name: loyaltyForm.value.name, is_active: loyaltyForm.value.is_active };
    if (loyaltyForm.value.points_per_currency_unit !== '') payload.points_per_currency_unit = loyaltyForm.value.points_per_currency_unit;
    if (loyaltyForm.value.redemption_rate !== '') payload.redemption_rate = loyaltyForm.value.redemption_rate;
    if (loyaltyForm.value.description) payload.description = loyaltyForm.value.description;
    try {
        if (loyaltyForm.value.id) {
            await pos.updateLoyaltyProgram(loyaltyForm.value.id, payload);
        } else {
            await pos.createLoyaltyProgram(payload);
        }
        showLoyaltyProgramModal.value = false;
        loyaltyForm.value = { id: null, name: '', points_per_currency_unit: '', redemption_rate: '', description: '', is_active: true };
    } catch (e) {
        modalError.value = e.response?.data?.message ?? 'An error occurred.';
    }
}

// ── Delete Loyalty Program ─────────────────────────────────────────────────

const showDeleteLoyaltyConfirm = ref(false);
const loyaltyProgramToDelete = ref(null);

function confirmDeleteLoyaltyProgram(program) {
    clearModalError();
    loyaltyProgramToDelete.value = program;
    showDeleteLoyaltyConfirm.value = true;
}

async function executeDeleteLoyaltyProgram() {
    clearModalError();
    try {
        await pos.deleteLoyaltyProgram(loyaltyProgramToDelete.value.id);
        showDeleteLoyaltyConfirm.value = false;
        loyaltyProgramToDelete.value = null;
    } catch (e) {
        modalError.value = e.response?.data?.message ?? 'An error occurred.';
    }
}

// ── Discount modal ─────────────────────────────────────────────────────────

const showDiscountModal = ref(false);
const discountForm = ref({ id: null, code: '', name: '', type: 'percentage', value: '', usage_limit: '', expires_at: '', description: '', is_active: true });

function openDiscountModal(discount) {
    clearModalError();
    if (discount) {
        discountForm.value = {
            id: discount.id,
            code: discount.code ?? '',
            name: discount.name ?? '',
            type: discount.type ?? 'percentage',
            value: discount.value ?? '',
            usage_limit: discount.usage_limit ?? '',
            // Normalise to "YYYY-MM-DDTHH:MM" for the datetime-local input.
            // The backend may return a space-separated datetime or an ISO 8601 string.
            expires_at: discount.expires_at ? discount.expires_at.toString().replace(' ', 'T').slice(0, 16) : '',
            description: discount.description ?? '',
            is_active: discount.is_active ?? true,
        };
    } else {
        discountForm.value = { id: null, code: '', name: '', type: 'percentage', value: '', usage_limit: '', expires_at: '', description: '', is_active: true };
    }
    showDiscountModal.value = true;
}

async function submitDiscount() {
    clearModalError();
    const payload = {
        code: discountForm.value.code.toUpperCase(),
        name: discountForm.value.name,
        type: discountForm.value.type,
        value: discountForm.value.value,
        is_active: discountForm.value.is_active,
    };
    if (discountForm.value.usage_limit !== '') payload.usage_limit = discountForm.value.usage_limit;
    if (discountForm.value.expires_at) payload.expires_at = discountForm.value.expires_at;
    if (discountForm.value.description) payload.description = discountForm.value.description;
    try {
        if (discountForm.value.id) {
            await pos.updateDiscount(discountForm.value.id, payload);
        } else {
            await pos.createDiscount(payload);
        }
        showDiscountModal.value = false;
        discountForm.value = { id: null, code: '', name: '', type: 'percentage', value: '', usage_limit: '', expires_at: '', description: '', is_active: true };
    } catch (e) {
        modalError.value = e.response?.data?.message ?? 'An error occurred.';
    }
}

// ── Delete Discount ────────────────────────────────────────────────────────

const showDeleteDiscountConfirm = ref(false);
const discountToDelete = ref(null);

function confirmDeleteDiscount(discount) {
    clearModalError();
    discountToDelete.value = discount;
    showDeleteDiscountConfirm.value = true;
}

async function executeDeleteDiscount() {
    clearModalError();
    try {
        await pos.deleteDiscount(discountToDelete.value.id);
        showDeleteDiscountConfirm.value = false;
        discountToDelete.value = null;
    } catch (e) {
        modalError.value = e.response?.data?.message ?? 'An error occurred.';
    }
}

// ── Loyalty Cards — Accrue modal ───────────────────────────────────────────

const showAccrueModal = ref(false);
const accrueForm = ref({ program_id: '', customer_id: '', order_amount: '', reference: '' });

function openAccrueModal(card) {
    clearModalError();
    accrueForm.value = {
        program_id: card?.program_id ?? '',
        customer_id: card?.customer_id ?? '',
        order_amount: '',
        reference: '',
    };
    // Ensure programs are loaded for the dropdown
    if (pos.loyaltyPrograms.length === 0) pos.fetchLoyaltyPrograms();
    showAccrueModal.value = true;
}

async function submitAccruePoints() {
    clearModalError();
    try {
        await pos.accruePoints({
            program_id: accrueForm.value.program_id,
            customer_id: accrueForm.value.customer_id,
            order_amount: accrueForm.value.order_amount,
            reference: accrueForm.value.reference || undefined,
        });
        showAccrueModal.value = false;
        accrueForm.value = { program_id: '', customer_id: '', order_amount: '', reference: '' };
    } catch (e) {
        modalError.value = e.response?.data?.message ?? 'An error occurred.';
    }
}

// ── Loyalty Cards — Redeem modal ───────────────────────────────────────────

const showRedeemModal = ref(false);
const redeemTargetCard = ref(null);
const redeemForm = ref({ card_id: '', points_to_redeem: '', reference: '' });

function openRedeemModal(card) {
    clearModalError();
    redeemTargetCard.value = card;
    redeemForm.value = { card_id: card.id, points_to_redeem: '', reference: '' };
    showRedeemModal.value = true;
}

async function submitRedeemPoints() {
    clearModalError();
    try {
        await pos.redeemPoints(redeemForm.value.card_id, {
            points_to_redeem: parseInt(redeemForm.value.points_to_redeem, 10),
            reference: redeemForm.value.reference || undefined,
        });
        showRedeemModal.value = false;
        redeemTargetCard.value = null;
        redeemForm.value = { card_id: '', points_to_redeem: '', reference: '' };
    } catch (e) {
        modalError.value = e.response?.data?.message ?? 'An error occurred.';
    }
}

// ── Init ───────────────────────────────────────────────────────────────────

onMounted(() => {
    pos.fetchTerminals();
    pos.fetchSessions();
    pos.fetchOrders();
    pos.fetchDiscounts();
});
</script>
