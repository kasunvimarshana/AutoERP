<template>
  <AuthLayout :title="$t('auth.login')">
    <Alert
      v-if="errorMessage"
      type="error"
      :message="errorMessage"
      @dismiss="errorMessage = ''"
    />
    
    <Alert
      v-if="successMessage"
      type="success"
      :message="successMessage"
      @dismiss="successMessage = ''"
    />

    <form @submit.prevent="handleLogin" class="space-y-6">
      <FormInput
        id="email"
        v-model="form.email"
        type="email"
        :label="$t('auth.email')"
        :placeholder="$t('auth.email')"
        autocomplete="email"
        required
        :error="errors.email"
      />

      <FormInput
        id="password"
        v-model="form.password"
        type="password"
        :label="$t('auth.password')"
        :placeholder="$t('auth.password')"
        autocomplete="current-password"
        required
        :error="errors.password"
      />

      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <input
            id="remember_me"
            v-model="form.remember"
            name="remember_me"
            type="checkbox"
            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
          />
          <label for="remember_me" class="ml-2 block text-sm text-gray-900">
            {{ $t('auth.rememberMe') }}
          </label>
        </div>

        <div class="text-sm">
          <RouterLink
            to="/forgot-password"
            class="font-medium text-indigo-600 hover:text-indigo-500"
          >
            {{ $t('auth.forgotPassword') }}
          </RouterLink>
        </div>
      </div>

      <FormButton type="submit" :loading="loading">
        {{ $t('auth.login') }}
      </FormButton>
    </form>

    <template #footer>
      <div class="text-center text-sm">
        <span class="text-gray-600">{{ $t('auth.dontHaveAccount') }}</span>
        <RouterLink
          to="/register"
          class="font-medium text-indigo-600 hover:text-indigo-500 ml-1"
        >
          {{ $t('auth.register') }}
        </RouterLink>
      </div>
    </template>
  </AuthLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useI18n } from 'vue-i18n';
import AuthLayout from '@/components/AuthLayout.vue';
import FormInput from '@/components/FormInput.vue';
import FormButton from '@/components/FormButton.vue';
import Alert from '@/components/Alert.vue';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const { t } = useI18n();

const loading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

const form = reactive({
  email: '',
  password: '',
  remember: false,
});

const errors = reactive({
  email: '',
  password: '',
});

const handleLogin = async () => {
  // Clear previous errors
  errors.email = '';
  errors.password = '';
  errorMessage.value = '';

  // Basic validation
  if (!form.email) {
    errors.email = t('validation.required');
    return;
  }
  
  if (!form.password) {
    errors.password = t('validation.required');
    return;
  }

  loading.value = true;

  try {
    await authStore.login({
      email: form.email,
      password: form.password,
    });

    successMessage.value = t('auth.loginSuccess');
    
    // Redirect to intended page or dashboard
    const redirectTo = route.query.redirect || '/dashboard';
    setTimeout(() => {
      router.push(redirectTo);
    }, 500);
  } catch (error) {
    if (error.response?.data?.errors) {
      // Handle validation errors
      const validationErrors = error.response.data.errors;
      if (validationErrors.email) {
        errors.email = validationErrors.email[0];
      }
      if (validationErrors.password) {
        errors.password = validationErrors.password[0];
      }
    } else {
      errorMessage.value = error.response?.data?.message || t('errors.generic');
    }
  } finally {
    loading.value = false;
  }
};
</script>
