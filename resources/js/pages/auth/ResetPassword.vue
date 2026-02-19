<template>
  <AuthLayout :title="$t('auth.resetPassword')">
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

    <form @submit.prevent="handleResetPassword" class="space-y-6">
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
        {{ $t('auth.resetPassword') }}
      </FormButton>
    </form>

    <template #footer>
      <div class="text-center text-sm">
        <RouterLink
          to="/login"
          class="font-medium text-indigo-600 hover:text-indigo-500"
        >
          {{ $t('auth.backToLogin') }}
        </RouterLink>
      </div>
    </template>
  </AuthLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
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
  password_confirmation: '',
  token: '',
});

const errors = reactive({
  email: '',
  password: '',
  password_confirmation: '',
});

onMounted(() => {
  // Get token and email from query params
  form.token = route.query.token || '';
  form.email = route.query.email || '';
  
  if (!form.token) {
    errorMessage.value = t('errors.generic');
  }
});

const handleResetPassword = async () => {
  // Clear previous errors
  Object.keys(errors).forEach(key => errors[key] = '');
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
  
  if (form.password !== form.password_confirmation) {
    errors.password_confirmation = t('validation.passwordMatch');
    return;
  }

  loading.value = true;

  try {
    await authStore.resetPassword({
      email: form.email,
      password: form.password,
      password_confirmation: form.password_confirmation,
      token: form.token,
    });

    successMessage.value = t('auth.passwordResetSuccess');
    
    // Redirect to login after successful reset
    setTimeout(() => {
      router.push('/login');
    }, 2000);
  } catch (error) {
    if (error.response?.data?.errors) {
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
