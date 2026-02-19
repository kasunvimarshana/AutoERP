<template>
  <div>
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Notification Center</h1>
        <p class="mt-1 text-sm text-gray-500">Manage system and user notifications</p>
      </div>
      <BaseButton v-if="hasUnread" variant="primary" @click="markAllAsRead">
        <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        Mark All as Read
      </BaseButton>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-blue-100 rounded-lg">
              <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Total</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-orange-100 rounded-lg">
              <svg class="h-6 w-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Unread</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.unread }}</p>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-green-100 rounded-lg">
              <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Read Today</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.readToday }}</p>
          </div>
        </div>
      </BaseCard>

      <BaseCard>
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <div class="p-3 bg-red-100 rounded-lg">
              <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <p class="text-sm font-medium text-gray-500">Failed</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.failed }}</p>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- Filters -->
    <BaseCard class="mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <BaseInput
          v-model="search"
          placeholder="Search notifications..."
          type="search"
        />
        <BaseSelect
          v-model="typeFilter"
          :options="typeOptions"
          placeholder="Filter by type"
        />
        <BaseSelect
          v-model="statusFilter"
          :options="statusOptions"
          placeholder="Filter by status"
        />
        <BaseSelect
          v-model="priorityFilter"
          :options="priorityOptions"
          placeholder="Filter by priority"
        />
      </div>
    </BaseCard>

    <!-- Notifications Table -->
    <BaseCard>
      <BaseTable
        :columns="columns"
        :data="filteredNotifications"
        :loading="loading"
        :actions="tableActions"
        @sort="handleSort"
        @action:view="viewNotification"
        @action:mark_read="markAsReadAction"
        @action:retry="retryFailedAction"
        @action:delete="deleteNotificationAction"
      >
        <template #cell-title="{ value }">
          <span class="font-medium text-gray-900">{{ value }}</span>
        </template>

        <template #cell-type="{ value }">
          <BaseBadge :variant="getTypeVariant(value)">
            {{ formatType(value) }}
          </BaseBadge>
        </template>

        <template #cell-recipient="{ value }">
          <span class="text-sm text-gray-600">{{ value || '-' }}</span>
        </template>

        <template #cell-sent_at="{ value }">
          <span class="text-sm text-gray-600">
            {{ value ? formatDate(value) : '-' }}
          </span>
        </template>

        <template #cell-status="{ value }">
          <BaseBadge :variant="getStatusVariant(value)">
            {{ formatStatus(value) }}
          </BaseBadge>
        </template>

        <template #cell-priority="{ value }">
          <BaseBadge :variant="getPriorityVariant(value)">
            {{ formatPriority(value) }}
          </BaseBadge>
        </template>
      </BaseTable>

      <div v-if="pagination.totalPages > 1" class="mt-4">
        <BasePagination
          :current-page="pagination.currentPage"
          :total-pages="pagination.totalPages"
          :total="pagination.total"
          :per-page="pagination.perPage"
          @page-change="handlePageChange"
        />
      </div>
    </BaseCard>

    <!-- Notification Detail Modal -->
    <BaseModal :show="detailModal.isOpen" title="Notification Details" size="lg" @close="detailModal.close">
      <div v-if="selectedNotification" class="space-y-4">
        <!-- Alert Message -->
        <div v-if="selectedNotification.content" class="rounded-lg bg-blue-50 border border-blue-200 p-4">
          <p class="text-sm text-blue-900">{{ selectedNotification.content }}</p>
        </div>

        <!-- Details Grid -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Title</label>
            <p class="mt-1 text-sm text-gray-900">{{ selectedNotification.title }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Type</label>
            <div class="mt-1">
              <BaseBadge :variant="getTypeVariant(selectedNotification.type)">
                {{ formatType(selectedNotification.type) }}
              </BaseBadge>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Status</label>
            <div class="mt-1">
              <BaseBadge :variant="getStatusVariant(selectedNotification.status)">
                {{ formatStatus(selectedNotification.status) }}
              </BaseBadge>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Priority</label>
            <div class="mt-1">
              <BaseBadge :variant="getPriorityVariant(selectedNotification.priority)">
                {{ formatPriority(selectedNotification.priority) }}
              </BaseBadge>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Recipient</label>
            <p class="mt-1 text-sm text-gray-900">{{ selectedNotification.recipient || '-' }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Sent At</label>
            <p class="mt-1 text-sm text-gray-900">
              {{ selectedNotification.sent_at ? formatDate(selectedNotification.sent_at) : '-' }}
            </p>
          </div>

          <div v-if="selectedNotification.read_at" class="col-span-2">
            <label class="block text-sm font-medium text-gray-700">Read At</label>
            <p class="mt-1 text-sm text-gray-900">{{ formatDate(selectedNotification.read_at) }}</p>
          </div>
        </div>

        <div class="mt-6 flex justify-end space-x-3">
          <BaseButton type="button" variant="secondary" @click="detailModal.close">
            Close
          </BaseButton>
        </div>
      </div>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useNotificationStore } from '../stores/notificationStore';
import { useModal } from '@/composables/useModal';
import { useTable } from '@/composables/useTable';
import { usePagination } from '@/composables/usePagination';
import { useNotifications } from '@/composables/useNotifications';
import BaseCard from '@/components/layout/BaseCard.vue';
import BaseButton from '@/components/common/BaseButton.vue';
import BaseInput from '@/components/common/BaseInput.vue';
import BaseSelect from '@/components/common/BaseSelect.vue';
import BaseTable from '@/components/data/BaseTable.vue';
import BasePagination from '@/components/data/BasePagination.vue';
import BaseBadge from '@/components/common/BaseBadge.vue';
import BaseModal from '@/components/layout/BaseModal.vue';

const notificationStore = useNotificationStore();
const detailModal = useModal();
const { showSuccess, showError } = useNotifications();

const loading = ref(false);
const search = ref('');
const typeFilter = ref('');
const statusFilter = ref('');
const priorityFilter = ref('');
const selectedNotification = ref(null);

const stats = computed(() => {
  const notifications = notificationStore.notifications || [];
  const today = new Date().toDateString();
  return {
    total: notifications.length,
    unread: notifications.filter(n => !n.read_at).length,
    readToday: notifications.filter(n => n.read_at && new Date(n.read_at).toDateString() === today).length,
    failed: notifications.filter(n => n.status === 'failed').length,
  };
});

const hasUnread = computed(() => stats.value.unread > 0);

const typeOptions = [
  { label: 'All Types', value: '' },
  { label: 'Email', value: 'email' },
  { label: 'SMS', value: 'sms' },
  { label: 'Push', value: 'push' },
  { label: 'In-App', value: 'in_app' },
];

const statusOptions = [
  { label: 'All Statuses', value: '' },
  { label: 'Sent', value: 'sent' },
  { label: 'Failed', value: 'failed' },
  { label: 'Pending', value: 'pending' },
];

const priorityOptions = [
  { label: 'All Priorities', value: '' },
  { label: 'High', value: 'high' },
  { label: 'Normal', value: 'normal' },
  { label: 'Low', value: 'low' },
];

const columns = [
  { key: 'title', label: 'Title', sortable: true },
  { key: 'type', label: 'Type', sortable: true },
  { key: 'recipient', label: 'Recipient', sortable: true },
  { key: 'sent_at', label: 'Sent At', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'priority', label: 'Priority', sortable: true },
];

const tableActions = (row) => {
  const actions = [
    { key: 'view', label: 'View', icon: 'eye' },
  ];
  
  if (!row.read_at) {
    actions.push({ key: 'mark_read', label: 'Mark as Read', icon: 'check' });
  }
  
  if (row.status === 'failed') {
    actions.push({ key: 'retry', label: 'Retry', icon: 'repeat' });
  }
  
  actions.push({ key: 'delete', label: 'Delete', icon: 'trash', variant: 'danger' });
  
  return actions;
};

const { sortedData, handleSort } = useTable(computed(() => notificationStore.notifications || []));

const filteredNotifications = computed(() => {
  let data = sortedData.value;

  if (search.value) {
    const searchLower = search.value.toLowerCase();
    data = data.filter(notification =>
      notification.title?.toLowerCase().includes(searchLower) ||
      notification.content?.toLowerCase().includes(searchLower) ||
      notification.recipient?.toLowerCase().includes(searchLower)
    );
  }

  if (typeFilter.value) {
    data = data.filter(notification => notification.type === typeFilter.value);
  }

  if (statusFilter.value) {
    data = data.filter(notification => notification.status === statusFilter.value);
  }

  if (priorityFilter.value) {
    data = data.filter(notification => notification.priority === priorityFilter.value);
  }

  return data;
});

const pagination = usePagination(filteredNotifications, 10);

const handlePageChange = (page) => {
  pagination.currentPage.value = page;
};

const fetchNotifications = async () => {
  loading.value = true;
  try {
    await notificationStore.fetchNotifications();
  } catch (error) {
    showError('Failed to load notifications');
  } finally {
    loading.value = false;
  }
};

const markAllAsRead = async () => {
  if (!confirm('Mark all notifications as read?')) return;

  try {
    await notificationStore.markAllAsRead();
    showSuccess('All notifications marked as read');
    await fetchNotifications();
  } catch (error) {
    showError('Failed to mark all as read');
  }
};

const markAsReadAction = async (notification) => {
  if (notification.read_at) {
    showError('Notification is already read');
    return;
  }

  try {
    await notificationStore.markAsRead(notification.id);
    showSuccess('Notification marked as read');
    await fetchNotifications();
  } catch (error) {
    showError('Failed to mark as read');
  }
};

const retryFailedAction = async (notification) => {
  if (notification.status !== 'failed') {
    showError('Only failed notifications can be retried');
    return;
  }

  try {
    await notificationStore.retryNotification(notification.id);
    showSuccess('Notification retry initiated');
    await fetchNotifications();
  } catch (error) {
    showError('Failed to retry notification');
  }
};

const deleteNotificationAction = async (notification) => {
  if (!confirm(`Are you sure you want to delete this notification?`)) return;

  try {
    await notificationStore.deleteNotification(notification.id);
    showSuccess('Notification deleted successfully');
    await fetchNotifications();
  } catch (error) {
    showError('Failed to delete notification');
  }
};

const viewNotification = (notification) => {
  selectedNotification.value = notification;
  detailModal.open();
};

const getTypeVariant = (type) => {
  const variants = {
    email: 'info',
    sms: 'primary',
    push: 'success',
    in_app: 'warning',
  };
  return variants[type] || 'secondary';
};

const getStatusVariant = (status) => {
  const variants = {
    sent: 'success',
    failed: 'danger',
    pending: 'warning',
  };
  return variants[status] || 'secondary';
};

const getPriorityVariant = (priority) => {
  const variants = {
    high: 'danger',
    normal: 'info',
    low: 'secondary',
  };
  return variants[priority] || 'secondary';
};

const formatType = (type) => {
  const map = {
    email: 'Email',
    sms: 'SMS',
    push: 'Push',
    in_app: 'In-App',
  };
  return map[type] || type;
};

const formatStatus = (status) => {
  return status.charAt(0).toUpperCase() + status.slice(1);
};

const formatPriority = (priority) => {
  return priority.charAt(0).toUpperCase() + priority.slice(1);
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

onMounted(() => {
  fetchNotifications();
});
</script>
