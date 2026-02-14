<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div class="text-center">
        <h1 class="text-3xl font-bold text-gray-900">AutoERP</h1>
        <p class="mt-2 text-sm text-gray-600">Enterprise Resource Planning</p>
      </div>

      <div class="bg-white shadow-md rounded-lg p-8">
        <router-view />
      </div>
    </div>
  </div>
</template>

<script setup>
</script>
