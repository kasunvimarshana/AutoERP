<template>
  <AppLayout>
    <div class="space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Inventory</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage products, warehouses, stock movements, and valuation.</p>
      </div>

      <!-- Tab navigation -->
      <div class="border-b border-gray-200 dark:border-gray-800">
        <nav class="-mb-px flex gap-6" aria-label="Inventory tabs">
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
          </button>
        </nav>
      </div>

      <div
        v-if="inventory.error"
        class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400"
        role="alert"
      >
        {{ inventory.error }}
      </div>

      <!-- Products tab -->
      <div v-if="activeTab === 'products'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Products table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">SKU</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Product Name</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Category</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Stock Qty</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Unit Price</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="inventory.loading">
                  <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="inventory.products.length === 0">
                  <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No products found.</td>
                </tr>
                <tr
                  v-for="product in inventory.products"
                  v-else
                  :key="product.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-mono text-gray-500 dark:text-gray-400">{{ product.sku ?? '—' }}</td>
                  <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ product.name }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ product.category ?? product.category_name ?? '—' }}</td>
                  <td class="px-4 py-3 text-sm text-right font-medium" :class="stockClass(product)">{{ product.stock_qty ?? product.quantity ?? '—' }}</td>
                  <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">{{ formatCurrency(product.unit_price ?? product.price) }}</td>
                  <td class="px-4 py-3"><StatusBadge :status="stockStatus(product)" /></td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="inventory.meta" @change="inventory.fetchProducts" />
        </div>
      </div>

      <!-- Valuation tab -->
      <div v-if="activeTab === 'valuation'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Valuation ledger table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Product ID</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Type</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Qty</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Unit Cost</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Total Value</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Balance Qty</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Balance Value</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Method</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="inventory.valuationLoading">
                  <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="inventory.valuation.length === 0">
                  <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No valuation entries found.</td>
                </tr>
                <tr
                  v-for="entry in inventory.valuation"
                  v-else
                  :key="entry.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-mono text-gray-500 dark:text-gray-400">{{ entry.product_id?.slice(0, 8) }}…</td>
                  <td class="px-4 py-3 text-sm">
                    <StatusBadge :status="entry.movement_type" />
                  </td>
                  <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">{{ parseFloat(entry.qty).toFixed(2) }}</td>
                  <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">{{ formatCurrency(entry.unit_cost) }}</td>
                  <td class="px-4 py-3 text-sm text-right font-medium" :class="parseFloat(entry.total_value) < 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-700 dark:text-emerald-400'">
                    {{ formatCurrency(entry.total_value) }}
                  </td>
                  <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">{{ parseFloat(entry.running_balance_qty).toFixed(2) }}</td>
                  <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">{{ formatCurrency(entry.running_balance_value) }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 capitalize">{{ entry.valuation_method?.replace('_', ' ') ?? '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="inventory.valuationMeta" @change="inventory.fetchValuation" />
        </div>
      </div>

      <!-- Lots & Serials tab -->
      <div v-if="activeTab === 'lots'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Lots and serials table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Lot / Serial #</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Product ID</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Type</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Qty</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Manufacture Date</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Expiry Date</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="inventory.lotsLoading">
                  <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="inventory.lots.length === 0">
                  <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No lots or serial numbers found.</td>
                </tr>
                <tr
                  v-for="lot in inventory.lots"
                  v-else
                  :key="lot.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-mono font-medium text-gray-900 dark:text-white">{{ lot.lot_number }}</td>
                  <td class="px-4 py-3 text-sm font-mono text-gray-500 dark:text-gray-400">{{ lot.product_id?.slice(0, 8) }}…</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 capitalize">{{ lot.tracking_type ?? '—' }}</td>
                  <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">{{ parseFloat(lot.qty).toFixed(2) }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ lot.manufacture_date ?? '—' }}</td>
                  <td class="px-4 py-3 text-sm" :class="isExpiringSoon(lot) ? 'text-amber-600 dark:text-amber-400 font-medium' : 'text-gray-500 dark:text-gray-400'">
                    {{ lot.expiry_date ?? '—' }}
                  </td>
                  <td class="px-4 py-3"><StatusBadge :status="lot.status" /></td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="inventory.lotsMeta" @change="inventory.fetchLots" />
        </div>
      </div>

      <!-- Cycle Counts tab -->
      <div v-if="activeTab === 'cyclecounts'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Cycle counts table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Reference</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Warehouse ID</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Count Date</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Notes</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="inventory.cycleCountsLoading">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="inventory.cycleCounts.length === 0">
                  <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No cycle counts found.</td>
                </tr>
                <tr
                  v-for="cc in inventory.cycleCounts"
                  v-else
                  :key="cc.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-mono font-medium text-indigo-700 dark:text-indigo-400">{{ cc.reference }}</td>
                  <td class="px-4 py-3 text-sm font-mono text-gray-500 dark:text-gray-400">{{ cc.warehouse_id?.slice(0, 8) }}…</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ cc.count_date ?? '—' }}</td>
                  <td class="px-4 py-3"><StatusBadge :status="cc.status" /></td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 truncate max-w-xs">{{ cc.notes ?? '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="inventory.cycleCountsMeta" @change="inventory.fetchCycleCounts" />
        </div>
      </div>

      <!-- Product Variants tab -->
      <div v-if="activeTab === 'variants'">
        <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Product variants table">
              <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">SKU</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Variant Name</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Attributes</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Unit Price</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Cost Price</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-if="inventory.variantsLoading">
                  <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
                </tr>
                <tr v-else-if="inventory.variants.length === 0">
                  <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No product variants found.</td>
                </tr>
                <tr
                  v-for="variant in inventory.variants"
                  v-else
                  :key="variant.id"
                  class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
                >
                  <td class="px-4 py-3 text-sm font-mono font-medium text-indigo-700 dark:text-indigo-400">{{ variant.sku }}</td>
                  <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ variant.name }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                    <span
                      v-for="(val, key) in (variant.attributes ?? {})"
                      :key="key"
                      class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 mr-1"
                    >{{ key }}: {{ val }}</span>
                    <span v-if="!variant.attributes || Object.keys(variant.attributes).length === 0" class="text-gray-400">—</span>
                  </td>
                  <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white">{{ formatCurrency(variant.unit_price) }}</td>
                  <td class="px-4 py-3 text-sm text-right text-gray-500 dark:text-gray-400">{{ formatCurrency(variant.cost_price) }}</td>
                  <td class="px-4 py-3"><StatusBadge :status="variant.is_active ? 'active' : 'inactive'" /></td>
                </tr>
              </tbody>
            </table>
          </div>
          <Pagination :meta="inventory.variantsMeta" @change="inventory.fetchVariants" />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import Pagination from '@/components/Pagination.vue';
