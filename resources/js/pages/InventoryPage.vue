<template>
  <div class="space-y-4">
    <PageHeader title="Inventory" subtitle="Stock levels, warehouses, and low-stock alerts">
      <template #actions>
        <div class="flex gap-2">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            @click="switchTab(tab.key)"
            :class="activeTab === tab.key
              ? 'bg-blue-600 text-white'
              : 'bg-white text-gray-700 hover:bg-gray-100'"
            class="px-4 py-1.5 rounded-lg text-sm font-medium border transition-colors"
          >
            {{ tab.label }}
          </button>
        </div>
        <button
          v-if="activeTab === 'warehouses' && auth.hasPermission('inventory.create')"
          class="flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-1.5 rounded-lg transition-colors"
          @click="openCreateWarehouse"
        >
          <span class="text-base leading-none">+</span> New Warehouse
        </button>
      </template>
    </PageHeader>

    <div v-if="loading" class="flex items-center justify-center py-16">
      <AppSpinner size="lg" />
    </div>
    <div v-else-if="error" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
      {{ error }}
    </div>

    <!-- Stock levels -->
    <div v-else-if="activeTab === 'stock'" class="bg-white shadow rounded-lg overflow-hidden">
      <AppEmptyState v-if="stockItems.length === 0" icon="🏭" title="No stock records found" />
      <table v-else class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Warehouse</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty on Hand</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Reorder Point</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="item in stockItems" :key="item.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm text-gray-900">{{ item.product?.name ?? item.product_id }}</td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ item.warehouse?.name ?? item.warehouse_id }}</td>
            <td class="px-4 py-3 text-sm text-gray-900 text-right font-medium">{{ item.quantity_on_hand }}</td>
            <td class="px-4 py-3 text-sm text-gray-500 text-right">{{ item.reorder_point ?? '—' }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Low-stock alerts -->
    <div v-else-if="activeTab === 'low-stock'" class="bg-white shadow rounded-lg overflow-hidden">
      <AppEmptyState
        v-if="stockItems.length === 0"
        icon="✅"
        title="All stock levels are healthy"
        message="No products are below their reorder point."
      />
      <table v-else class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Qty on Hand</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Reorder Point</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="item in stockItems" :key="item.id" class="hover:bg-red-50">
            <td class="px-4 py-3 text-sm text-gray-900">{{ item.product?.name ?? item.product_id }}</td>
            <td class="px-4 py-3 text-sm text-red-600 font-bold text-right">{{ item.quantity_on_hand }}</td>
            <td class="px-4 py-3 text-sm text-gray-500 text-right">{{ item.reorder_point }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Warehouses -->
    <div v-else-if="activeTab === 'warehouses'" class="bg-white shadow rounded-lg overflow-hidden">
      <AppEmptyState v-if="warehouses.length === 0" icon="🏪" title="No warehouses found" />
      <template v-else>
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
              <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="wh in warehouses" :key="wh.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-sm text-gray-900 font-medium">{{ wh.name }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ wh.location ?? '—' }}</td>
              <td class="px-4 py-3 text-center">
                <StatusBadge :status="wh.is_active ? 'active' : 'suspended'" />
              </td>
              <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button v-if="auth.hasPermission('inventory.update')" class="text-xs text-blue-600 hover:underline" @click="openEditWarehouse(wh)">Edit</button>
                  <button v-if="auth.hasPermission('inventory.delete')" class="text-xs text-red-500 hover:underline" @click="deleteWarehouse(wh)">Delete</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </template>
    </div>
  </div>

  <!-- Warehouse Form Modal -->
  <AppModal v-model="showWarehouseForm" :title="editWarehouseTarget ? 'Edit Warehouse' : 'New Warehouse'">
    <form id="warehouse-form" class="space-y-4" @submit.prevent="handleWarehouseSubmit">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
        <input v-model="warehouseForm.name" type="text" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
        <input v-model="warehouseForm.location" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Optional address" />
      </div>
      <div class="flex items-center gap-2">
        <input id="wh-active" v-model="warehouseForm.is_active" type="checkbox" class="rounded" />
        <label for="wh-active" class="text-sm text-gray-700">Active</label>
      </div>
      <div v-if="formError" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded px-3 py-2">{{ formError }}</div>
    </form>
    <template #footer>
      <button type="button" class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50" @click="showWarehouseForm = false">Cancel</button>
      <button type="submit" form="warehouse-form" :disabled="saving" class="flex items-center gap-2 px-4 py-2 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-60">
        <AppSpinner v-if="saving" size="sm" />
        {{ saving ? 'Saving…' : 'Save' }}
      </button>
    </template>
  </AppModal>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { inventoryService } from '@/services/inventory';
import type { Warehouse } from '@/services/inventory';
import { useAuthStore } from '@/stores/auth';
import { useNotificationStore } from '@/stores/notifications';
import type { StockItem } from '@/types/index';
import PageHeader from '@/components/PageHeader.vue';
import AppSpinner from '@/components/AppSpinner.vue';
import AppEmptyState from '@/components/AppEmptyState.vue';
import AppModal from '@/components/AppModal.vue';
import StatusBadge from '@/components/StatusBadge.vue';

const auth = useAuthStore();
const notify = useNotificationStore();

const tabs = [
  { key: 'stock', label: 'Stock Levels' },
  { key: 'low-stock', label: 'Low Stock Alerts' },
  { key: 'warehouses', label: 'Warehouses' },
];

const activeTab = ref<string>('stock');
const stockItems = ref<StockItem[]>([]);
const warehouses = ref<Warehouse[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);

async function loadData(): Promise<void> {
  loading.value = true;
  error.value = null;
  try {
    if (activeTab.value === 'stock') {
      const { data } = await inventoryService.listStock();
      stockItems.value = Array.isArray(data) ? data : (data as { data: StockItem[] }).data;
    } else if (activeTab.value === 'low-stock') {
      const { data } = await inventoryService.listLowStock();
      stockItems.value = Array.isArray(data) ? data : (data as { data: StockItem[] }).data;
    } else {
      const { data } = await inventoryService.listWarehouses();
      warehouses.value = Array.isArray(data) ? data : (data as { data: Warehouse[] }).data;
    }
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } };
    error.value = err.response?.data?.message ?? 'Failed to load inventory data.';
  } finally {
    loading.value = false;
  }
}

