<template>
  <div class="multi-select" ref="containerRef">
    <label v-if="label" :for="id" class="select-label">
      {{ label }}
      <span v-if="required" class="required">*</span>
    </label>
    
    <div
      class="select-input"
      :class="{ 'is-open': isOpen, 'is-disabled': disabled }"
      @click="toggleDropdown"
    >
      <div class="selected-items">
        <span
          v-for="item in selectedItems"
          :key="item.value"
          class="selected-tag"
        >
          {{ item.label }}
          <button
            v-if="!disabled"
            type="button"
            @click.stop="removeItem(item.value)"
            class="tag-remove"
          >
            ✕
          </button>
        </span>
        <input
          v-if="searchable && isOpen"
          ref="searchInputRef"
          v-model="searchQuery"
          type="text"
          class="search-input"
          :placeholder="selectedItems.length === 0 ? placeholder : ''"
          @keydown.down.prevent="navigateOptions('down')"
          @keydown.up.prevent="navigateOptions('up')"
          @keydown.enter.prevent="selectHighlighted"
          @keydown.esc="closeDropdown"
        />
        <span v-if="selectedItems.length === 0 && !isOpen" class="placeholder">
          {{ placeholder }}
        </span>
      </div>
      <span class="dropdown-arrow">{{ isOpen ? '▲' : '▼' }}</span>
    </div>
    
    <div v-if="isOpen" class="select-dropdown">
      <div v-if="filteredOptions.length === 0" class="no-options">
        No options available
      </div>
      <div
        v-for="(option, index) in filteredOptions"
        :key="option.value"
        class="select-option"
        :class="{
          'is-selected': isSelected(option.value),
          'is-highlighted': index === highlightedIndex
        }"
        @click="toggleItem(option.value)"
        @mouseenter="highlightedIndex = index"
      >
        <input
          type="checkbox"
          :checked="isSelected(option.value)"
          class="option-checkbox"
          @click.stop
        />
        <span class="option-label">{{ option.label }}</span>
      </div>
    </div>
    
    <span v-if="error" class="select-error">{{ error }}</span>
    <span v-if="help" class="select-help">{{ help }}</span>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => []
  },
  options: {
    type: Array,
    required: true,
    default: () => []
  },
  id: {
    type: String,
    default: () => `multi-select-${Math.random().toString(36).substr(2, 9)}`
  },
  label: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: 'Select options...'
  },
  required: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  },
  searchable: {
    type: Boolean,
    default: true
  },
  error: {
    type: String,
    default: ''
  },
  help: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['update:modelValue', 'change']);

const containerRef = ref(null);
const searchInputRef = ref(null);
const isOpen = ref(false);
const searchQuery = ref('');
const highlightedIndex = ref(0);
const selectedValues = ref([...props.modelValue]);

const selectedItems = computed(() => {
  return props.options.filter(opt => selectedValues.value.includes(opt.value));
});

const filteredOptions = computed(() => {
  if (!props.searchable || !searchQuery.value) {
    return props.options;
  }
  
  const query = searchQuery.value.toLowerCase();
  return props.options.filter(opt =>
    opt.label.toLowerCase().includes(query)
  );
});

const isSelected = (value) => {
  return selectedValues.value.includes(value);
};

const toggleDropdown = () => {
  if (props.disabled) return;
  
  isOpen.value = !isOpen.value;
  
  if (isOpen.value) {
    nextTick(() => {
      searchInputRef.value?.focus();
    });
  } else {
    searchQuery.value = '';
  }
};

const closeDropdown = () => {
  isOpen.value = false;
  searchQuery.value = '';
};

const toggleItem = (value) => {
  const index = selectedValues.value.indexOf(value);
  
  if (index > -1) {
    selectedValues.value.splice(index, 1);
  } else {
    selectedValues.value.push(value);
  }
  
  emit('update:modelValue', [...selectedValues.value]);
  emit('change', [...selectedValues.value]);
};

const removeItem = (value) => {
  const index = selectedValues.value.indexOf(value);
  if (index > -1) {
    selectedValues.value.splice(index, 1);
    emit('update:modelValue', [...selectedValues.value]);
    emit('change', [...selectedValues.value]);
  }
};

const navigateOptions = (direction) => {
  if (direction === 'down') {
    highlightedIndex.value = Math.min(
      highlightedIndex.value + 1,
      filteredOptions.value.length - 1
    );
  } else {
    highlightedIndex.value = Math.max(highlightedIndex.value - 1, 0);
  }
};

const selectHighlighted = () => {
  const option = filteredOptions.value[highlightedIndex.value];
  if (option) {
    toggleItem(option.value);
  }
};

const handleClickOutside = (e) => {
  if (containerRef.value && !containerRef.value.contains(e.target)) {
    closeDropdown();
  }
};

watch(() => props.modelValue, (newValue) => {
  selectedValues.value = [...newValue];
});

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped>
.multi-select {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.select-label {
  font-weight: 500;
  color: #374151;
  font-size: 0.875rem;
}

.required {
  color: #ef4444;
  margin-left: 0.25rem;
}

.select-input {
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 2.5rem;
  padding: 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  background: white;
  cursor: pointer;
}

.select-input.is-open {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.select-input.is-disabled {
  background: #f3f4f6;
  cursor: not-allowed;
}

.selected-items {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
  flex: 1;
}

.selected-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.25rem 0.5rem;
  background: #3b82f6;
  color: white;
  border-radius: 0.25rem;
  font-size: 0.875rem;
}

.tag-remove {
  padding: 0;
  border: none;
  background: transparent;
  color: white;
  cursor: pointer;
  font-weight: bold;
}

.tag-remove:hover {
  color: #fee2e2;
}

.search-input {
  flex: 1;
  border: none;
  outline: none;
  font-size: 0.875rem;
  min-width: 100px;
}

.placeholder {
  color: #9ca3af;
  font-size: 0.875rem;
}

.dropdown-arrow {
  color: #6b7280;
  font-size: 0.75rem;
}

.select-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  margin-top: 0.25rem;
  max-height: 200px;
  overflow-y: auto;
  background: white;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  z-index: 50;
}

.no-options {
  padding: 0.75rem;
  text-align: center;
  color: #6b7280;
  font-size: 0.875rem;
}

.select-option {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  cursor: pointer;
  font-size: 0.875rem;
  color: #374151;
}

.select-option:hover,
.select-option.is-highlighted {
  background: #f3f4f6;
}

.select-option.is-selected {
  background: #eff6ff;
  color: #3b82f6;
}

.option-checkbox {
  cursor: pointer;
}

.option-label {
  flex: 1;
}

.select-error {
  font-size: 0.75rem;
  color: #ef4444;
}

.select-help {
  font-size: 0.75rem;
  color: #6b7280;
}
</style>