import { useInventoryStore } from '@/stores/inventory';
import { useFormatters } from '@/composables/useFormatters';
const { formatCurrency } = useFormatters();

const inventory = useInventoryStore();

const tabs = [
    { key: 'products',     label: 'Products' },
    { key: 'valuation',    label: 'Stock Valuation' },
    { key: 'lots',         label: 'Lots & Serials' },
    { key: 'cyclecounts',  label: 'Cycle Counts' },
    { key: 'variants',     label: 'Product Variants' },
];
const activeTab = ref('products');

function switchTab(key) {
    activeTab.value = key;
    if (key === 'products' && inventory.products.length === 0) inventory.fetchProducts();
    if (key === 'valuation' && inventory.valuation.length === 0) inventory.fetchValuation();
    if (key === 'lots' && inventory.lots.length === 0) inventory.fetchLots();
    if (key === 'cyclecounts' && inventory.cycleCounts.length === 0) inventory.fetchCycleCounts();
    if (key === 'variants' && inventory.variants.length === 0) inventory.fetchVariants();
}

function stockStatus(product) {
    const qty = parseFloat(product.stock_qty ?? product.quantity ?? 0);
    const reorder = parseFloat(product.reorder_point ?? 0);
    if (qty <= 0) return 'out_of_stock';
    if (reorder > 0 && qty <= reorder) return 'low_stock';
    return 'available';
}

function stockClass(product) {
    const s = stockStatus(product);
    if (s === 'out_of_stock') return 'text-red-600 dark:text-red-400';
    if (s === 'low_stock') return 'text-amber-600 dark:text-amber-400';
    return 'text-gray-900 dark:text-white';
}

function isExpiringSoon(lot) {
    if (!lot.expiry_date) return false;
    const diff = (new Date(lot.expiry_date) - Date.now()) / 86400000;
    return diff >= 0 && diff <= 30;
}

onMounted(() => inventory.fetchProducts());
</script>
