<template>
  <div class="min-h-screen flex flex-col items-center justify-center bg-gray-50 dark:bg-gray-950 px-4 text-center">
    <div class="w-16 h-16 rounded-2xl bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center mb-6">
      <svg
        class="w-8 h-8 text-indigo-600 dark:text-indigo-400"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        stroke-width="1.5"
        aria-hidden="true"
      >
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
      </svg>
    </div>

    <h1 class="text-6xl font-extrabold text-gray-900 dark:text-white tracking-tight">404</h1>
    <p class="mt-3 text-xl font-semibold text-gray-700 dark:text-gray-300">Page not found</p>
    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 max-w-sm">
      The page you're looking for doesn't exist or has been moved.
    </p>

    <div class="mt-8 flex flex-col sm:flex-row gap-3">
      <button
        type="button"
        class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 px-5 py-2.5 text-sm font-semibold text-white transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
        @click="goBack"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Go back
      </button>
      <RouterLink
        to="/dashboard"
        class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-5 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
        </svg>
        Dashboard
      </RouterLink>
    </div>
  </div>
</template>

<script setup>
import { RouterLink, useRouter } from 'vue-router';

const router = useRouter();

function goBack() {
    if (window.history.length > 1) {
        router.back();
    } else {
        router.push('/dashboard');
    }
}
</script>
