<template>
  <div>
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Customers</h1>
        <p class="mt-1 text-sm text-gray-600">
          Manage your customer database
        </p>
      </div>
      <div class="mt-4 sm:mt-0">
        <Button @click="$router.push({ name: 'customers.create' })">
          Add Customer
        </Button>
      </div>
    </div>

    <div class="mb-4">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Search customers..."
        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
        @input="handleSearch"
      />
    </div>

    <DataTable
      :columns="columns"
      :data="customerStore.customers"
      :loading="customerStore.loading"
      :pagination="customerStore.pagination"
      @page-change="handlePageChange"
      empty-text="No customers found"
    >
      <template #cell-name="{ row }">
        <div class="font-medium text-gray-900">{{ row.name }}</div>
      </template>

      <template #cell-email="{ row }">
        <div class="text-gray-600">{{ row.email || '-' }}</div>
      </template>

      <template #cell-phone="{ row }">
        <div class="text-gray-600">{{ row.phone || '-' }}</div>
      </template>

      <template #cell-status="{ row }">
        <span
          class="inline-flex rounded-full px-2 text-xs font-semibold leading-5"
          :class="row.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
        >
          {{ row.status || 'active' }}
        </span>
      </template>

      <template #actions="{ row }">
        <div class="flex space-x-2">
          <button
            @click="handleEdit(row.id)"
            class="text-blue-600 hover:text-blue-900"
          >
            Edit
          </button>
          <button
            @click="handleDelete(row.id)"
            class="text-red-600 hover:text-red-900"
          >
            Delete
          </button>
        </div>
      </template>
    </DataTable>

    <Modal v-model="showDeleteModal" title="Confirm Delete" size="sm">
      <p class="text-sm text-gray-500">
        Are you sure you want to delete this customer? This action cannot be undone.
      </p>
      <template #footer>
        <Button variant="outline" @click="showDeleteModal = false">
          Cancel
        </Button>
        <Button variant="danger" @click="confirmDelete" :loading="deleting">
          Delete
        </Button>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useCustomerStore } from '../../../stores/customer';
import DataTable from '../../../components/DataTable.vue';
import Button from '../../../components/Button.vue';
import Modal from '../../../components/Modal.vue';

const router = useRouter();
const customerStore = useCustomerStore();

const searchQuery = ref('');
const showDeleteModal = ref(false);
const customerToDelete = ref(null);
const deleting = ref(false);

const columns = [
  { key: 'name', label: 'Name' },
  { key: 'email', label: 'Email' },
  { key: 'phone', label: 'Phone' },
  { key: 'status', label: 'Status' },
];

onMounted(() => {
  loadCustomers();
});

const loadCustomers = async (page = 1) => {
  try {
    await customerStore.fetchCustomers({ page });
  } catch (error) {
    console.error('Failed to load customers:', error);
  }
};

const handlePageChange = (page) => {
  loadCustomers(page);
};

const handleSearch = () => {
  if (searchQuery.value.length > 2) {
    customerStore.searchCustomers(searchQuery.value);
  } else if (searchQuery.value.length === 0) {
    loadCustomers();
  }
};

const handleEdit = (id) => {
  router.push({ name: 'customers.edit', params: { id } });
};

const handleDelete = (id) => {
  customerToDelete.value = id;
  showDeleteModal.value = true;
};

const confirmDelete = async () => {
  deleting.value = true;
  try {
    await customerStore.deleteCustomer(customerToDelete.value);
    showDeleteModal.value = false;
    customerToDelete.value = null;
  } catch (error) {
    console.error('Failed to delete customer:', error);
  } finally {
    deleting.value = false;
  }
};
</script>
