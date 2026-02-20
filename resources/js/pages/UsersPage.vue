<template>
  <div class="space-y-4">
    <PageHeader title="Users" subtitle="Identity and access management">
      <template #actions>
        <input
          v-model="search"
          type="search"
          placeholder="Search users…"
          class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-48"
          @input="onSearchInput"
        />
        <button
          v-if="auth.hasPermission('user.create')"
          class="flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-1.5 rounded-lg transition-colors"
          @click="openCreate"
        >
          <span class="text-base leading-none">+</span> Invite User
        </button>
      </template>
    </PageHeader>

    <div v-if="loading" class="flex items-center justify-center py-16">
      <AppSpinner size="lg" />
    </div>
    <div v-else-if="error" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
      {{ error }}
    </div>

    <div v-else class="bg-white shadow rounded-lg overflow-hidden">
      <AppEmptyState
        v-if="items.length === 0"
        icon="👥"
        title="No users found"
        :message="search ? 'Try a different search term.' : 'No users have been added yet.'"
      />
      <template v-else>
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Roles</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="user in items" :key="user.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-sm text-gray-900 font-medium">{{ user.name }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ user.email }}</td>
              <td class="px-4 py-3 text-sm">
                <StatusBadge :status="user.status" />
              </td>
              <td class="px-4 py-3 text-sm text-gray-500">
                {{ user.roles?.map((r) => r.name).join(', ') ?? '—' }}
              </td>
              <td class="px-4 py-3 text-right">
                <div class="flex items-center justify-end gap-2">
                  <button
                    v-if="auth.hasPermission('user.update')"
                    class="text-xs text-blue-600 hover:underline"
                    @click="openEdit(user)"
                  >Edit</button>
                  <button
                    v-if="auth.hasPermission('user.update') && user.status === 'active'"
                    class="text-xs text-orange-500 hover:underline"
                    :disabled="actionUserId === user.id"
                    @click="suspendUser(user)"
                  >Suspend</button>
                  <button
                    v-if="auth.hasPermission('user.update') && user.status === 'suspended'"
                    class="text-xs text-green-600 hover:underline"
                    :disabled="actionUserId === user.id"
                    @click="activateUser(user)"
                  >Activate</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <AppPaginator
          :page="page"
          :last-page="lastPage"
          :per-page="perPage"
          :total="total"
          @prev="prevPage"
          @next="nextPage"
          @go-to="goToPage"
        />
      </template>
    </div>
  </div>

  <!-- Create / Edit User Modal -->
  <AppModal v-model="showForm" :title="editTarget ? 'Edit User' : 'Invite User'">
    <form id="user-form" class="space-y-4" @submit.prevent="handleSubmit">
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
          <input v-model="form.name" type="text" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
          <input v-model="form.email" type="email" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Password <span v-if="!editTarget" class="text-red-500">*</span>
          <span v-else class="text-xs text-gray-400">(leave blank to keep current)</span>
        </label>
        <input
          v-model="form.password"
          type="password"
          :required="!editTarget"
          autocomplete="new-password"
          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="••••••••"
        />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
        <select v-model="form.role" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
          <option value="">— No role —</option>
          <option value="tenant-admin">Tenant Admin</option>
          <option value="manager">Manager</option>
          <option value="staff">Staff</option>
          <option value="viewer">Viewer</option>
        </select>
      </div>
      <div v-if="formError" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded px-3 py-2">{{ formError }}</div>
    </form>
    <template #footer>
      <button type="button" class="px-4 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50" @click="showForm = false">Cancel</button>
      <button type="submit" form="user-form" :disabled="saving" class="flex items-center gap-2 px-4 py-2 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-60">
        <AppSpinner v-if="saving" size="sm" />
        {{ saving ? 'Saving…' : 'Save' }}
      </button>
    </template>
  </AppModal>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useListPage } from '@/composables/useListPage';
import { userService } from '@/services/users';
import { useAuthStore } from '@/stores/auth';
import { useNotificationStore } from '@/stores/notifications';
import type { User } from '@/types/index';
import PageHeader from '@/components/PageHeader.vue';
import AppSpinner from '@/components/AppSpinner.vue';
import AppEmptyState from '@/components/AppEmptyState.vue';
import AppModal from '@/components/AppModal.vue';
import AppPaginator from '@/components/AppPaginator.vue';
import StatusBadge from '@/components/StatusBadge.vue';

const auth = useAuthStore();
const notify = useNotificationStore();

// ─── List ────────────────────────────────────────────────────────────────────

const search = ref('');
let searchDebounce: ReturnType<typeof setTimeout> | null = null;

const { items, loading, error, page, perPage, total, lastPage, load, nextPage, prevPage, goToPage } =
  useListPage<User>({
    endpoint: '/users',
    params: () => (search.value ? { search: search.value } : {}),
  });

void load();

function onSearchInput(): void {
  if (searchDebounce) clearTimeout(searchDebounce);
  searchDebounce = setTimeout(() => {
    page.value = 1;
    void load();
  }, 300);
}

// ─── Actions ─────────────────────────────────────────────────────────────────

const actionUserId = ref<number | null>(null);

async function suspendUser(user: User): Promise<void> {
  if (!confirm(`Suspend user "${user.name}"?`)) return;
  actionUserId.value = user.id;
  try {
    await userService.suspend(user.id);
    notify.success('User suspended.');
    void load();
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } };
    notify.error(err.response?.data?.message ?? 'Failed to suspend user.');
  } finally {
    actionUserId.value = null;
  }
}

async function activateUser(user: User): Promise<void> {
  actionUserId.value = user.id;
  try {
    await userService.activate(user.id);
    notify.success('User activated.');
    void load();
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } };
    notify.error(err.response?.data?.message ?? 'Failed to activate user.');
  } finally {
    actionUserId.value = null;
  }
}

// ─── Form ────────────────────────────────────────────────────────────────────

const showForm = ref(false);
const editTarget = ref<User | null>(null);
const saving = ref(false);
const formError = ref<string | null>(null);
const form = ref({ name: '', email: '', password: '', role: '' });

function openCreate(): void {
  editTarget.value = null;
  form.value = { name: '', email: '', password: '', role: '' };
  formError.value = null;
  showForm.value = true;
}

function openEdit(user: User): void {
  editTarget.value = user;
  form.value = {
    name: user.name,
    email: user.email,
    password: '',
    role: user.roles?.[0]?.name ?? '',
  };
  formError.value = null;
  showForm.value = true;
}

async function handleSubmit(): Promise<void> {
  saving.value = true;
  formError.value = null;
  try {
    const roles = form.value.role ? [form.value.role] : undefined;
    if (editTarget.value) {
      const payload: { name?: string; email?: string; password?: string; roles?: string[] } = {
        name: form.value.name,
        email: form.value.email,
        roles,
      };
      if (form.value.password) payload.password = form.value.password;
      await userService.update(editTarget.value.id, payload);
      notify.success('User updated.');
    } else {
      await userService.create({
        name: form.value.name,
        email: form.value.email,
        password: form.value.password,
        roles,
      });
      notify.success('User created.');
    }
    showForm.value = false;
    void load();
  } catch (e: unknown) {
    const err = e as { response?: { data?: { message?: string } } };
    formError.value = err.response?.data?.message ?? 'Failed to save user.';
  } finally {
    saving.value = false;
  }
}
</script>
