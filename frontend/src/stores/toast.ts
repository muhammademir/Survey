import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { ToastItem, ToastType } from '@/types'

export const useToastStore = defineStore('toast', () => {
  const toasts = ref<ToastItem[]>([])

  function remove(id: string) {
    const index = toasts.value.findIndex((t) => t.id === id)
    if (index !== -1) {
      toasts.value.splice(index, 1)
    }
  }

  function add(type: ToastType, message: string, title?: string, duration = 4500): string {
    const id = `${Date.now()}-${Math.random().toString(36).substring(2, 9)}`
    const toast: ToastItem = {
      id,
      type,
      message,
      title,
      duration,
    }

    toasts.value.push(toast)

    if (duration > 0) {
      setTimeout(() => {
        remove(id)
      }, duration)
    }

    return id
  }

  function success(message: string, title = 'Berhasil', duration = 4000) {
    return add('success', message, title, duration)
  }

  function error(message: string, title = 'Terjadi Kesalahan', duration = 5000) {
    return add('error', message, title, duration)
  }

  function warning(message: string, title = 'Peringatan', duration = 4500) {
    return add('warning', message, title, duration)
  }

  function info(message: string, title = 'Informasi', duration = 4000) {
    return add('info', message, title, duration)
  }

  function clear() {
    toasts.value = []
  }

  return {
    toasts,
    add,
    success,
    error,
    warning,
    info,
    remove,
    clear,
  }
})

// Shortcut helper yang dapat diimpor langsung: import { toast } from '@/stores/toast'
export const toast = {
  success: (message: string, title?: string, duration?: number) =>
    useToastStore().success(message, title, duration),
  error: (message: string, title?: string, duration?: number) =>
    useToastStore().error(message, title, duration),
  warning: (message: string, title?: string, duration?: number) =>
    useToastStore().warning(message, title, duration),
  info: (message: string, title?: string, duration?: number) =>
    useToastStore().info(message, title, duration),
  remove: (id: string) => useToastStore().remove(id),
  clear: () => useToastStore().clear(),
}
