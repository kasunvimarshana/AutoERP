<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Forgot your password?
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          Enter your email and we'll send you a reset link
        </p>
      </div>

      <form
        class="mt-8 space-y-6"
        @submit.prevent="handleSubmit"
      >
        <div
          v-if="success"
          class="rounded-md bg-green-50 p-4"
        >
          <p class="text-sm text-green-800">
            {{ success }}
          </p>
        </div>

        <div
          v-if="error"
          class="rounded-md bg-red-50 p-4"
        >
          <p class="text-sm text-red-800">
            {{ error }}
          </p>
        </div>

        <div>
          <label
            for="email"
            class="label"
          >Email</label>
          <input
            id="email"
            v-model="email"
            type="email"
            required
            class="input"
          >
        </div>

        <div>
          <button
            type="submit"
            :disabled="loading"
            class="btn btn-primary w-full"
          >
            <span
              v-if="loading"
              class="spinner h-4 w-4 mr-2"
            />
            Send Reset Link
          </button>
        </div>

        <div class="text-center">
          <router-link
            to="/login"
            class="text-sm text-blue-600 hover:text-blue-500"
          >
            Back to login
          </router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { authApi } from '@/api/auth';

const email = ref('');
const loading = ref(false);
const success = ref<string | null>(null);
const error = ref<string | null>(null);

const handleSubmit = async () => {
  loading.value = true;
  success.value = null;
  error.value = null;

  try {
    await authApi.forgotPassword(email.value);
    success.value = 'Password reset link sent! Check your email.';
  } catch (err: any) {
    error.value = err.message || 'Failed to send reset link. Please try again.';
  } finally {
    loading.value = false;
  }
};
</script>
