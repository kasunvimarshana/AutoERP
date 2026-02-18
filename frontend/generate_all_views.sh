#!/bin/bash

# Function to create a generic list view
create_list_view() {
  local module=$1
  local entity=$2
  local entity_plural=$3
  local store=$4
  local path=$5
  
  cat > "src/modules/${module}/views/${entity}List.vue" << EOF
<template>
  <div class="page-container">
    <div class="page-header">
      <h1 class="page-title">${entity_plural}</h1>
      <button @click="showCreateModal = true" class="btn btn-primary">
        <PlusIcon class="icon" />
        Add ${entity}
      </button>
    </div>
    
    <DataTable
      :columns="columns"
      :data="items"
      :loading="loading"
      :current-page="currentPage"
      :total-pages="totalPages"
      @page-change="handlePageChange"
    >
      <template #actions="{ row }">
        <div class="action-buttons">
          <button @click="viewItem(row)" class="btn-icon" title="View">
            <EyeIcon class="icon" />
          </button>
          <button @click="editItem(row)" class="btn-icon" title="Edit">
            <PencilIcon class="icon" />
          </button>
          <button @click="deleteItemConfirm(row)" class="btn-icon" title="Delete">
            <TrashIcon class="icon" />
          </button>
        </div>
      </template>
    </DataTable>
    
    <Modal
      :show="showCreateModal"
      title="Add ${entity}"
      @close="showCreateModal = false"
      @confirm="handleCreate"
    >
      <p class="text-muted">Form fields for ${entity} will appear here</p>
    </Modal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { PlusIcon, PencilIcon, TrashIcon, EyeIcon } from '@heroicons/vue/24/outline'
import DataTable from '../../../components/common/DataTable.vue'
import Modal from '../../../components/common/Modal.vue'
import { ${store} } from '../../../stores/${path}Store'

const store = ${store}()
const showCreateModal = ref(false)
const currentPage = ref(1)
const totalPages = ref(1)

const columns = [
  { key: 'id', label: 'ID', width: '80px' },
  { key: 'name', label: 'Name', sortable: true },
  { key: 'created_at', label: 'Created', sortable: true }
]

const items = computed(() => store.${entity_plural.toLowerCase()})
const loading = computed(() => store.loading)

onMounted(() => {
  fetchItems()
})

const fetchItems = async () => {
  await store.fetch${entity_plural}({ page: currentPage.value })
}

const handlePageChange = (page) => {
  currentPage.value = page
  fetchItems()
}

const handleCreate = async () => {
  showCreateModal.value = false
}

const viewItem = (item) => {
  console.log('View:', item)
}

const editItem = (item) => {
  console.log('Edit:', item)
}

const deleteItemConfirm = async (item) => {
  if (confirm('Are you sure you want to delete this item?')) {
    await store.delete${entity}(item.id)
  }
}
</script>

<style scoped>
.page-container {
  max-width: 1400px;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: #1f2937;
}

.btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover {
  background: #2563eb;
}

.icon {
  width: 20px;
  height: 20px;
}

.action-buttons {
  display: flex;
  gap: 8px;
}

.btn-icon {
  background: none;
  border: none;
  cursor: pointer;
  padding: 6px;
  color: #6b7280;
  transition: color 0.2s;
  border-radius: 4px;
}

.btn-icon:hover {
  color: #3b82f6;
  background: #eff6ff;
}

.text-muted {
  color: #9ca3af;
  font-size: 14px;
}
</style>
EOF
}

# IAM Module
create_list_view "iam" "Role" "Roles" "useIAMStore" "iam"
create_list_view "iam" "Permission" "Permissions" "useIAMStore" "iam"

# Inventory Module
create_list_view "inventory" "StockLedger" "StockLedgers" "useInventoryStore" "inventory"
create_list_view "inventory" "StockMovement" "StockMovements" "useInventoryStore" "inventory"
create_list_view "inventory" "Warehouse" "Warehouses" "useInventoryStore" "inventory"

# CRM Module
create_list_view "crm" "Customer" "Customers" "useCRMStore" "crm"
create_list_view "crm" "Contact" "Contacts" "useCRMStore" "crm"

# POS Module - Create simple views
for entity in "Quotation" "SalesOrder" "Invoice" "Payment"; do
  entity_lower=$(echo "$entity" | sed 's/\([A-Z]\)/-\1/g' | sed 's/^-//' | tr '[:upper:]' '[:lower:]')
  entity_plural="${entity}s"
  
  cat > "src/modules/pos/views/${entity}List.vue" << EOF
<template>
  <div class="page-container">
    <div class="page-header">
      <h1 class="page-title">${entity_plural}</h1>
      <button @click="createNew" class="btn btn-primary">
        <PlusIcon class="icon" />
        Add ${entity}
      </button>
    </div>
    
    <div class="content-card">
      <p class="text-muted">${entity_plural} management interface</p>
    </div>
  </div>
</template>

<script setup>
import { PlusIcon } from '@heroicons/vue/24/outline'

const createNew = () => {
  console.log('Create ${entity}')
}
</script>

<style scoped>
.page-container {
  max-width: 1400px;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: #1f2937;
}

.btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  border: none;
  background: #3b82f6;
  color: white;
}

.btn:hover {
  background: #2563eb;
}

.icon {
  width: 20px;
  height: 20px;
}

