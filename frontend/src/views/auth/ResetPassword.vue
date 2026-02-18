<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Reset your password
        </h2>
      </div>

      <form
        class="mt-8 space-y-6"
        @submit.prevent="handleSubmit"
      >
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
            for="password"
            class="label"
          >New Password</label>
          <input
            id="password"
            v-model="password"
            type="password"
            required
            class="input"
          >
        </div>

        <div>
          <label
            for="password_confirmation"
            class="label"
          >Confirm Password</label>
          <input
            id="password_confirmation"
            v-model="passwordConfirmation"
            type="password"
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
            Reset Password
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { authApi } from '@/api/auth';

const route = useRoute();
const router = useRouter();

const token = route.params.token as string;
const password = ref('');
const passwordConfirmation = ref('');
const loading = ref(false);
const error = ref<string | null>(null);

const handleSubmit = async () => {
  if (password.value !== passwordConfirmation.value) {
    error.value = 'Passwords do not match';
    return;
  }

  loading.value = true;
  error.value = null;

  try {
    await authApi.resetPassword(token, password.value, passwordConfirmation.value);
    router.push('/login');
  } catch (err: any) {
    error.value = err.message || 'Failed to reset password. Please try again.';
  } finally {
    loading.value = false;
  }
};
</script>
