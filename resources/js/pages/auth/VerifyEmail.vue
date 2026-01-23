<template>
  <AuthLayout>
    <div class="text-center mb-4">
      <h2 class="text-2xl font-bold text-gray-900">{{ $t('auth.verifyEmail') }}</h2>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
      <p class="mt-4 text-gray-600">{{ $t('auth.verifying') }}...</p>
    </div>

    <!-- Success State -->
    <Alert v-else-if="verified && !error" type="success" class="mb-4">
      <p class="font-medium">{{ $t('auth.emailVerifiedSuccess') }}</p>
      <p class="text-sm mt-1">{{ $t('auth.redirectingToDashboard') }}</p>
    </Alert>

    <!-- Error State -->
    <Alert v-else-if="error" type="error" class="mb-4">
      <p class="font-medium">{{ error }}</p>
    </Alert>

    <!-- Resend Verification Email -->
    <div v-if="!loading && !verified" class="text-center">
      <p class="text-gray-600 mb-4">{{ $t('auth.didNotReceiveEmail') }}</p>
      <FormButton
        @click="handleResendVerification"
        :loading="resending"
        :disabled="resending || resendCooldown > 0"
        class="w-full"
      >
        {{ resendCooldown > 0 
          ? $t('auth.resendIn', { seconds: resendCooldown }) 
          : $t('auth.resendVerification') 
        }}
      </FormButton>

      <Alert v-if="resent" type="success" class="mt-4">
        {{ $t('auth.verificationEmailSent') }}
      </Alert>
    </div>

    <!-- Back to Login Link -->
    <div class="text-center mt-6">
      <RouterLink 
        to="/login" 
        class="text-sm text-indigo-600 hover:text-indigo-500"
      >
        {{ $t('auth.backToLogin') }}
      </RouterLink>
    </div>
  </AuthLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useI18n } from 'vue-i18n';
import AuthLayout from '@/components/AuthLayout.vue';
import Alert from '@/components/Alert.vue';
import FormButton from '@/components/FormButton.vue';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const { t } = useI18n();

const loading = ref(false);
const verified = ref(false);
const error = ref('');
const resending = ref(false);
const resent = ref(false);
const resendCooldown = ref(0);

// Verify email on mount
onMounted(async () => {
  const { id, hash } = route.params;
  const expires = route.query.expires;
  const signature = route.query.signature;

  if (!id || !hash || !expires || !signature) {
    error.value = t('auth.invalidVerificationLink');
    return;
  }

  loading.value = true;
  try {
    await authStore.verifyEmail(id, hash, { expires, signature });
    verified.value = true;
    
    // Redirect to dashboard after 2 seconds
    setTimeout(() => {
      router.push('/dashboard');
    }, 2000);
  } catch (err) {
    error.value = err.response?.data?.message || t('auth.verificationFailed');
  } finally {
    loading.value = false;
  }
});

// Handle resend verification email
const handleResendVerification = async () => {
  if (resending.value || resendCooldown.value > 0) return;

  resending.value = true;
  resent.value = false;
  error.value = '';

  try {
    await authStore.resendVerification();
    resent.value = true;
    
    // Start cooldown timer (60 seconds)
    resendCooldown.value = 60;
    const interval = setInterval(() => {
      resendCooldown.value--;
      if (resendCooldown.value <= 0) {
        clearInterval(interval);
      }
    }, 1000);
  } catch (err) {
    error.value = err.response?.data?.message || t('auth.resendFailed');
  } finally {
    resending.value = false;
  }
};
</script>

<style scoped>
.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
