import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User, LoginForm, RegisterForm } from '@/types'
import * as authService from '@/services/authService'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const loading = ref(false)
  const initialized = ref(false)

  const isLoggedIn = computed(() => !!user.value)
  const isAdmin = computed(() => user.value?.role === 'admin')

  /**
   * Inisialisasi auth state — cek session dengan GET /me.
   * Dipanggil 1x saat app mount.
   */
  async function init() {
    if (initialized.value) return

    loading.value = true
    try {
      user.value = await authService.fetchCurrentUser()
    } catch {
      // Session tidak valid / belum login
      user.value = null
    } finally {
      loading.value = false
      initialized.value = true
    }
  }

  async function login(credentialsOrEmail: LoginForm | string, maybePassword?: string) {
    loading.value = true
    try {
      const credentials: LoginForm =
        typeof credentialsOrEmail === 'string'
          ? { email: credentialsOrEmail, password: maybePassword || '' }
          : credentialsOrEmail
      const data = await authService.login(credentials)
      user.value = data.user
      return data
    } finally {
      loading.value = false
    }
  }

  async function register(form: RegisterForm) {
    loading.value = true
    try {
      const data = await authService.register(form)
      // Pendaftaran menunggu persetujuan admin, jangan loginkan user langsung
      user.value = null
      return data
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try {
      await authService.logout()
    } catch {
      // ignore — session mungkin sudah expired
    }
    user.value = null
  }

  async function fetchMe() {
    try {
      user.value = await authService.fetchCurrentUser()
    } catch {
      // token/session invalid
      user.value = null
    }
  }

  async function updateProfile(data: Partial<User>) {
    const result = await authService.updateProfile(data)
    user.value = result.user
    return result
  }

  return {
    user,
    loading,
    initialized,
    isLoggedIn,
    isAdmin,
    init,
    login,
    register,
    logout,
    fetchMe,
    updateProfile,
  }
})
