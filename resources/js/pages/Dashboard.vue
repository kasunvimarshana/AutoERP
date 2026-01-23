<template>
  <AdminLayout :page-title="$t('dashboard.title')">
    <!-- Welcome Message -->
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">{{ $t('dashboard.welcome') }}, {{ authStore.userName }}!</h3>
          </div>
          <div class="card-body">
            <p class="mb-0">Welcome to your dashboard. Here's an overview of your account.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Stats Row -->
    <div class="row">
      <!-- Email Box -->
      <div class="col-lg-4 col-6">
        <div class="small-box bg-info">
          <div class="inner">
            <h3 style="font-size: 1.2rem; overflow-wrap: break-word;">{{ authStore.userEmail }}</h3>
            <p>Email Address</p>
          </div>
          <div class="icon">
            <i class="fas fa-envelope"></i>
          </div>
        </div>
      </div>

      <!-- Roles Box -->
      <div class="col-lg-4 col-6">
        <div class="small-box bg-success">
          <div class="inner">
            <h3>{{ authStore.userRoles.length }}</h3>
            <p>
              <span v-for="role in authStore.userRoles" :key="role" class="badge badge-light mr-1">
                {{ role }}
              </span>
            </p>
            <p v-if="authStore.userRoles.length === 0">No roles assigned</p>
          </div>
          <div class="icon">
            <i class="fas fa-user-tag"></i>
          </div>
        </div>
      </div>

      <!-- Permissions Box -->
      <div class="col-lg-4 col-6">
        <div class="small-box bg-warning">
          <div class="inner">
            <h3>{{ authStore.userPermissions.length }}</h3>
            <p>Permissions</p>
          </div>
          <div class="icon">
            <i class="fas fa-shield-alt"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- User Details Card -->
    <div class="row">
      <div class="col-12">
        <div class="card card-primary">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-user mr-2"></i>Account Information</h3>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <dl class="row">
                  <dt class="col-sm-4">Name:</dt>
                  <dd class="col-sm-8">{{ authStore.userName }}</dd>
                  
                  <dt class="col-sm-4">Email:</dt>
                  <dd class="col-sm-8">{{ authStore.userEmail }}</dd>
                  
                  <dt class="col-sm-4">Roles:</dt>
                  <dd class="col-sm-8">
                    <span v-for="role in authStore.userRoles" :key="role" class="badge badge-success mr-1">
                      {{ role }}
                    </span>
                    <span v-if="authStore.userRoles.length === 0" class="text-muted">No roles assigned</span>
                  </dd>
                </dl>
              </div>
              <div class="col-md-6">
                <dl class="row">
                  <dt class="col-sm-4">Permissions:</dt>
                  <dd class="col-sm-8">{{ authStore.userPermissions.length }} permissions</dd>
                  
                  <dt class="col-sm-4">Status:</dt>
                  <dd class="col-sm-8"><span class="badge badge-success">Active</span></dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth';
import { useI18n } from 'vue-i18n';
import AdminLayout from '@/layouts/AdminLayout.vue';

const authStore = useAuthStore();
const { t } = useI18n();
</script>

<style scoped>
/* Ensure email text doesn't overflow */
.small-box .inner h3 {
  word-break: break-all;
}
</style>
