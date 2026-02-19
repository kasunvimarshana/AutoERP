<template>
  <AuthLayout :title="$t('auth.register')">
    <Alert
      v-if="errorMessage"
      type="error"
      :message="errorMessage"
      @dismiss="errorMessage = ''"
    />

    <form @submit.prevent="handleRegister" class="space-y-6">
      <FormInput
        id="name"
        v-model="form.name"
        type="text"
        :label="$t('auth.name')"
        :placeholder="$t('auth.name')"
        autocomplete="name"
        required
        :error="errors.name"
      />

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
        autocomplete="new-password"
        required
        :error="errors.password"
        :hint="$t('validation.passwordStrength')"
      />

      <FormInput
        id="password_confirmation"
        v-model="form.password_confirmation"
        type="password"
        :label="$t('auth.passwordConfirmation')"
        :placeholder="$t('auth.passwordConfirmation')"
        autocomplete="new-password"
        required
        :error="errors.password_confirmation"
      />

      <FormButton type="submit" :loading="loading">
        {{ $t('auth.register') }}
      </FormButton>
    </form>

    <template #footer>
      <div class="text-center text-sm">
        <span class="text-gray-600">{{ $t('auth.alreadyHaveAccount') }}</span>
        <RouterLink
          to="/login"
          class="font-medium text-indigo-600 hover:text-indigo-500 ml-1"
        >
          {{ $t('auth.login') }}
        </RouterLink>
      </div>
    </template>
  </AuthLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useI18n } from 'vue-i18n';
import AuthLayout from '@/components/AuthLayout.vue';
import FormInput from '@/components/FormInput.vue';
import FormButton from '@/components/FormButton.vue';
import Alert from '@/components/Alert.vue';

const router = useRouter();
const authStore = useAuthStore();
const { t } = useI18n();

const loading = ref(false);
const errorMessage = ref('');

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
});

const errors = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
});

const handleRegister = async () => {
  // Clear previous errors
  Object.keys(errors).forEach(key => errors[key] = '');
  errorMessage.value = '';

  // Basic validation
  if (!form.name) {
    errors.name = t('validation.required');
    return;
  }
  
  if (!form.email) {
    errors.email = t('validation.required');
    return;
  }
  
  if (!form.password) {
    errors.password = t('validation.required');
    return;
  }
  
  if (form.password !== form.password_confirmation) {
    errors.password_confirmation = t('validation.passwordMatch');
    return;
  }

  loading.value = true;

  try {
    await authStore.register({
      name: form.name,
      email: form.email,
      password: form.password,
      password_confirmation: form.password_confirmation,
    });

    // Redirect to dashboard on successful registration
    router.push('/dashboard');
  } catch (error) {
    if (error.response?.data?.errors) {
      // Handle validation errors
      const validationErrors = error.response.data.errors;
      Object.keys(validationErrors).forEach(key => {
        if (errors[key] !== undefined) {
          errors[key] = validationErrors[key][0];
        }
      });
    } else {
      errorMessage.value = error.response?.data?.message || t('errors.generic');
    }
  } finally {
    loading.value = false;
  }
};
</script>
