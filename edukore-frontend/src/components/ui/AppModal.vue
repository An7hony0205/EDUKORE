<script setup>
import { onMounted, onUnmounted } from 'vue'

const props = defineProps({
  modelValue: {
    type: Boolean,
    required: true
  },
  title: {
    type: String,
    required: true
  },
  maxWidth: {
    type: String,
    default: 'max-w-2xl'
  }
})

const emit = defineEmits(['update:modelValue', 'close'])

const close = () => {
  emit('update:modelValue', false)
  emit('close')
}

const onEscape = (e) => {
  if (e.key === 'Escape' && props.modelValue) {
    close()
  }
}

onMounted(() => {
  document.addEventListener('keydown', onEscape)
})

onUnmounted(() => {
  document.removeEventListener('keydown', onEscape)
})
</script>

<template>
  <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" @click="close"></div>
    
    <!-- Modal -->
    <div :class="['bg-brand-surface border border-brand-border rounded-2xl w-full z-10 overflow-hidden shadow-2xl flex flex-col max-h-[90vh]', maxWidth]">
      
      <!-- Header -->
      <div class="p-6 border-b border-brand-border flex items-center justify-between shrink-0">
        <h3 class="text-lg font-bold text-white">{{ title }}</h3>
        <button @click="close" class="text-slate-400 hover:text-white transition-colors">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
      
      <!-- Body -->
      <div class="p-6 overflow-y-auto flex-1">
        <slot></slot>
      </div>
      
      <!-- Footer -->
      <div v-if="$slots.footer" class="p-6 border-t border-brand-border flex justify-end gap-3 shrink-0 bg-brand-dark/30">
        <slot name="footer"></slot>
      </div>

    </div>
  </div>
</template>
