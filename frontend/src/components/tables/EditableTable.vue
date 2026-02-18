<template>
  <div class="editable-table">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700">
          <tr>
            <th
              v-for="column in columns"
              :key="column.key"
              class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider"
              :style="{ width: column.width }"
            >
              {{ column.label }}
            </th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
          <tr
            v-for="(row, rowIndex) in localData"
            :key="getRowKey(row)"
            class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
          >
            <!-- Editable Columns -->
            <td
              v-for="column in columns"
              :key="column.key"
              class="px-4 py-3 text-sm"
            >
              <!-- Edit Mode -->
              <div
                v-if="isEditing(row) && isColumnEditable(column)"
                class="min-w-[150px]"
              >
                <!-- Text Input -->
                <input
                  v-if="column.type === 'text' || column.type === 'number'"
                  v-model="editingData[column.key]"
                  :type="column.type === 'number' ? 'number' : 'text'"
                  class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                  @keyup.enter="saveRow(row)"
                  @keyup.escape="cancelEdit(row)"
                >

                <!-- Select -->
                <select
                  v-else-if="column.type === 'select' && column.options"
                  v-model="editingData[column.key]"
                  class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                >
                  <option
                    v-for="option in column.options"
                    :key="option.value"
                    :value="option.value"
                  >
                    {{ option.label }}
                  </option>
                </select>

                <!-- Date -->
                <input
                  v-else-if="column.type === 'date'"
                  v-model="editingData[column.key]"
                  type="date"
                  class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                >

                <!-- Boolean (Checkbox) -->
                <input
                  v-else-if="column.type === 'boolean'"
                  v-model="editingData[column.key]"
                  type="checkbox"
                  class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                >

                <!-- Default (Read-only in edit) -->
                <span
                  v-else
                  class="text-gray-900 dark:text-gray-100"
                >
                  {{ getCellValue(row, column) }}
                </span>
              </div>

              <!-- View Mode -->
              <span
                v-else
                class="text-gray-900 dark:text-gray-100"
              >
                {{ getCellValue(row, column) }}
              </span>
            </td>

            <!-- Actions Column -->
            <td class="px-4 py-3 text-sm text-right space-x-2 whitespace-nowrap">
              <!-- Edit Mode Actions -->
              <template v-if="isEditing(row)">
                <button
                  :disabled="saving"
                  class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                  @click="saveRow(row)"
                >
                  Save
                </button>
                <button
                  :disabled="saving"
                  class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300"
                  @click="cancelEdit(row)"
                >
                  Cancel
                </button>
              </template>

              <!-- View Mode Actions -->
              <template v-else>
                <button
                  class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300"
                  @click="startEdit(row)"
                >
                  Edit
                </button>
                <button
                  v-if="allowDelete"
                  class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                  @click="deleteRow(row)"
                >
                  Delete
                </button>
              </template>
            </td>
          </tr>

          <!-- Add New Row -->
          <tr
            v-if="allowAdd && isAddingNew"
            class="bg-blue-50 dark:bg-blue-900/20"
          >
            <td
              v-for="column in columns"
              :key="column.key"
              class="px-4 py-3 text-sm"
            >
              <div
                v-if="isColumnEditable(column)"
                class="min-w-[150px]"
              >
                <input
                  v-if="column.type === 'text' || column.type === 'number'"
                  v-model="newRowData[column.key]"
                  :type="column.type === 'number' ? 'number' : 'text'"
                  :placeholder="column.label"
                  class="w-full px-2 py-1 border border-gray-300 dark:border-gray-600 rounded focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                  @keyup.enter="addNewRow"
                  @keyup.escape="cancelAddNew"
                >
              </div>
            </td>
            <td class="px-4 py-3 text-sm text-right space-x-2">
              <button
                :disabled="saving"
                class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                @click="addNewRow"
              >
                Add
              </button>
              <button
                :disabled="saving"
                class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-300"
                @click="cancelAddNew"
              >
                Cancel
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Add Button -->
    <div
      v-if="allowAdd && !isAddingNew"
      class="mt-4"
    >
      <button
        class="px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500"
        @click="startAddNew"
      >
        Add Row
      </button>
    </div>

    <!-- Empty State -->
    <div
      v-if="!localData || localData.length === 0"
      class="text-center py-8 text-gray-500"
    >
      No data available
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import type { TableColumnMetadata } from '@/types/metadata';
import { formatDate, formatNumber, formatCurrency } from '@/utils/formatters';

interface EditableColumn extends TableColumnMetadata {
  editable?: boolean;
  options?: Array<{ label: string; value: any }>;
}

interface Props {
  data: any[];
  columns: EditableColumn[];
  keyField?: string;
  allowAdd?: boolean;
  allowDelete?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  keyField: 'id',
  allowAdd: true,
  allowDelete: true,
});

const emit = defineEmits<{
  save: [row: any];
  delete: [row: any];
  add: [row: any];
}>();

const localData = ref([...props.data]);
const editingRow = ref<any | null>(null);
const editingData = ref<Record<string, any>>({});
const newRowData = ref<Record<string, any>>({});
const isAddingNew = ref(false);
const saving = ref(false);

const getRowKey = (row: any) => {
  return row[props.keyField];
};

const isEditing = (row: any) => {
  return editingRow.value && getRowKey(editingRow.value) === getRowKey(row);
};

const isColumnEditable = (column: EditableColumn) => {
  return column.editable !== false;
};

const getCellValue = (row: Record<string, any>, column: EditableColumn) => {
  const value = row[column.key];

  if (value === null || value === undefined) {
    return '-';
  }

  switch (column.type) {
    case 'date':
      return formatDate(value);
    case 'number':
      return formatNumber(value);
    case 'boolean':
      return value ? 'Yes' : 'No';
    default:
      return String(value);
  }
};

const startEdit = (row: any) => {
  editingRow.value = row;
  editingData.value = { ...row };
};

const cancelEdit = (row: any) => {
  editingRow.value = null;
  editingData.value = {};
};

const saveRow = async (row: any) => {
  saving.value = true;
  try {
    const updatedRow = { ...row, ...editingData.value };
    emit('save', updatedRow);
    
    // Update local data
    const index = localData.value.findIndex(r => getRowKey(r) === getRowKey(row));
    if (index !== -1) {
      localData.value[index] = updatedRow;
    }
    
    editingRow.value = null;
    editingData.value = {};
  } catch (error) {
    console.error('Failed to save row:', error);
  } finally {
    saving.value = false;
  }
};

const deleteRow = (row: any) => {
  if (confirm('Are you sure you want to delete this row?')) {
    emit('delete', row);
    localData.value = localData.value.filter(r => getRowKey(r) !== getRowKey(row));
  }
};

const startAddNew = () => {
  isAddingNew.value = true;
  newRowData.value = {};
};

const cancelAddNew = () => {
  isAddingNew.value = false;
  newRowData.value = {};
};

const addNewRow = async () => {
  if (Object.keys(newRowData.value).length === 0) {
    return;
  }

  saving.value = true;
  try {
    emit('add', newRowData.value);
    localData.value.push({ ...newRowData.value });
    
    isAddingNew.value = false;
    newRowData.value = {};
  } catch (error) {
    console.error('Failed to add row:', error);
  } finally {
    saving.value = false;
  }
};

defineExpose({
  startEdit,
  cancelEdit,
  saveRow,
});
</script>
