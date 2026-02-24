<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Fleet Management</h1>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage your vehicle registry and maintenance records.</p>
        </div>
      </div>

      <div
        v-if="fleet.error"
        class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400"
        role="alert"
      >
        {{ fleet.error }}
      </div>

      <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800" aria-label="Vehicles table">
            <thead class="bg-gray-50 dark:bg-gray-800/50">
              <tr>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Plate Number</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Make / Model</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Year</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Fuel Type</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Driver</th>
                <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
              <tr v-if="fleet.loading">
                <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Loading…</td>
              </tr>
              <tr v-else-if="fleet.vehicles.length === 0">
                <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">No vehicles found.</td>
              </tr>
              <tr
                v-for="vehicle in fleet.vehicles"
                v-else
                :key="vehicle.id"
                class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors"
              >
                <td class="px-4 py-3 text-sm font-mono font-medium text-gray-900 dark:text-white">{{ vehicle.plate_number }}</td>
                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ vehicle.make }} {{ vehicle.model }}</td>
                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ vehicle.year ?? '—' }}</td>
                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 capitalize">{{ vehicle.fuel_type ?? '—' }}</td>
                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ vehicle.driver_name ?? vehicle.driver_id ?? '—' }}</td>
                <td class="px-4 py-3"><StatusBadge :status="vehicle.status" /></td>
              </tr>
            </tbody>
          </table>
        </div>
        <Pagination :meta="fleet.meta" @change="fleet.fetchVehicles" />
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import Pagination from '@/components/Pagination.vue';
import { useFleetStore } from '@/stores/fleet';

const fleet = useFleetStore();

onMounted(() => fleet.fetchVehicles());
</script>
