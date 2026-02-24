<template>
  <AuthLayout>
    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2 text-center">Reset your password</h2>
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6 text-center">
      Enter your email and we'll send you a reset link.
    </p>

    <template v-if="!sent">
      <form @submit.prevent="handleSubmit" novalidate>
        <div class="space-y-4">
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email address</label>
            <input
              id="email"
              v-model="email"
              type="email"
              autocomplete="email"
              required
              :disabled="loading"
              class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50"
              placeholder="you@example.com"
            />
          </div>

          <p v-if="error" role="alert" class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>

          <button
            type="submit"
            :disabled="loading"
            class="w-full flex items-center justify-center gap-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 px-4 py-2.5 text-sm font-semibold text-white transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
          >
            <svg v-if="loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24" aria-hidden="true">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
            </svg>
            <span>{{ loading ? 'Sending…' : 'Send reset link' }}</span>
          </button>
        </div>
      </form>
    </template>

    <template v-else>
      <div class="text-center space-y-4">
        <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center mx-auto">
          <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <p class="text-sm text-gray-700 dark:text-gray-300">
          If an account exists for <strong>{{ email }}</strong>, you'll receive a reset link shortly.
        </p>
      </div>
    </template>

    <p class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
      <RouterLink :to="{ name: 'login' }" class="text-indigo-600 dark:text-indigo-400 font-medium hover:underline">
        Back to sign in
      </RouterLink>
    </p>
  </AuthLayout>
</template>

<script setup>
import { ref } from 'vue';
import { RouterLink } from 'vue-router';
import AuthLayout from '@/layouts/AuthLayout.vue';
import api from '@/composables/useApi';

const email = ref('');
const loading = ref(false);
const error = ref('');
const sent = ref(false);

async function handleSubmit() {
    error.value = '';
    loading.value = true;
    try {
        await api.post('/api/v1/auth/forgot-password', { email: email.value });
        sent.value = true;
    } catch (e) {
        // Show generic success to avoid email enumeration
        sent.value = true;
    } finally {
        loading.value = false;
    }
}
</script>
