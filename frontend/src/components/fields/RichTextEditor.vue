<template>
  <div class="rich-text-editor">
    <label v-if="label" :for="id" class="editor-label">
      {{ label }}
      <span v-if="required" class="required">*</span>
    </label>
    
    <div class="editor-toolbar">
      <button
        v-for="action in toolbarActions"
        :key="action.command"
        type="button"
        @click="execCommand(action.command, action.value)"
        :class="['toolbar-btn', { active: isActive(action.command) }]"
        :title="action.title"
        :disabled="disabled"
      >
        {{ action.icon }}
      </button>
    </div>
    
    <div
      ref="editorRef"
      :id="id"
      class="editor-content"
      :contenteditable="!disabled"
      @input="handleInput"
      @focus="handleFocus"
      @blur="handleBlur"
      v-html="content"
    />
    
    <div v-if="showCharCount" class="editor-footer">
      <span class="char-count">{{ charCount }} / {{ maxLength || '∞' }}</span>
    </div>
    
    <span v-if="error" class="editor-error">{{ error }}</span>
    <span v-if="help" class="editor-help">{{ help }}</span>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  id: {
    type: String,
    default: () => `rich-editor-${Math.random().toString(36).substr(2, 9)}`
  },
  label: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: 'Enter text...'
  },
  required: {
    type: Boolean,
    default: false
  },
  disabled: {
    type: Boolean,
    default: false
  },
  maxLength: {
    type: Number,
    default: null
  },
  showCharCount: {
    type: Boolean,
    default: false
  },
  error: {
    type: String,
    default: ''
  },
  help: {
    type: String,
    default: ''
  },
  toolbar: {
    type: Array,
    default: () => ['bold', 'italic', 'underline', 'list', 'link']
  }
});

const emit = defineEmits(['update:modelValue', 'focus', 'blur']);

const editorRef = ref(null);
const content = ref(props.modelValue);
const isFocused = ref(false);

const toolbarActions = computed(() => {
  const actions = {
    bold: { command: 'bold', icon: 'B', title: 'Bold' },
    italic: { command: 'italic', icon: 'I', title: 'Italic' },
    underline: { command: 'underline', icon: 'U', title: 'Underline' },
    list: { command: 'insertUnorderedList', icon: '•', title: 'Bullet List' },
    link: { command: 'createLink', icon: '🔗', title: 'Insert Link' },
  };
  
  return props.toolbar.map(key => actions[key]).filter(Boolean);
});

const charCount = computed(() => {
  const text = editorRef.value?.innerText || '';
  return text.length;
});

const handleInput = (e) => {
  const html = e.target.innerHTML;
  content.value = html;
  emit('update:modelValue', html);
};

const handleFocus = () => {
  isFocused.value = true;
  emit('focus');
};

const handleBlur = () => {
  isFocused.value = false;
  emit('blur');
};

const execCommand = (command, value = null) => {
  if (command === 'createLink') {
    const url = prompt('Enter URL:');
    if (url) {
      document.execCommand(command, false, url);
    }
  } else {
    document.execCommand(command, false, value);
  }
  editorRef.value?.focus();
};

const isActive = (command) => {
  return document.queryCommandState(command);
};

watch(() => props.modelValue, (newValue) => {
  if (newValue !== content.value && !isFocused.value) {
    content.value = newValue;
  }
});

onMounted(() => {
  if (props.placeholder && !content.value && editorRef.value) {
    editorRef.value.setAttribute('data-placeholder', props.placeholder);
  }
});
</script>

<style scoped>
.rich-text-editor {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.editor-label {
  font-weight: 500;
  color: #374151;
  font-size: 0.875rem;
}

.required {
  color: #ef4444;
  margin-left: 0.25rem;
}

.editor-toolbar {
  display: flex;
  gap: 0.25rem;
  padding: 0.5rem;
  background: #f9fafb;
  border: 1px solid #d1d5db;
  border-bottom: none;
  border-radius: 0.375rem 0.375rem 0 0;
}

.toolbar-btn {
  padding: 0.25rem 0.5rem;
  border: none;
  background: transparent;
  border-radius: 0.25rem;
  cursor: pointer;
  font-weight: 600;
  font-size: 0.875rem;
  color: #374151;
}

.toolbar-btn:hover:not(:disabled) {
  background: #e5e7eb;
}

.toolbar-btn.active {
  background: #3b82f6;
  color: white;
}

.editor-content {
  min-height: 150px;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0 0 0.375rem 0.375rem;
  font-size: 0.875rem;
  line-height: 1.5;
  color: #374151;
  background: white;
}

.editor-content:focus {
  outline: none;
  border-color: #3b82f6;
}

.editor-content:empty:before {
  content: attr(data-placeholder);
  color: #9ca3af;
}

.editor-error {
  font-size: 0.75rem;
  color: #ef4444;
}

.editor-help {
  font-size: 0.75rem;
  color: #6b7280;
}
</style>
