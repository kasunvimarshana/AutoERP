<template>
  <div>
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">
        {{ isEdit ? 'Edit Product' : 'Add Product' }}
      </h1>
      <p class="mt-1 text-sm text-gray-600">
        {{ isEdit ? 'Update product information' : 'Create a new product' }}
      </p>
    </div>

    <div class="bg-white shadow rounded-lg">
      <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
          <div>
            <label for="name" class="block text-sm font-medium text-gray-700">
              Product Name *
            </label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
              :class="{ 'border-red-300': errors.name }"
            />
            <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
          </div>

          <div>
            <label for="sku" class="block text-sm font-medium text-gray-700">
              SKU *
            </label>
            <input
              id="sku"
              v-model="form.sku"
              type="text"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm font-mono"
              :class="{ 'border-red-300': errors.sku }"
            />
            <p v-if="errors.sku" class="mt-1 text-sm text-red-600">{{ errors.sku }}</p>
          </div>

          <div class="sm:col-span-2">
            <label for="description" class="block text-sm font-medium text-gray-700">
              Description
            </label>
            <textarea
              id="description"
              v-model="form.description"
              rows="3"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
            ></textarea>
          </div>

          <div>
            <label for="category" class="block text-sm font-medium text-gray-700">
              Category
            </label>
            <input
              id="category"
              v-model="form.category"
              type="text"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
            />
          </div>

          <div>
            <label for="brand" class="block text-sm font-medium text-gray-700">
              Brand
            </label>
            <input
              id="brand"
              v-model="form.brand"
              type="text"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
            />
          </div>

          <div>
            <label for="price" class="block text-sm font-medium text-gray-700">
              Price *
            </label>
            <div class="mt-1 relative rounded-md shadow-sm">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <span class="text-gray-500 sm:text-sm">$</span>
              </div>
              <input
                id="price"
                v-model="form.price"
                type="number"
                step="0.01"
                required
                class="block w-full rounded-md border-gray-300 pl-7 focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                :class="{ 'border-red-300': errors.price }"
              />
            </div>
            <p v-if="errors.price" class="mt-1 text-sm text-red-600">{{ errors.price }}</p>
          </div>

          <div>
            <label for="cost" class="block text-sm font-medium text-gray-700">
              Cost
            </label>
            <div class="mt-1 relative rounded-md shadow-sm">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <span class="text-gray-500 sm:text-sm">$</span>
              </div>
              <input
                id="cost"
                v-model="form.cost"
                type="number"
                step="0.01"
                class="block w-full rounded-md border-gray-300 pl-7 focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
              />
            </div>
          </div>

          <div>
            <label for="stock" class="block text-sm font-medium text-gray-700">
              Stock Quantity *
            </label>
            <input
              id="stock"
              v-model="form.stock"
              type="number"
              required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
              :class="{ 'border-red-300': errors.stock }"
            />
            <p v-if="errors.stock" class="mt-1 text-sm text-red-600">{{ errors.stock }}</p>
          </div>

          <div>
            <label for="min_stock" class="block text-sm font-medium text-gray-700">
              Minimum Stock Level
            </label>
            <input
              id="min_stock"
              v-model="form.min_stock"
              type="number"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
            />
          </div>

          <div>
            <label for="unit" class="block text-sm font-medium text-gray-700">
              Unit
            </label>
            <input
              id="unit"
              v-model="form.unit"
              type="text"
              placeholder="e.g., pcs, kg, liters"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
            />
          </div>

          <div>
            <label for="status" class="block text-sm font-medium text-gray-700">
              Status
            </label>
            <select
              id="status"
              v-model="form.status"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
            >
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>

        <div v-if="error" class="rounded-md bg-red-50 p-4">
          <p class="text-sm text-red-800">{{ error }}</p>
        </div>

        <div class="flex justify-end space-x-3">
          <Button variant="outline" type="button" @click="$router.back()">
            Cancel
          </Button>
          <Button type="submit" :loading="loading">
            {{ isEdit ? 'Update' : 'Create' }}
          </Button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useProductStore } from '../../../stores/product';
import Button from '../../../components/Button.vue';

const router = useRouter();
const route = useRoute();
const productStore = useProductStore();

const form = ref({
  name: '',
  sku: '',
  description: '',
  category: '',
  brand: '',
  price: '',
  cost: '',
  stock: 0,
  min_stock: 0,
  unit: 'pcs',
  status: 'active',
});

const loading = ref(false);
const error = ref('');
const errors = ref({});

const isEdit = computed(() => !!route.params.id);

onMounted(async () => {
  if (isEdit.value) {
    try {
      const response = await productStore.fetchProduct(route.params.id);
      Object.assign(form.value, response.data);
    } catch (err) {
      error.value = 'Failed to load product';
    }
  }
});

const handleSubmit = async () => {
  loading.value = true;
  error.value = '';
  errors.value = {};

  try {
    if (isEdit.value) {
      await productStore.updateProduct(route.params.id, form.value);
    } else {
      await productStore.createProduct(form.value);
    }
    router.push({ name: 'products' });
  } catch (err) {
    if (err.response?.data?.errors) {
      errors.value = err.response.data.errors;
    } else {
      error.value = err.response?.data?.message || 'Failed to save product';
    }
  } finally {
    loading.value = false;
  }
};
</script>
