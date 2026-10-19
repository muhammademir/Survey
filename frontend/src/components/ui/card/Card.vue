<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <div
    :class="
      cn(
        'rounded-xl border border-border/60 bg-card text-card-foreground shadow-sm transition-all duration-200',
        props.class
      )
    "
  >
    <slot />
  </div>
</template>
