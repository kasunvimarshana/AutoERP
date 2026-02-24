<template>
  <AuthLayout>
    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 text-center">Sign in to your account</h2>

    <form @submit.prevent="handleSubmit" novalidate>
      <div class="space-y-4">
        <!-- Email -->
        <div>
          <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            Email address
          </label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            autocomplete="email"
            required
            :disabled="loading"
            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50"
            placeholder="you@example.com"
          />
        </div>

        <!-- Password -->
        <div>
          <div class="flex items-center justify-between mb-1">
            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              Password
            </label>
            <RouterLink
              :to="{ name: 'forgot-password' }"
              class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline"
            >
              Forgot password?
            </RouterLink>
          </div>
          <input
            id="password"
            v-model="form.password"
            type="password"
            autocomplete="current-password"
            required
            :disabled="loading"
            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50"
            placeholder="••••••••"
          />
        </div>

        <!-- Error -->
        <p v-if="error" role="alert" class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>

        <!-- Submit -->
        <button
          type="submit"
          :disabled="loading"
          class="w-full flex items-center justify-center gap-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 px-4 py-2.5 text-sm font-semibold text-white transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
        >
          <svg v-if="loading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
          </svg>
          <span>{{ loading ? 'Signing in…' : 'Sign in' }}</span>
        </button>
      </div>
    </form>

    <p class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
      Don't have an account?
      <RouterLink :to="{ name: 'register' }" class="text-indigo-600 dark:text-indigo-400 font-medium hover:underline">
        Register
      </RouterLink>
    </p>
  </AuthLayout>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { RouterLink } from 'vue-router';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const router = useRouter();

const form = reactive({ email: '', password: '' });
const loading = ref(false);
const error = ref('');

async function handleSubmit() {
    error.value = '';
    loading.value = true;
    try {
        await auth.login(form.email, form.password);
        router.push({ name: 'dashboard' });
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Login failed. Please check your credentials.';
    } finally {
        loading.value = false;
    }
}
</script>
