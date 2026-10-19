<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<{
  defaultValue?: string | number
  modelValue?: string | number
  class?: HTMLAttributes['class']
}>()

const emits = defineEmits<{
  (e: 'update:modelValue', payload: string | number): void
}>()

function handleInput(event: Event) {
  const target = event.target as HTMLInputElement
  emits('update:modelValue', target.value)
}

function handleClick(event: MouseEvent) {
  const target = event.target as HTMLInputElement
  if (target && ['date', 'datetime-local', 'time', 'month', 'week'].includes(target.type)) {
    try {
      target.showPicker?.()
    } catch {
      // Ignore if not supported or already active
    }
  }
}
</script>

<template>
  <input
    :value="modelValue ?? defaultValue"
    :class="
      cn(
        'flex h-12 w-full rounded-xl border border-input/80 bg-background/60 px-4 py-2.5 text-sm text-foreground shadow-xs transition-all placeholder:text-muted-foreground/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:border-transparent disabled:cursor-not-allowed disabled:opacity-50',
        props.class
      )
    "
    @input="handleInput"
    @click="handleClick"
  />
</template>