function switchTab(key: string): void {
  activeTab.value = key;
  void loadData();
}

onMounted(() => void loadData());

// ─── Warehouse Form ───────────────────────────────────────────────────────────

const showWarehouseForm = ref(false);
const editWarehouseTarget = ref<Warehouse | null>(null);
const saving = ref(false);
const formError = ref<string | null>(null);
const warehouseForm = ref({ name: '', location: '', is_active: true });

function openCreateWarehouse(): void {
  editWarehouseTarget.value = null;
  warehouseForm.value = { name: '', location: '', is_active: true };
  formError.value = null;
  showWarehouseForm.value = true;
}

function openEditWarehouse(wh: Warehouse): void {
  editWarehouseTarget.value = wh;
  warehouseForm.value = { name: wh.name, location: wh.location ?? '', is_active: wh.is_active };
  formError.value = null;
  showWarehouseForm.value = true;
}

async function handleWarehouseSubmit(): Promise<void> {
  saving.value = true;
  formError.value = null;
  try {
    const payload = {
      name: warehouseForm.value.name,
      location: warehouseForm.value.location || null,
      is_active: warehouseForm.value.is_active,
    };
    if (editWarehouseTarget.value) {
      await inventoryService.updateWarehouse(editWarehouseTarget.value.id, payload);
      notify.success('Warehouse updated.');
    } else {
      await inventoryService.createWarehouse(payload);
      notify.success('Warehouse created.');
    }
    showWarehouseForm.value = false;
    void loadData();
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } };
    formError.value = err.response?.data?.message ?? 'Failed to save warehouse.';
  } finally {
    saving.value = false;
  }
}

async function deleteWarehouse(wh: Warehouse): Promise<void> {
  if (!confirm(`Delete warehouse "${wh.name}"?`)) return;
  try {
    await inventoryService.deleteWarehouse(wh.id);
    notify.success('Warehouse deleted.');
    void loadData();
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } };
    notify.error(err.response?.data?.message ?? 'Failed to delete warehouse.');
  }
}
</script>
