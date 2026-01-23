<template>
  <AdminLayout :page-title="$t('profile.myProfile')">
    <!-- Profile Card -->
    <div class="row">
      <div class="col-md-4">
        <!-- Profile Image -->
        <div class="card card-primary card-outline">
          <div class="card-body box-profile">
            <div class="text-center">
              <div class="profile-user-img img-fluid img-circle bg-primary d-inline-flex align-items-center justify-content-center profile-avatar">
                <i class="fas fa-user fa-3x text-white"></i>
              </div>
            </div>

            <h3 class="profile-username text-center">{{ authStore.user?.name }}</h3>

            <p class="text-muted text-center">{{ authStore.user?.email }}</p>

            <ul class="list-group list-group-unbordered mb-3">
              <li class="list-group-item">
                <b>Roles</b> <span class="float-right badge badge-primary">{{ authStore.userRoles.length }}</span>
              </li>
              <li class="list-group-item">
                <b>Permissions</b> <span class="float-right badge badge-success">{{ authStore.userPermissions.length }}</span>
              </li>
              <li class="list-group-item">
                <b>Status</b> <span class="float-right badge badge-success">Active</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <div class="col-md-8">
        <!-- About Me Box -->
        <div class="card card-primary">
          <div class="card-header">
            <h3 class="card-title">Profile Information</h3>
          </div>
          <div class="card-body">
            <strong><i class="fas fa-user mr-1"></i> Full Name</strong>
            <p class="text-muted">{{ authStore.user?.name }}</p>
            <hr>

            <strong><i class="fas fa-envelope mr-1"></i> Email Address</strong>
            <p class="text-muted">{{ authStore.user?.email }}</p>
            <hr>

            <strong><i class="fas fa-user-tag mr-1"></i> Roles</strong>
            <p class="text-muted">
              <span v-for="role in authStore.userRoles" :key="role" class="badge badge-primary mr-1">
                {{ role }}
              </span>
              <span v-if="authStore.userRoles.length === 0">No roles assigned</span>
            </p>
            <hr>

            <strong><i class="fas fa-shield-alt mr-1"></i> Permissions</strong>
            <p class="text-muted">
              <span v-for="permission in authStore.userPermissions" :key="permission" class="badge badge-secondary mr-1 mb-1">
                {{ permission }}
              </span>
              <span v-if="authStore.userPermissions.length === 0">No permissions assigned</span>
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Roles Card -->
    <div class="row" v-if="authStore.userRoles.length > 0">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-user-tag mr-2"></i>Assigned Roles</h3>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-3 col-sm-6" v-for="role in authStore.userRoles" :key="role">
                <div class="info-box bg-gradient-success">
                  <span class="info-box-icon"><i class="fas fa-user-shield"></i></span>
                  <div class="info-box-content">
                    <span class="info-box-text">Role</span>
                    <span class="info-box-number">{{ role }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Permissions Card -->
    <div class="row" v-if="authStore.userPermissions.length > 0">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-shield-alt mr-2"></i>Permissions ({{ authStore.userPermissions.length }})</h3>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-4 col-sm-6 mb-2" v-for="permission in authStore.userPermissions" :key="permission">
                <span class="badge badge-info p-2 d-block">
                  <i class="fas fa-check-circle mr-1"></i>{{ permission }}
                </span>
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
.profile-user-img {
  border: 3px solid #adb5bd;
  margin: 0 auto;
  padding: 3px;
}

.profile-avatar {
  width: 100px;
  height: 100px;
}
</style>
