<template>
  <div>
    <h2 class="text-2xl font-bold text-center text-gray-900 mb-6">
      {{ t('auth.login') }}
    </h2>
    
    <form @submit.prevent="handleLogin" class="space-y-6">
      <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
        {{ error }}
      </div>

      <div>
        <label for="email" class="block text-sm font-medium text-gray-700">
          {{ t('auth.email') }}
        </label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          required
          class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
        />
      </div>

      <div>
        <label for="password" class="block text-sm font-medium text-gray-700">
          {{ t('auth.password') }}
        </label>
        <input
          id="password"
          v-model="form.password"
          type="password"
          required
          class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
        />
      </div>

      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <input
            id="remember"
            v-model="form.remember"
            type="checkbox"
            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
          />
          <label for="remember" class="ml-2 block text-sm text-gray-900">
            {{ t('auth.remember') }}
          </label>
        </div>

        <div class="text-sm">
          <router-link
            :to="{ name: 'forgot-password' }"
            class="font-medium text-blue-600 hover:text-blue-500"
          >
            {{ t('auth.forgot') }}
          </router-link>
        </div>
      </div>

      <button
        type="submit"
        :disabled="authStore.loading"
        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
      >
        <span v-if="authStore.loading">{{ t('common.loading') }}</span>
        <span v-else>{{ t('auth.login') }}</span>
      </button>
    </form>

    <div class="mt-6 text-center">
      <p class="text-sm text-gray-600">
        {{ t('auth.noAccount') }}
        <router-link
          :to="{ name: 'register' }"
          class="font-medium text-blue-600 hover:text-blue-500"
        >
          {{ t('auth.register') }}
        </router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const { t } = useI18n();
const authStore = useAuthStore();

const form = ref({
  email: '',
  password: '',
  remember: false,
});

const error = ref(null);

const handleLogin = async () => {
  error.value = null;
  try {
    await authStore.login(form.value);
    router.push({ name: 'dashboard' });
  } catch (err) {
    error.value = err.response?.data?.message || 'Login failed. Please try again.';
  }
};
</script>
