<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <!-- Logo and Title -->
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Sign in to your account
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          Or
          <router-link
            to="/register"
            class="font-medium text-blue-600 hover:text-blue-500"
          >
            create a new account
          </router-link>
        </p>
      </div>

      <!-- Login Form -->
      <form
        class="mt-8 space-y-6"
        @submit="handleSubmit"
      >
        <div
          v-if="error"
          class="rounded-md bg-red-50 p-4"
        >
          <p class="text-sm text-red-800">
            {{ error }}
          </p>
        </div>

        <div class="rounded-md shadow-sm -space-y-px">
          <div>
            <label
              for="email"
              class="sr-only"
            >Email address</label>
            <input
              id="email"
              v-model="values.email"
              type="email"
              required
              class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
              placeholder="Email address"
              @blur="setFieldTouched('email')"
            >
            <p
              v-if="errors.email && touched.email"
              class="error-message"
            >
              {{ errors.email }}
            </p>
          </div>
          <div>
            <label
              for="password"
              class="sr-only"
            >Password</label>
            <input
              id="password"
              v-model="values.password"
              type="password"
              required
              class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
              placeholder="Password"
              @blur="setFieldTouched('password')"
            >
            <p
              v-if="errors.password && touched.password"
              class="error-message"
            >
              {{ errors.password }}
            </p>
          </div>
        </div>

        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <input
              id="remember-me"
              v-model="values.remember"
              type="checkbox"
              class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
            >
            <label
              for="remember-me"
              class="ml-2 block text-sm text-gray-900"
            >
              Remember me
            </label>
          </div>

          <div class="text-sm">
            <router-link
              to="/forgot-password"
              class="font-medium text-blue-600 hover:text-blue-500"
            >
              Forgot your password?
            </router-link>
          </div>
        </div>

        <div>
          <button
            type="submit"
            :disabled="isSubmitting"
            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
          >
            <span
              v-if="isSubmitting"
              class="spinner h-4 w-4 mr-2"
            />
            Sign in
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { object, string } from 'yup';
import { useForm } from '@/composables/useForm';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();
const error = ref<string | null>(null);

const validationSchema = object({
  email: string().email('Invalid email address').required('Email is required'),
  password: string().required('Password is required'),
});

const {
  values,
  errors,
  touched,
  isSubmitting,
  setFieldTouched,
  handleSubmit,
} = useForm({
  initialValues: {
    email: '',
    password: '',
    remember: false,
  },
  validationSchema,
  onSubmit: async (formValues) => {
    error.value = null;
    try {
      await authStore.login({
        email: formValues.email,
        password: formValues.password,
      });
      router.push('/');
    } catch (err: any) {
      error.value = err.message || 'Login failed. Please try again.';
    }
  },
});
</script>
