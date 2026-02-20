<template>
  <div
    class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-900 via-gray-800 to-blue-900"
  >
    <!-- Background pattern -->
    <div
      class="absolute inset-0 opacity-10"
      style="
        background-image: url(&quot;data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%239C92AC' fill-opacity='0.4'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E&quot;);
      "
    />

    <div class="relative w-full max-w-md px-4">
      <!-- Logo -->
      <div class="text-center mb-8">
        <div
          class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-blue-600 shadow-lg mb-4"
        >
          <span class="text-3xl">🏢</span>
        </div>
        <h1 class="text-2xl font-bold text-white tracking-tight">AutoERP</h1>
        <p class="text-sm text-gray-400 mt-1">Multi-tenant SaaS Platform</p>
      </div>

      <!-- Card -->
      <div class="bg-white rounded-2xl shadow-2xl p-8">
        <RouterView />
      </div>

      <!-- Footer -->
      <p class="text-center text-xs text-gray-500 mt-6">
        &copy; {{ year }} AutoERP. All rights reserved.
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { RouterView } from 'vue-router';

const year = new Date().getFullYear();
</script>
