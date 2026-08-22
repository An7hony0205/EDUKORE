<script setup>
defineProps({
  title: {
    type: String,
    required: true
  },
  description: {
    type: String,
    required: true
  },
  icon: {
    type: String,
    default: 'inbox'
  }
})
</script>

<template>
  <div class="flex flex-col items-center justify-center p-12 text-center border border-dashed border-brand-border rounded-2xl bg-white/5">
    <div class="w-16 h-16 rounded-full bg-brand-surface flex items-center justify-center text-slate-400 mb-4 shadow-inner">
      <slot name="icon">
        <svg v-if="icon === 'inbox'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
        <svg v-else-if="icon === 'search'" class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
      </slot>
    </div>
    <h3 class="text-lg font-bold text-white mb-2">{{ title }}</h3>
    <p class="text-slate-400 text-sm max-w-sm mb-6">{{ description }}</p>
    <slot name="action"></slot>
  </div>
</template>
