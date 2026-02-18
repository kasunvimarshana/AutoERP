<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4">
    <div class="max-w-md w-full">
      <div class="text-center">
        <h1 class="text-6xl font-bold text-gray-900">
          403
        </h1>
        <h2 class="mt-2 text-3xl font-bold text-gray-900">
          Access Denied
        </h2>
        <p class="mt-2 text-base text-gray-500">
          You don't have permission to access this resource.
        </p>
        <div class="mt-6">
          <router-link
            to="/"
            class="btn btn-primary"
          >
            Go back home
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
//
</script>
