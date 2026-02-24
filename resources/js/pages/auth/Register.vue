<template>
  <AuthLayout>
    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 text-center">Create your account</h2>

    <form @submit.prevent="handleSubmit" novalidate>
      <div class="space-y-4">
        <!-- Name -->
        <div>
          <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Full name</label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            autocomplete="name"
            required
            :disabled="loading"
            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50"
            placeholder="Jane Smith"
          />
        </div>

        <!-- Email -->
        <div>
          <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email address</label>
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
          <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password</label>
          <input
            id="password"
            v-model="form.password"
            type="password"
            autocomplete="new-password"
            required
            :disabled="loading"
            class="w-full rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50"
            placeholder="Min. 8 characters"
          />
        </div>

        <!-- Password Confirmation -->
        <div>
          <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirm password</label>
          <input
            id="password_confirmation"
            v-model="form.passwordConfirmation"
            type="password"
            autocomplete="new-password"
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
          <span>{{ loading ? 'Creating account…' : 'Create account' }}</span>
        </button>
      </div>
    </form>

    <p class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
      Already have an account?
      <RouterLink :to="{ name: 'login' }" class="text-indigo-600 dark:text-indigo-400 font-medium hover:underline">
        Sign in
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

const form = reactive({ name: '', email: '', password: '', passwordConfirmation: '' });
const loading = ref(false);
const error = ref('');

async function handleSubmit() {
    error.value = '';
    loading.value = true;
    try {
        await auth.register(form.name, form.email, form.password, form.passwordConfirmation);
        router.push({ name: 'dashboard' });
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Registration failed. Please try again.';
    } finally {
        loading.value = false;
    }
}
</script>
