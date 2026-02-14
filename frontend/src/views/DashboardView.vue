<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ $t('nav.dashboard') }}</h1>
    
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <UsersIcon class="h-6 w-6 text-gray-400" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Total Customers</dt>
                <dd class="text-lg font-medium text-gray-900">{{ stats.customers }}</dd>
              </dl>
            </div>
          </div>
        </div>
        <div class="bg-gray-50 px-5 py-3">
          <router-link to="/crm/customers" class="text-sm text-blue-600 hover:text-blue-800">
            View all
          </router-link>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <CubeIcon class="h-6 w-6 text-gray-400" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Total Products</dt>
                <dd class="text-lg font-medium text-gray-900">{{ stats.products }}</dd>
              </dl>
            </div>
          </div>
        </div>
        <div class="bg-gray-50 px-5 py-3">
          <router-link to="/inventory/products" class="text-sm text-blue-600 hover:text-blue-800">
            View all
          </router-link>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <DocumentTextIcon class="h-6 w-6 text-gray-400" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Total Invoices</dt>
                <dd class="text-lg font-medium text-gray-900">{{ stats.invoices }}</dd>
              </dl>
            </div>
          </div>
        </div>
        <div class="bg-gray-50 px-5 py-3">
          <router-link to="/billing/invoices" class="text-sm text-blue-600 hover:text-blue-800">
            View all
          </router-link>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <CalendarIcon class="h-6 w-6 text-gray-400" />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">Appointments</dt>
                <dd class="text-lg font-medium text-gray-900">{{ stats.appointments }}</dd>
              </dl>
            </div>
          </div>
        </div>
        <div class="bg-gray-50 px-5 py-3">
          <router-link to="/appointments" class="text-sm text-blue-600 hover:text-blue-800">
            View all
          </router-link>
        </div>
      </div>
    </div>

    <div class="mt-8">
      <BaseCard title="Recent Activity">
        <p class="text-gray-500">Recent activity will be displayed here.</p>
      </BaseCard>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { UsersIcon, CubeIcon, DocumentTextIcon, CalendarIcon } from '@heroicons/vue/24/outline'
import BaseCard from '@components/common/BaseCard.vue'

const stats = ref({
  customers: 0,
  products: 0,
  invoices: 0,
  appointments: 0
})
</script>
