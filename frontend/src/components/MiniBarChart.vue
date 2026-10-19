<script setup lang="ts">
interface Props {
  heights?: number[] // array of heights in percent, e.g. [35, 60, 45, 75, 100]
  activeIdx?: number // which bar is highlighted with solid primary color (default last bar)
}

const props = withDefaults(defineProps<Props>(), {
  heights: () => [35, 60, 45, 75, 100],
  activeIdx: 4,
})
</script>

<template>
  <div class="flex items-end gap-1.5 h-10 px-1">
    <div
      v-for="(h, idx) in props.heights"
      :key="idx"
      class="w-2 rounded-full transition-all duration-300"
      :class="idx === props.activeIdx ? 'bg-[#4E53E9]' : 'bg-[#EEF1FD]'"
      :style="{ height: `${h}%` }"
    />
  </div>
</template>
