<script setup lang="ts">
import { useToastStore } from '@/stores/toast'
import {
  CheckCircle2,
  AlertCircle,
  AlertTriangle,
  Info,
  X,
} from 'lucide-vue-next'

const toastStore = useToastStore()

function getIcon(type: string) {
  switch (type) {
    case 'success':
      return CheckCircle2
    case 'error':
      return AlertCircle
    case 'warning':
      return AlertTriangle
    case 'info':
    default:
      return Info
  }
}

function getStyles(type: string) {
  switch (type) {
    case 'success':
      return {
        wrapper: 'border-emerald-500/30 bg-emerald-950/80 text-emerald-100 shadow-emerald-950/50',
        icon: 'text-emerald-400 bg-emerald-500/20',
        progress: 'bg-emerald-400',
        title: 'text-emerald-200',
      }
    case 'error':
      return {
        wrapper: 'border-rose-500/30 bg-rose-950/85 text-rose-100 shadow-rose-950/50',
        icon: 'text-rose-400 bg-rose-500/20',
        progress: 'bg-rose-400',
        title: 'text-rose-200',
      }
    case 'warning':
      return {
        wrapper: 'border-amber-500/30 bg-amber-950/85 text-amber-100 shadow-amber-950/50',
        icon: 'text-amber-400 bg-amber-500/20',
        progress: 'bg-amber-400',
        title: 'text-amber-200',
      }
    case 'info':
    default:
      return {
        wrapper: 'border-sky-500/30 bg-slate-900/90 text-sky-100 shadow-sky-950/50',
        icon: 'text-sky-400 bg-sky-500/20',
        progress: 'bg-sky-400',
        title: 'text-sky-200',
      }
  }
}
</script>

<template>
  <div
    class="fixed top-4 right-4 z-[9999] flex flex-col gap-2.5 max-w-sm sm:max-w-md w-full pointer-events-none px-3 sm:px-0"
    aria-live="polite"
  >
    <TransitionGroup
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="transform translate-x-8 opacity-0 scale-95"
      enter-to-class="transform translate-x-0 opacity-100 scale-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="transform translate-x-0 opacity-100 scale-100"
      leave-to-class="transform translate-x-8 opacity-0 scale-95"
      move-class="transition duration-300 ease-out"
    >
      <div
        v-for="toast in toastStore.toasts"
        :key="toast.id"
        class="pointer-events-auto relative overflow-hidden rounded-xl border backdrop-blur-xl shadow-xl p-4 transition-all"
        :class="getStyles(toast.type).wrapper"
      >
        <div class="flex items-start gap-3">
          <!-- Icon -->
          <div
            class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center"
            :class="getStyles(toast.type).icon"
          >
            <component :is="getIcon(toast.type)" class="w-4 h-4" />
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0 pt-0.5">
            <h4
              v-if="toast.title"
              class="text-xs sm:text-sm font-semibold tracking-wide mb-0.5"
              :class="getStyles(toast.type).title"
            >
              {{ toast.title }}
            </h4>
            <p class="text-xs sm:text-sm font-normal text-muted-foreground/90 text-foreground/90 leading-relaxed break-words">
              {{ toast.message }}
            </p>
          </div>

          <!-- Close button -->
          <button
            type="button"
            class="flex-shrink-0 text-muted-foreground hover:text-foreground transition-colors p-1 rounded-md hover:bg-white/10"
            @click="toastStore.remove(toast.id)"
          >
            <X class="w-4 h-4" />
          </button>
        </div>

        <!-- Optional progress animation if duration exists -->
        <div
          v-if="toast.duration && toast.duration > 0"
          class="absolute bottom-0 left-0 right-0 h-0.5 opacity-60 overflow-hidden"
        >
          <div
            class="h-full origin-left animate-toast-progress"
            :class="getStyles(toast.type).progress"
            :style="{ animationDuration: `${toast.duration}ms` }"
          />
        </div>
      </div>
    </TransitionGroup>
  </div>
</template>

<style scoped>
@keyframes toastProgress {
  from {
    transform: scaleX(1);
  }
  to {
    transform: scaleX(0);
  }
}

.animate-toast-progress {
  animation-name: toastProgress;
  animation-timing-function: linear;
  animation-fill-mode: forwards;
}
</style>
