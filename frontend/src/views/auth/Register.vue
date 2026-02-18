<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          Create your account
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          Already have an account?
          <router-link
            to="/login"
            class="font-medium text-blue-600 hover:text-blue-500"
          >
            Sign in
          </router-link>
        </p>
      </div>

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

        <div class="space-y-4">
          <div>
            <label
              for="name"
              class="label"
            >Full Name</label>
            <input
              id="name"
              v-model="values.name"
              type="text"
              required
              class="input"
              @blur="setFieldTouched('name')"
            >
            <p
              v-if="errors.name && touched.name"
              class="error-message"
            >
              {{ errors.name }}
            </p>
          </div>

          <div>
            <label
              for="email"
              class="label"
            >Email</label>
            <input
              id="email"
              v-model="values.email"
              type="email"
              required
              class="input"
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
              for="tenant_name"
              class="label"
            >Company Name</label>
            <input
              id="tenant_name"
              v-model="values.tenant_name"
              type="text"
              required
              class="input"
              @blur="setFieldTouched('tenant_name')"
            >
            <p
              v-if="errors.tenant_name && touched.tenant_name"
              class="error-message"
            >
              {{ errors.tenant_name }}
            </p>
          </div>

          <div>
            <label
              for="password"
              class="label"
            >Password</label>
            <input
              id="password"
              v-model="values.password"
              type="password"
              required
              class="input"
              @blur="setFieldTouched('password')"
            >
            <p
              v-if="errors.password && touched.password"
              class="error-message"
            >
              {{ errors.password }}
            </p>
          </div>

          <div>
            <label
              for="password_confirmation"
              class="label"
            >Confirm Password</label>
            <input
              id="password_confirmation"
              v-model="values.password_confirmation"
              type="password"
              required
              class="input"
              @blur="setFieldTouched('password_confirmation')"
            >
            <p
              v-if="errors.password_confirmation && touched.password_confirmation"
              class="error-message"
            >
              {{ errors.password_confirmation }}
            </p>
          </div>
        </div>

        <div>
          <button
            type="submit"
            :disabled="isSubmitting"
            class="btn btn-primary w-full"
          >
            <span
              v-if="isSubmitting"
              class="spinner h-4 w-4 mr-2"
            />
            Create Account
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { object, string, ref as yupRef } from 'yup';
import { useForm } from '@/composables/useForm';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();
const error = ref<string | null>(null);

const validationSchema = object({
  name: string().required('Name is required'),
  email: string().email('Invalid email address').required('Email is required'),
  tenant_name: string().required('Company name is required'),
  password: string()
    .min(8, 'Password must be at least 8 characters')
    .required('Password is required'),
  password_confirmation: string()
    .oneOf([yupRef('password')], 'Passwords must match')
    .required('Password confirmation is required'),
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
    name: '',
    email: '',
    tenant_name: '',
    tenant_slug: '',
    password: '',
    password_confirmation: '',
  },
  validationSchema,
  onSubmit: async (formValues) => {
    error.value = null;
    try {
      // Generate slug from tenant name
      formValues.tenant_slug = formValues.tenant_name
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '');

      await authStore.register(formValues);
      router.push('/');
    } catch (err: any) {
      error.value = err.message || 'Registration failed. Please try again.';
    }
  },
});
</script>