.content-card {
  background: white;
  padding: 40px;
  border-radius: 12px;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  text-align: center;
}

.text-muted {
  color: #9ca3af;
  font-size: 14px;
}
</style>
EOF
done

# Procurement Module
for entity in "Supplier" "PurchaseOrder" "GRN"; do
  entity_plural="${entity}s"
  
  cat > "src/modules/procurement/views/${entity}List.vue" << EOF
<template>
  <div class="page-container">
    <div class="page-header">
      <h1 class="page-title">${entity_plural}</h1>
      <button @click="createNew" class="btn btn-primary">
        <PlusIcon class="icon" />
        Add ${entity}
      </button>
    </div>
    
    <div class="content-card">
      <p class="text-muted">${entity_plural} management interface</p>
    </div>
  </div>
</template>

<script setup>
import { PlusIcon } from '@heroicons/vue/24/outline'

const createNew = () => {
  console.log('Create ${entity}')
}
</script>

<style scoped>
.page-container {
  max-width: 1400px;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: #1f2937;
}

.btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  border: none;
  background: #3b82f6;
  color: white;
}

.btn:hover {
  background: #2563eb;
}

.icon {
  width: 20px;
  height: 20px;
}

.content-card {
  background: white;
  padding: 40px;
  border-radius: 12px;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  text-align: center;
}

.text-muted {
  color: #9ca3af;
  font-size: 14px;
}
</style>
EOF
done

# Manufacturing Module
for entity in "BOM" "ProductionOrder" "WorkOrder"; do
  entity_plural="${entity}s"
  
  cat > "src/modules/manufacturing/views/${entity}List.vue" << EOF
<template>
  <div class="page-container">
    <div class="page-header">
      <h1 class="page-title">${entity_plural}</h1>
      <button @click="createNew" class="btn btn-primary">
        <PlusIcon class="icon" />
        Add ${entity}
      </button>
    </div>
    
    <div class="content-card">
      <p class="text-muted">${entity_plural} management interface</p>
    </div>
  </div>
</template>

<script setup>
import { PlusIcon } from '@heroicons/vue/24/outline'

const createNew = () => {
  console.log('Create ${entity}')
}
</script>

<style scoped>
.page-container {
  max-width: 1400px;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: #1f2937;
}

.btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  border: none;
  background: #3b82f6;
  color: white;
}

.btn:hover {
  background: #2563eb;
}

.icon {
  width: 20px;
  height: 20px;
}

.content-card {
  background: white;
  padding: 40px;
  border-radius: 12px;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  text-align: center;
}

.text-muted {
  color: #9ca3af;
  font-size: 14px;
}
</style>
EOF
done

# Finance Module
for entity in "Account" "JournalEntry" "Report"; do
  entity_plural="${entity}s"
  if [ "$entity" = "JournalEntry" ]; then
    entity_plural="JournalEntries"
  fi
  
  cat > "src/modules/finance/views/${entity}List.vue" << EOF
<template>
  <div class="page-container">
    <div class="page-header">
      <h1 class="page-title">${entity_plural}</h1>
      <button @click="createNew" class="btn btn-primary">
        <PlusIcon class="icon" />
        Add ${entity}
      </button>
    </div>
    
    <div class="content-card">
      <p class="text-muted">${entity_plural} management interface</p>
    </div>
  </div>
</template>

<script setup>
import { PlusIcon } from '@heroicons/vue/24/outline'

const createNew = () => {
  console.log('Create ${entity}')
}
</script>

<style scoped>
.page-container {
  max-width: 1400px;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: #1f2937;
}

.btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  border: none;
  background: #3b82f6;
  color: white;
}

.btn:hover {
  background: #2563eb;
}

.icon {
  width: 20px;
  height: 20px;
}

.content-card {
  background: white;
  padding: 40px;
  border-radius: 12px;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  text-align: center;
}

.text-muted {
  color: #9ca3af;
  font-size: 14px;
}
</style>
EOF
done

# Reporting Module
for entity in "Dashboard" "Analytics"; do
  entity_plural="${entity}List"
  if [ "$entity" = "Analytics" ]; then
    entity_plural="AnalyticsList"
  else
    entity_plural="DashboardList"
  fi
  
  cat > "src/modules/reporting/views/${entity_plural}.vue" << EOF
<template>
  <div class="page-container">
    <div class="page-header">
      <h1 class="page-title">${entity}</h1>
      <button @click="refresh" class="btn btn-primary">
        <ArrowPathIcon class="icon" />
        Refresh
      </button>
    </div>
    
    <div class="content-card">
      <p class="text-muted">${entity} interface - charts and visualizations will appear here</p>
    </div>
  </div>
</template>

<script setup>
import { ArrowPathIcon } from '@heroicons/vue/24/outline'

const refresh = () => {
  console.log('Refresh ${entity}')
}
</script>

<style scoped>
.page-container {
  max-width: 1400px;
}

.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: #1f2937;
}

.btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  border: none;
  background: #3b82f6;
  color: white;
}

.btn:hover {
  background: #2563eb;
}

.icon {
  width: 20px;
  height: 20px;
}

.content-card {
  background: white;
  padding: 40px;
  border-radius: 12px;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  text-align: center;
}

.text-muted {
  color: #9ca3af;
  font-size: 14px;
}
</style>
EOF
done

echo "All module views created successfully!"
