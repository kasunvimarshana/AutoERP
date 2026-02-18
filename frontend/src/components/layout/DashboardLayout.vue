<template>
  <div class="flex h-screen bg-gray-100">
    <!-- Sidebar -->
    <Sidebar
      :open="sidebarOpen"
      @close="toggleSidebar"
    />

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <!-- Header -->
      <Header @toggle-sidebar="toggleSidebar" />

      <!-- Page Content -->
      <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100">
        <div class="container mx-auto px-6 py-8">
          <RouterView />
        </div>
      </main>
    </div>

    <!-- Notifications -->
    <NotificationContainer />

    <!-- Modal -->
    <Modal
      v-if="modalOpen"
      @close="closeModal"
    >
      <component
        :is="modalComponent"
        v-bind="modalProps"
        @close="closeModal"
      />
    </Modal>

    <!-- Confirm Dialog -->
    <ConfirmDialog
      :is-open="confirmDialog.isOpen.value"
      :title="confirmDialog.dialogOptions.value.title"
      :message="confirmDialog.dialogOptions.value.message"
      :type="confirmDialog.dialogOptions.value.type"
      :confirm-text="confirmDialog.dialogOptions.value.confirmText"
      :cancel-text="confirmDialog.dialogOptions.value.cancelText"
      @confirm="confirmDialog.handleConfirm"
      @cancel="confirmDialog.handleCancel"
    />

    <!-- Keyboard Shortcuts Help -->
    <KeyboardShortcutsHelp
      :is-open="showKeyboardHelp"
      @close="showKeyboardHelp = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { RouterView } from 'vue-router';
import { useUiStore } from '@/stores/ui';
import { useConfirmDialog } from '@/composables/useConfirmDialog';
import { useKeyboardShortcuts } from '@/composables/useKeyboardShortcuts';
import Sidebar from './Sidebar.vue';
import Header from './Header.vue';
import NotificationContainer from '../common/NotificationContainer.vue';
import Modal from '../common/Modal.vue';
import ConfirmDialog from '../common/ConfirmDialog.vue';
import KeyboardShortcutsHelp from '../common/KeyboardShortcutsHelp.vue';

const uiStore = useUiStore();
const confirmDialog = useConfirmDialog();
const showKeyboardHelp = ref(false);

const sidebarOpen = computed(() => uiStore.sidebarOpen);
const modalOpen = computed(() => uiStore.modalOpen);
const modalComponent = computed(() => uiStore.modalComponent);
const modalProps = computed(() => uiStore.modalProps);

const toggleSidebar = () => {
  uiStore.toggleSidebar();
};

const closeModal = () => {
  uiStore.closeModal();
};

// Keyboard shortcuts
useKeyboardShortcuts([
  {
    key: '?',
    shift: true,
    description: 'Show keyboard shortcuts',
    callback: () => {
      showKeyboardHelp.value = true;
    },
  },
]);
</script>
