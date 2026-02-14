<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary-500 to-primary-700">
    <div class="w-full max-w-md">
      <div class="bg-white rounded-2xl shadow-2xl p-8">
        <div class="text-center mb-8">
          <h1 class="text-3xl font-bold text-primary-600">AutoERP</h1>
          <p class="text-secondary-600 mt-2">{{ $t('auth.loginSubtitle') }}</p>
        </div>
        <router-view />
      </div>
    </div>
  </div>
</template>

<script setup>
</script>
