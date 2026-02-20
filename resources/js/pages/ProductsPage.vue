<template>
  <div class="space-y-4">
    <PageHeader title="Products" subtitle="Manage your product catalog">
      <template #actions>
        <input
          v-model="search"
          type="search"
          placeholder="Search products…"
          class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-48"
          @input="onSearchInput"
        />
        <button
          v-if="auth.hasPermission('product.create')"
          class="flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-1.5 rounded-lg transition-colors"
          @click="openCreate"
        >
          <span class="text-base leading-none">+</span> New Product
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
        icon="📦"
        title="No products found"
        :message="search ? 'Try a different search term.' : 'No products have been added yet.'"
      />
      <template v-else>
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Base Price</th>
              <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="product in items" :key="product.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-sm text-gray-900 font-medium">{{ product.name }}</td>
              <td class="px-4 py-3 text-sm text-gray-500 capitalize">{{ product.type }}</td>
              <td class="px-4 py-3 text-sm font-mono text-gray-500">{{ product.sku ?? '—' }}</td>
              <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ product.base_price }}</td>
              <td class="px-4 py-3 text-center">
                <StatusBadge :status="product.is_active ? 'active' : 'suspended'" />
              </td>
              <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button
                    v-if="auth.hasPermission('product.update')"
                    class="text-xs text-blue-600 hover:underline"
                    @click="openEdit(product)"
                  >Edit</button>
                  <button
                    v-if="auth.hasPermission('product.delete')"
                    class="text-xs text-red-500 hover:underline"
                    @click="confirmDelete(product)"
                  >Delete</button>
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

  <!-- Create / Edit Modal -->
  <AppModal v-model="showForm" :title="editTarget ? 'Edit Product' : 'New Product'">
    <form id="product-form" class="space-y-4" @submit.prevent="handleSubmit">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Name <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.name"
          type="text"
          required
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="Product name"
        />
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Type <span class="text-red-500">*</span>
          </label>
          <select
            v-model="form.type"
            required
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"
          >
            <option value="goods">Goods</option>
            <option value="service">Service</option>
            <option value="digital">Digital</option>
            <option value="bundle">Bundle</option>
            <option value="composite">Composite</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
          <input
            v-model="form.sku"
            type="text"
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Optional"
          />
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Base Price <span class="text-red-500">*</span>
        </label>
        <input
          v-model="form.base_price"
          type="text"
          required
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="0.00"
        />
      </div>

      <div class="flex items-center gap-2">
        <input id="is-active" v-model="form.is_active" type="checkbox" class="rounded" />
        <label for="is-active" class="text-sm text-gray-700">Active</label>
      </div>

      <div
        v-if="formError"
        class="bg-red-50 border border-red-200 text-red-700 text-sm rounded px-3 py-2"
      >{{ formError }}</div>
    </form>

    <template #footer>
      <button
        type="button"
        class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
        @click="showForm = false"
      >Cancel</button>
      <button
        type="submit"
        form="product-form"
        :disabled="saving"
        class="flex items-center gap-2 px-4 py-2 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-60"
      >
        <AppSpinner v-if="saving" size="sm" />
        {{ saving ? 'Saving…' : 'Save' }}
      </button>
    </template>
  </AppModal>

  <!-- Delete Confirmation -->
  <AppModal v-model="showDelete" title="Delete Product" size="sm">
    <p class="text-sm text-gray-700">
      Are you sure you want to delete
      <span class="font-semibold">{{ deleteTarget?.name }}</span>?
      This action cannot be undone.
    </p>
    <template #footer>
      <button
        type="button"
        class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
        @click="showDelete = false"
      >Cancel</button>
      <button
        type="button"
        :disabled="saving"
        class="flex items-center gap-2 px-4 py-2 text-sm text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-60"
        @click="handleDelete"
      >
        <AppSpinner v-if="saving" size="sm" />
        {{ saving ? 'Deleting…' : 'Delete' }}
      </button>
    </template>
  </AppModal>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useListPage } from '@/composables/useListPage';
import { productService } from '@/services/products';
import { useAuthStore } from '@/stores/auth';
import { useNotificationStore } from '@/stores/notifications';
import type { Product } from '@/types/index';
import PageHeader from '@/components/PageHeader.vue';
import AppSpinner from '@/components/AppSpinner.vue';
import AppEmptyState from '@/components/AppEmptyState.vue';
import AppModal from '@/components/AppModal.vue';
import AppPaginator from '@/components/AppPaginator.vue';
import StatusBadge from '@/components/StatusBadge.vue';

const auth = useAuthStore();
const notify = useNotificationStore();

// ─── List ────────────────────────────────────────────────────────────────────

const search = ref('');
let searchDebounce: ReturnType<typeof setTimeout> | null = null;

const { items, loading, error, page, perPage, total, lastPage, load, nextPage, prevPage, goToPage } =
  useListPage<Product>({
    endpoint: '/products',
    params: () => (search.value ? { search: search.value } : {}),
  });

void load();

function onSearchInput(): void {
  if (searchDebounce) clearTimeout(searchDebounce);
  searchDebounce = setTimeout(() => {
    page.value = 1;
    void load();
  }, 300);
}

// ─── Form ────────────────────────────────────────────────────────────────────

const showForm = ref(false);
const editTarget = ref<Product | null>(null);
const saving = ref(false);
const formError = ref<string | null>(null);

const defaultForm = () => ({
  name: '',
  type: 'goods' as Product['type'],
  sku: '',
  base_price: '',
  is_active: true,
});

const form = ref(defaultForm());

function openCreate(): void {
  editTarget.value = null;
  form.value = defaultForm();
  formError.value = null;
  showForm.value = true;
}

function openEdit(product: Product): void {
  editTarget.value = product;
  form.value = {
    name: product.name,
    type: product.type,
    sku: product.sku ?? '',
    base_price: product.base_price,
    is_active: product.is_active,
  };
  formError.value = null;
  showForm.value = true;
}

async function handleSubmit(): Promise<void> {
  saving.value = true;
  formError.value = null;
  try {
    const payload = {
      name: form.value.name,
      type: form.value.type,
      sku: form.value.sku || null,
      base_price: form.value.base_price,
      is_active: form.value.is_active,
    };
    if (editTarget.value) {
      await productService.update(editTarget.value.id, payload);
      notify.success('Product updated successfully.');
    } else {
      await productService.create(payload);
      notify.success('Product created successfully.');
    }
    showForm.value = false;
    void load();
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } };
    formError.value = err.response?.data?.message ?? 'Failed to save product.';
  } finally {
    saving.value = false;
  }
}

// ─── Delete ──────────────────────────────────────────────────────────────────

const showDelete = ref(false);
const deleteTarget = ref<Product | null>(null);

function confirmDelete(product: Product): void {
  deleteTarget.value = product;
  showDelete.value = true;
}

async function handleDelete(): Promise<void> {
  if (!deleteTarget.value) return;
  saving.value = true;
  try {
    await productService.remove(deleteTarget.value.id);
    notify.success('Product deleted.');
    showDelete.value = false;
    void load();
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } };
    notify.error(err.response?.data?.message ?? 'Failed to delete product.');
  } finally {
    saving.value = false;
  }
}
</script>
