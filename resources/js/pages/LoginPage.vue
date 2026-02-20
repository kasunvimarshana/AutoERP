<template>
  <div>
    <div class="text-center mb-6">
      <h2 class="text-xl font-bold text-gray-900">Sign in to your account</h2>
      <p class="text-sm text-gray-500 mt-1">Enter your credentials to continue</p>
    </div>

    <form class="space-y-5" @submit.prevent="handleLogin">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1" for="login-email">Email</label>
        <input
          id="login-email"
          v-model="form.email"
          type="email"
          required
          autocomplete="email"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          placeholder="you@example.com"
        />
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1" for="login-password"
          >Password</label
        >
        <input
          id="login-password"
          v-model="form.password"
          type="password"
          required
          autocomplete="current-password"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          placeholder="••••••••"
        />
      </div>

      <div
        v-if="error"
        class="flex items-center gap-2 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-3 py-2"
        role="alert"
      >
        <span aria-hidden="true">❌</span>
        <span>{{ error }}</span>
      </div>

      <button
        type="submit"
        :disabled="loading"
        class="w-full flex items-center justify-center gap-2 bg-blue-600 text-white py-2.5 rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-60 transition-colors"
      >
        <AppSpinner v-if="loading" size="sm" />
        <span>{{ loading ? 'Signing in…' : 'Sign In' }}</span>
      </button>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import AppSpinner from '@/components/AppSpinner.vue';
import type { ApiError } from '@/types/index';

const router = useRouter();
const route = useRoute();
const auth = useAuthStore();

const form = ref({ email: '', password: '' });
const error = ref<string | null>(null);
const loading = ref(false);

async function handleLogin(): Promise<void> {
  error.value = null;
  loading.value = true;
  try {
    await auth.login(form.value.email, form.value.password);
    const redirect = (route.query.redirect as string | undefined) ?? '/dashboard';
    await router.push(redirect);
  } catch (e: unknown) {
    const axiosError = e as { response?: { data?: ApiError } };
    error.value = axiosError.response?.data?.message ?? 'Login failed. Please try again.';
  } finally {
    loading.value = false;
  }
}
</script>
