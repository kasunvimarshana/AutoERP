<template>
  <div class="profile-view">
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-900">
        Profile
      </h1>
      <p class="mt-1 text-sm text-gray-600">
        Manage your personal information and account settings
      </p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
      <!-- Profile Photo -->
      <div class="lg:col-span-1">
        <div class="bg-white shadow rounded-lg p-6">
          <div class="text-center">
            <div class="mx-auto h-32 w-32 rounded-full bg-primary-100 flex items-center justify-center text-primary-600 text-4xl font-bold">
              {{ initials }}
            </div>
            <h3 class="mt-4 text-lg font-medium text-gray-900">
              {{ profile.name }}
            </h3>
            <p class="text-sm text-gray-500">
              {{ profile.email }}
            </p>
            
            <button
              class="mt-4 w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500"
            >
              Change Photo
            </button>
          </div>
        </div>
      </div>

      <!-- Profile Form -->
      <div class="lg:col-span-2">
        <div class="bg-white shadow rounded-lg p-6">
          <h2 class="text-lg font-medium text-gray-900 mb-6">
            Personal Information
          </h2>
          
          <form
            class="space-y-6"
            @submit.prevent="saveProfile"
          >
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
              <div>
                <label class="block text-sm font-medium text-gray-700">First Name</label>
                <input
                  v-model="profile.firstName"
                  type="text"
                  required
                  class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                >
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Last Name</label>
                <input
                  v-model="profile.lastName"
                  type="text"
                  required
                  class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                >
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Email Address</label>
              <input
                v-model="profile.email"
                type="email"
                required
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
              >
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Phone Number</label>
              <input
                v-model="profile.phone"
                type="tel"
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
              >
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Job Title</label>
              <input
                v-model="profile.jobTitle"
                type="text"
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
              >
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Bio</label>
              <textarea
                v-model="profile.bio"
                rows="4"
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
              />
            </div>

            <div class="pt-4 border-t border-gray-200 flex justify-end">
              <button
                type="submit"
                :disabled="saving"
                class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:opacity-50"
              >
                {{ saving ? 'Saving...' : 'Save Changes' }}
              </button>
            </div>
          </form>
        </div>

        <!-- Change Password -->
        <div class="mt-6 bg-white shadow rounded-lg p-6">
          <h2 class="text-lg font-medium text-gray-900 mb-6">
            Change Password
          </h2>
          
          <form
            class="space-y-6"
            @submit.prevent="changePassword"
          >
            <div>
              <label class="block text-sm font-medium text-gray-700">Current Password</label>
              <input
                v-model="passwordForm.current"
                type="password"
                required
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
              >
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">New Password</label>
              <input
                v-model="passwordForm.new"
                type="password"
                required
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
              >
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Confirm New Password</label>
              <input
                v-model="passwordForm.confirm"
                type="password"
                required
                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
              >
            </div>

            <div class="pt-4 border-t border-gray-200 flex justify-end">
              <button
                type="submit"
                :disabled="changingPassword"
                class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:opacity-50"
              >
                {{ changingPassword ? 'Changing...' : 'Change Password' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue';

const saving = ref(false);
const changingPassword = ref(false);

const profile = reactive({
  name: 'John Doe',
  firstName: 'John',
  lastName: 'Doe',
  email: 'john.doe@example.com',
  phone: '+1 (555) 123-4567',
  jobTitle: 'System Administrator',
  bio: 'Experienced system administrator with a passion for automation and optimization.',
});

const passwordForm = reactive({
  current: '',
  new: '',
  confirm: '',
});

const initials = computed(() => {
  return `${profile.firstName[0]}${profile.lastName[0]}`.toUpperCase();
});

const saveProfile = async () => {
  saving.value = true;
  
  try {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1000));
    
    profile.name = `${profile.firstName} ${profile.lastName}`;
    console.log('Profile saved successfully');
  } catch (error) {
    console.error('Failed to save profile', error);
  } finally {
    saving.value = false;
  }
};

const changePassword = async () => {
  if (passwordForm.new !== passwordForm.confirm) {
    console.error('Passwords do not match');
    return;
  }
  
  changingPassword.value = true;
  
  try {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1000));
    
    console.log('Password changed successfully');
    
    // Reset form
    passwordForm.current = '';
    passwordForm.new = '';
    passwordForm.confirm = '';
  } catch (error) {
    console.error('Failed to change password', error);
  } finally {
    changingPassword.value = false;
  }
};
</script>
