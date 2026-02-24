<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-950 px-4 py-12">
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">AutoERP</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Multi-tenant SaaS CRM / ERP Platform</p>
      </div>
      <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800 p-8">
        <slot />
      </div>
    </div>
  </div>
</template>
