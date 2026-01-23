<template>
  <AuthLayout :title="$t('auth.forgotPassword')">
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

    <form v-if="!emailSent" @submit.prevent="handleForgotPassword" class="space-y-6">
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

      <FormButton type="submit" :loading="loading">
        {{ $t('auth.sendResetLink') }}
      </FormButton>
    </form>

    <div v-else class="text-center">
      <p class="text-sm text-gray-600 mb-4">
        {{ $t('auth.passwordResetSent') }}
      </p>
      <RouterLink
        to="/login"
        class="font-medium text-indigo-600 hover:text-indigo-500"
      >
        {{ $t('auth.backToLogin') }}
      </RouterLink>
    </div>

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
import { ref, reactive } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useI18n } from 'vue-i18n';
import AuthLayout from '@/components/AuthLayout.vue';
import FormInput from '@/components/FormInput.vue';
import FormButton from '@/components/FormButton.vue';
import Alert from '@/components/Alert.vue';

const authStore = useAuthStore();
const { t } = useI18n();

const loading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const emailSent = ref(false);

const form = reactive({
  email: '',
});

const errors = reactive({
  email: '',
});

const handleForgotPassword = async () => {
  // Clear previous errors
  errors.email = '';
  errorMessage.value = '';

  // Basic validation
  if (!form.email) {
    errors.email = t('validation.required');
    return;
  }

  loading.value = true;

  try {
    await authStore.forgotPassword(form.email);
    
    emailSent.value = true;
    successMessage.value = t('auth.passwordResetSent');
  } catch (error) {
    if (error.response?.data?.errors) {
      const validationErrors = error.response.data.errors;
      if (validationErrors.email) {
        errors.email = validationErrors.email[0];
      }
    } else {
      errorMessage.value = error.response?.data?.message || t('errors.generic');
    }
  } finally {
    loading.value = false;
  }
};
</script>
