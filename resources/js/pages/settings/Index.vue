<template>
  <AppLayout>
    <div class="space-y-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Settings</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Configure tenant-level settings for your workspace.</p>
      </div>

      <div
        v-if="setting.error"
        class="rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-400"
        role="alert"
      >
        {{ setting.error }}
      </div>

      <div
        v-if="saveMessage"
        class="rounded-lg bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-400"
        role="status"
      >
        {{ saveMessage }}
      </div>

      <div v-if="setting.loading" class="text-sm text-gray-400 dark:text-gray-500 py-8 text-center">Loading settings…</div>

      <div v-else class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 divide-y divide-gray-100 dark:divide-gray-800">
        <div
          v-for="s in setting.settings"
          :key="s.key"
          class="flex items-center gap-4 px-5 py-4"
        >
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ formatKey(s.key) }}</p>
            <p v-if="s.description" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ s.description }}</p>
          </div>
          <div class="flex items-center gap-2">
            <input
              v-model="edits[s.key]"
              type="text"
              :aria-label="`Setting value for ${s.key}`"
              class="rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-1.5 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 w-48"
            />
            <button
              type="button"
              :disabled="setting.saving || edits[s.key] === s.value"
              class="rounded-lg bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-medium px-3 py-1.5 transition-colors"
              @click="save(s.key)"
            >
              Save
            </button>
          </div>
        </div>

        <div v-if="setting.settings.length === 0" class="px-5 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
          No settings found.
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { useSettingStore } from '@/stores/setting';

const setting = useSettingStore();
const edits = ref({});
const saveMessage = ref('');
let saveTimer = null;

watch(
    () => setting.settings,
    (list) => {
        list.forEach(s => { edits.value[s.key] = s.value; });
    },
    { immediate: true },
);

function formatKey(key) {
    return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

async function save(key) {
    try {
        await setting.updateSetting(key, edits.value[key]);
        saveMessage.value = `"${formatKey(key)}" saved successfully.`;
        clearTimeout(saveTimer);
        saveTimer = setTimeout(() => { saveMessage.value = ''; }, 3000);
    } catch {
        // error shown via setting.saveError
    }
}

onMounted(() => setting.fetchSettings());
</script>
