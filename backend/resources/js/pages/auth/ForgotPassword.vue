<template>
  <div>
    <h2 class="text-2xl font-bold text-center text-gray-900 mb-6">
      Forgot Password
    </h2>
    
    <p class="text-sm text-gray-600 text-center mb-6">
      Enter your email address and we'll send you a link to reset your password.
    </p>

    <form @submit.prevent="handleForgotPassword" class="space-y-6">
      <div v-if="success" class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
        Password reset link sent! Check your email.
      </div>

      <div v-if="error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
        {{ error }}
      </div>

      <div>
        <label for="email" class="block text-sm font-medium text-gray-700">
          {{ t('auth.email') }}
        </label>
        <input
          id="email"
          v-model="email"
          type="email"
          required
          class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
        />
      </div>

      <button
        type="submit"
        :disabled="loading"
        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
      >
        <span v-if="loading">{{ t('common.loading') }}</span>
        <span v-else>Send Reset Link</span>
      </button>
    </form>

    <div class="mt-6 text-center">
      <router-link
        :to="{ name: 'login' }"
        class="text-sm font-medium text-blue-600 hover:text-blue-500"
      >
        Back to {{ t('auth.login') }}
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const email = ref('');
const loading = ref(false);
const success = ref(false);
const error = ref(null);

const handleForgotPassword = async () => {
  loading.value = true;
  error.value = null;
  success.value = false;
  
  try {
    // Implement password reset API call here
    await new Promise(resolve => setTimeout(resolve, 1000)); // Simulated delay
    success.value = true;
  } catch (err) {
    error.value = 'Failed to send reset link. Please try again.';
  } finally {
    loading.value = false;
  }
};
</script>
