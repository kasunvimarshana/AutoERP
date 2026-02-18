<template>
  <div class="file-upload">
    <label v-if="label" class="upload-label">
      {{ label }}
      <span v-if="required" class="required">*</span>
    </label>
    
    <div
      class="upload-area"
      :class="{ 'drag-over': isDragging, 'has-files': files.length > 0 }"
      @drop.prevent="handleDrop"
      @dragover.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      @click="triggerFileInput"
    >
      <input
        ref="fileInputRef"
        type="file"
        :accept="accept"
        :multiple="multiple"
        :disabled="disabled"
        class="file-input"
        @change="handleFileSelect"
      />
      
      <div class="upload-prompt">
        <span class="upload-icon">📁</span>
        <p class="upload-text">
          {{ files.length > 0 ? `${files.length} file(s) selected` : 'Drop files here or click to browse' }}
        </p>
        <p v-if="accept" class="upload-hint">{{ accept }}</p>
      </div>
    </div>
    
    <div v-if="files.length > 0" class="file-list">
      <div
        v-for="(file, index) in files"
        :key="index"
        class="file-item"
      >
        <span class="file-icon">📄</span>
        <span class="file-name">{{ file.name }}</span>
        <span class="file-size">{{ formatFileSize(file.size) }}</span>
        <button
          v-if="!disabled"
          type="button"
          @click.stop="removeFile(index)"
          class="file-remove"
          title="Remove file"
        >
          ✕
        </button>
      </div>
    </div>
    
    <span v-if="error" class="upload-error">{{ error }}</span>
    <span v-if="help" class="upload-help">{{ help }}</span>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  modelValue: {
    type: [Array, File, null],
    default: () => []
  },
  label: {
    type: String,
    default: ''
  },
  accept: {
    type: String,
    default: ''
  },
  multiple: {
    type: Boolean,
    default: false
  },
  required: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  },
  maxSize: {
    type: Number,
    default: 10 * 1024 * 1024 // 10MB default
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

const fileInputRef = ref(null);
const files = ref([]);
const isDragging = ref(false);

const triggerFileInput = () => {
  if (!props.disabled) {
    fileInputRef.value?.click();
  }
};

const handleFileSelect = (e) => {
  const selectedFiles = Array.from(e.target.files);
  addFiles(selectedFiles);
};

const handleDrop = (e) => {
  isDragging.value = false;
  if (props.disabled) return;
  
  const droppedFiles = Array.from(e.dataTransfer.files);
  addFiles(droppedFiles);
};

const addFiles = (newFiles) => {
  const validFiles = newFiles.filter(file => {
    if (file.size > props.maxSize) {
      console.warn(`File ${file.name} exceeds max size`);
      return false;
    }
    return true;
  });
  
  if (props.multiple) {
    files.value = [...files.value, ...validFiles];
  } else {
    files.value = validFiles.slice(0, 1);
  }
  
  updateModelValue();
};

const removeFile = (index) => {
  files.value.splice(index, 1);
  updateModelValue();
};

const updateModelValue = () => {
  const value = props.multiple ? files.value : files.value[0] || null;
  emit('update:modelValue', value);
  emit('change', value);
};

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};

watch(() => props.modelValue, (newValue) => {
  if (newValue !== null && newValue !== undefined) {
    files.value = Array.isArray(newValue) ? newValue : [newValue];
  }
});
</script>

<style scoped>
.file-upload {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.upload-label {
  font-weight: 500;
  color: #374151;
  font-size: 0.875rem;
}

.required {
  color: #ef4444;
  margin-left: 0.25rem;
}

.upload-area {
  border: 2px dashed #d1d5db;
  border-radius: 0.375rem;
  padding: 2rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.15s;
  background: #f9fafb;
}

.upload-area:hover {
  border-color: #3b82f6;
  background: #eff6ff;
}

.upload-area.drag-over {
  border-color: #3b82f6;
  background: #dbeafe;
}

.file-input {
  display: none;
}

.upload-prompt {
  pointer-events: none;
}

.upload-icon {
  font-size: 3rem;
  display: block;
  margin-bottom: 0.5rem;
}

.upload-text {
  font-size: 0.875rem;
  color: #374151;
  margin: 0;
}

.upload-hint {
  font-size: 0.75rem;
  color: #6b7280;
  margin: 0.25rem 0 0;
}

.file-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.file-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem;
  background: #f9fafb;
  border-radius: 0.375rem;
  border: 1px solid #e5e7eb;
}

.file-icon {
  font-size: 1.25rem;
}

.file-name {
  flex: 1;
  font-size: 0.875rem;
  color: #374151;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.file-size {
  font-size: 0.75rem;
  color: #6b7280;
}

.file-remove {
  padding: 0.25rem 0.5rem;
  border: none;
  background: transparent;
  cursor: pointer;
  color: #ef4444;
  font-weight: bold;
}

.file-remove:hover {
  background: #fee2e2;
  border-radius: 0.25rem;
}

.upload-error {
  font-size: 0.75rem;
  color: #ef4444;
}

.upload-help {
  font-size: 0.75rem;
  color: #6b7280;
}
</style>
