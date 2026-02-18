<script setup>
import { ref } from 'vue'

const title = ref('autoerp')
const subtitle = ref('Enterprise-grade SaaS ERP/CRM with Native Laravel & Vue')
</script>

<template>
  <div class="min-h-screen flex items-center justify-center">
    <div class="card max-w-2xl">
      <h1 class="text-4xl font-bold text-primary-600 mb-4">
        {{ title }}
      </h1>
      <p class="text-xl text-gray-600 mb-8">
        {{ subtitle }}
      </p>
      <div class="space-y-4">
        <div class="p-4 bg-blue-50 rounded-md">
          <h3 class="font-semibold text-blue-900 mb-2">✅ Native Laravel Features</h3>
          <ul class="list-disc list-inside text-blue-800 space-y-1">
            <li>Multi-tenant (Global Scopes)</li>
            <li>Multi-language (JSON Columns)</li>
            <li>RBAC (Gates & Policies)</li>
            <li>Activity Logging (Eloquent Events)</li>
          </ul>
        </div>
        <div class="p-4 bg-green-50 rounded-md">
          <h3 class="font-semibold text-green-900 mb-2">✅ Native Vue 3 Features</h3>
          <ul class="list-disc list-inside text-green-800 space-y-1">
            <li>Composition API</li>
            <li>Custom Components</li>
            <li>Provide/Inject</li>
            <li>Teleport & Suspense</li>
          </ul>
        </div>
        <div class="flex gap-4 mt-6">
          <button class="btn btn-primary">Get Started</button>
          <button class="btn btn-secondary">Documentation</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Component-specific styles if needed */
</style>
