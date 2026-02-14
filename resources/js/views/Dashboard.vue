<template>
  <div>
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Dashboard</h1>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-1">
              <p class="text-sm font-medium text-gray-500">Total Revenue</p>
              <p class="mt-1 text-3xl font-semibold text-gray-900">$0</p>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-1">
              <p class="text-sm font-medium text-gray-500">Total Customers</p>
              <p class="mt-1 text-3xl font-semibold text-gray-900">0</p>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-1">
              <p class="text-sm font-medium text-gray-500">Total Products</p>
              <p class="mt-1 text-3xl font-semibold text-gray-900">0</p>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-1">
              <p class="text-sm font-medium text-gray-500">Total Invoices</p>
              <p class="mt-1 text-3xl font-semibold text-gray-900">0</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="bg-white shadow rounded-lg p-6">
      <h2 class="text-xl font-semibold text-gray-900 mb-4">Welcome to AutoERP</h2>
      <p class="text-gray-600">
        This is your enterprise-grade ERP dashboard. Navigate using the menu above to manage products, inventory, customers, and more.
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../services/api'

const stats = ref({
  totalRevenue: 0,
  totalCustomers: 0,
  totalProducts: 0,
  totalInvoices: 0
})

onMounted(async () => {
  try {
    const response = await api.get('/api/v1/analytics/dashboard')
    if (response.data.data) {
      stats.value = response.data.data
    }
  } catch (error) {
    console.error('Failed to fetch dashboard stats:', error)
  }
})
</script>
