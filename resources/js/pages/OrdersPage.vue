<template>
  <div class="space-y-4">
    <PageHeader title="Orders" subtitle="Sales and purchase order management">
      <template #actions>
        <input
          v-model="search"
          type="search"
          placeholder="Search orders…"
          class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-48"
          @input="onSearchInput"
        />
        <button
          v-if="auth.hasPermission('order.create')"
          class="flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-1.5 rounded-lg transition-colors"
          @click="openCreate"
        >
          <span class="text-base leading-none">+</span> New Order
        </button>
      </template>
    </PageHeader>

    <div v-if="loading" class="flex items-center justify-center py-16">
      <AppSpinner size="lg" />
    </div>
    <div v-else-if="error" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
      {{ error }}
    </div>

    <div v-else class="bg-white shadow rounded-lg overflow-hidden">
      <AppEmptyState
        v-if="items.length === 0"
        icon="🛒"
        title="No orders found"
        :message="search ? 'Try a different search term.' : 'No orders have been created yet.'"
      />
      <template v-else>
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ref #</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="order in items" :key="order.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-sm font-mono text-gray-900">{{ order.reference_number }}</td>
              <td class="px-4 py-3 text-sm text-gray-500 capitalize">{{ order.order_type }}</td>
              <td class="px-4 py-3 text-sm"><StatusBadge :status="order.status" /></td>
              <td class="px-4 py-3 text-sm text-gray-900 text-right font-medium">{{ order.total_amount }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ order.created_at?.substring(0, 10) }}</td>
              <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button
                    v-if="order.status === 'draft' && auth.hasPermission('order.update')"
                    class="text-xs text-green-600 hover:underline"
                    :disabled="actionOrderId === order.id"
                    @click="confirmOrder(order)"
                  >Confirm</button>
                  <button
                    v-if="order.status === 'draft' && auth.hasPermission('order.update')"
                    class="text-xs text-red-500 hover:underline"
                    :disabled="actionOrderId === order.id"
                    @click="cancelOrder(order)"
                  >Cancel</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <AppPaginator
          :page="page"
          :last-page="lastPage"
          :per-page="perPage"
          :total="total"
          @prev="prevPage"
          @next="nextPage"
          @go-to="goToPage"
        />
      </template>
    </div>
  </div>

  <!-- Create Order Modal -->
  <AppModal v-model="showForm" title="New Order">
    <form id="order-form" class="space-y-4" @submit.prevent="handleSubmit">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Order Type <span class="text-red-500">*</span></label>
        <select v-model="form.order_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
          <option value="sale">Sale</option>
          <option value="purchase">Purchase</option>
          <option value="return">Return</option>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
        <textarea v-model="form.notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Optional notes" />
      </div>
      <p class="text-xs text-gray-400">Order lines can be added after creation via the API.</p>
      <div v-if="formError" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded px-3 py-2">{{ formError }}</div>
    </form>
    <template #footer>
      <button type="button" class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50" @click="showForm = false">Cancel</button>
      <button type="submit" form="order-form" :disabled="saving" class="flex items-center gap-2 px-4 py-2 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-60">
        <AppSpinner v-if="saving" size="sm" />
        {{ saving ? 'Creating…' : 'Create' }}
      </button>
    </template>
  </AppModal>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useListPage } from '@/composables/useListPage';
import { orderService } from '@/services/orders';
import { useAuthStore } from '@/stores/auth';
import { useNotificationStore } from '@/stores/notifications';
import type { Order } from '@/types/index';
import PageHeader from '@/components/PageHeader.vue';
import AppSpinner from '@/components/AppSpinner.vue';
import AppEmptyState from '@/components/AppEmptyState.vue';
import AppModal from '@/components/AppModal.vue';
import AppPaginator from '@/components/AppPaginator.vue';
import StatusBadge from '@/components/StatusBadge.vue';

const auth = useAuthStore();
const notify = useNotificationStore();

const search = ref('');
let searchDebounce: ReturnType<typeof setTimeout> | null = null;

const { items, loading, error, page, perPage, total, lastPage, load, nextPage, prevPage, goToPage } =
  useListPage<Order>({
    endpoint: '/orders',
    params: () => (search.value ? { search: search.value } : {}),
  });

void load();

function onSearchInput(): void {
  if (searchDebounce) clearTimeout(searchDebounce);
  searchDebounce = setTimeout(() => { page.value = 1; void load(); }, 300);
}

const actionOrderId = ref<number | null>(null);

async function confirmOrder(order: Order): Promise<void> {
  actionOrderId.value = order.id;
  try {
    await orderService.confirm(order.id);
    notify.success('Order confirmed.');
    void load();
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } };
    notify.error(err.response?.data?.message ?? 'Failed to confirm order.');
  } finally {
    actionOrderId.value = null;
  }
}

async function cancelOrder(order: Order): Promise<void> {
  if (!confirm('Cancel this order?')) return;
  actionOrderId.value = order.id;
  try {
    await orderService.cancel(order.id);
    notify.success('Order cancelled.');
    void load();
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } };
    notify.error(err.response?.data?.message ?? 'Failed to cancel order.');
  } finally {
    actionOrderId.value = null;
  }
}

// Create form
const showForm = ref(false);
const saving = ref(false);
const formError = ref<string | null>(null);
const form = ref({ order_type: 'sale' as 'sale' | 'purchase' | 'return', notes: '' });

function openCreate(): void {
  form.value = { order_type: 'sale', notes: '' };
  formError.value = null;
  showForm.value = true;
}

async function handleSubmit(): Promise<void> {
  saving.value = true;
  formError.value = null;
  try {
    await orderService.create({ order_type: form.value.order_type, lines: [], notes: form.value.notes || null });
    notify.success('Order created.');
    showForm.value = false;
    void load();
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } };
    formError.value = err.response?.data?.message ?? 'Failed to create order.';
  } finally {
    saving.value = false;
  }
}
</script>
