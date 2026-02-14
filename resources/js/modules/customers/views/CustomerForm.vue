<template>
  <div>
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">
        {{ isEdit ? 'Edit Customer' : 'Add Customer' }}
      </h1>
      <p class="mt-1 text-sm text-gray-600">
        {{ isEdit ? 'Update customer information' : 'Create a new customer' }}
      </p>
    </div>

    <div class="bg-white shadow rounded-lg">
      <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
          <div>
            <label for="name" class="block text-sm font-medium text-gray-700">
              Name *
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
            <label for="email" class="block text-sm font-medium text-gray-700">
              Email
            </label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
              :class="{ 'border-red-300': errors.email }"
            />
            <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email }}</p>
          </div>

          <div>
            <label for="phone" class="block text-sm font-medium text-gray-700">
              Phone
            </label>
            <input
              id="phone"
              v-model="form.phone"
              type="tel"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
              :class="{ 'border-red-300': errors.phone }"
            />
            <p v-if="errors.phone" class="mt-1 text-sm text-red-600">{{ errors.phone }}</p>
          </div>

          <div>
            <label for="company" class="block text-sm font-medium text-gray-700">
              Company
            </label>
            <input
              id="company"
              v-model="form.company"
              type="text"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
            />
          </div>

          <div class="sm:col-span-2">
            <label for="address" class="block text-sm font-medium text-gray-700">
              Address
            </label>
            <textarea
              id="address"
              v-model="form.address"
              rows="3"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
            ></textarea>
          </div>

          <div>
            <label for="city" class="block text-sm font-medium text-gray-700">
              City
            </label>
            <input
              id="city"
              v-model="form.city"
              type="text"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
            />
          </div>

          <div>
            <label for="country" class="block text-sm font-medium text-gray-700">
              Country
            </label>
            <input
              id="country"
              v-model="form.country"
              type="text"
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
import { useCustomerStore } from '../../../stores/customer';
import Button from '../../../components/Button.vue';

const router = useRouter();
const route = useRoute();
const customerStore = useCustomerStore();

const form = ref({
  name: '',
  email: '',
  phone: '',
  company: '',
  address: '',
  city: '',
  country: '',
  status: 'active',
});

const loading = ref(false);
const error = ref('');
const errors = ref({});

const isEdit = computed(() => !!route.params.id);

onMounted(async () => {
  if (isEdit.value) {
    try {
      const response = await customerStore.fetchCustomer(route.params.id);
      Object.assign(form.value, response.data);
    } catch (err) {
      error.value = 'Failed to load customer';
    }
  }
});

const handleSubmit = async () => {
  loading.value = true;
  error.value = '';
  errors.value = {};

  try {
    if (isEdit.value) {
      await customerStore.updateCustomer(route.params.id, form.value);
    } else {
      await customerStore.createCustomer(form.value);
    }
    router.push({ name: 'customers' });
  } catch (err) {
    if (err.response?.data?.errors) {
      errors.value = err.response.data.errors;
    } else {
      error.value = err.response?.data?.message || 'Failed to save customer';
    }
  } finally {
    loading.value = false;
  }
};
</script>
