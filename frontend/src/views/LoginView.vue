<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import { toast } from '@/stores/toast'
import { AlertCircle, Loader2, Eye, EyeOff } from 'lucide-vue-next'

const auth = useAuthStore()
const router = useRouter()

const form = ref({
  email: '',
  password: '',
})
const showPassword = ref(false)
const error = ref('')
const loading = ref(false)

async function handleLogin() {
  error.value = ''
  loading.value = true
  try {
    await auth.login({ email: form.value.email, password: form.value.password })
    toast.success(`Selamat datang kembali, ${auth.user?.name || 'Pengguna'}!`, 'Login Berhasil')
    if (auth.isAdmin) {
      router.push({ name: 'admin-dashboard' })
    } else {
      router.push({ name: 'user-dashboard' })
    }
  } catch (e: any) {
    let msg =
      e.response?.data?.errors?.email?.[0] ||
      e.response?.data?.message ||
      (e.response?.status === 429 ? 'Terlalu banyak percobaan. Coba lagi beberapa saat.' : 'Email atau password salah.')

    if (form.value.password.length < 12 && msg.includes('password')) {
      msg = `${msg} (Catatan: Password harus minimal 12 karakter)`
    }

    error.value = msg
    toast.error(msg, 'Gagal Masuk')
  } finally {
    loading.value = false
  }
}

function handleGoogleLogin() {
  toast.info('Login dengan Google sedang dalam tahap integrasi.', 'Info')
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-[#F8F9FD] px-4 py-12 sm:px-6 lg:px-8 font-sans">
    <div class="w-full max-w-md bg-white rounded-3xl p-8 sm:p-10 shadow-sm border border-[#EDF1F7]">
      <!-- Header Left-Aligned matching screenshot -->
      <div class="text-left mb-8 space-y-2.5">
        <h1 class="text-2xl sm:text-[30px] font-extrabold text-[#111827] tracking-tight leading-snug">
          Welcome to PulsePoint !
        </h1>
        <p class="text-sm text-[#4B5563] leading-relaxed">
          Effortlessly gather insights with tools to create, distribute, and analyze surveys in real time.
        </p>
      </div>

      <!-- Error Alert Box -->
      <div
        v-if="error"
        class="mb-6 p-4 rounded-xl bg-[#FEF2F2] border border-[#FCA5A5]/60 text-xs text-[#DC2626] font-medium flex items-center gap-2.5"
      >
        <AlertCircle class="w-4 h-4 shrink-0 text-[#DC2626]" />
        <span>{{ error }}</span>
      </div>

      <!-- Login Form -->
      <form class="space-y-5" @submit.prevent="handleLogin">
        <!-- E - Mail Field -->
        <div class="text-left space-y-2">
          <label for="email" class="block text-sm font-bold text-[#111827]">
            Email
          </label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            required
            autocomplete="email"
            class="w-full h-[52px] px-4 rounded-2xl border border-[#E5E7EB] bg-white text-sm text-[#111827] placeholder-[#9CA3AF] focus:outline-none focus:border-[#8B95F6] focus:ring-3 focus:ring-[#8B95F6]/20 transition-all shadow-2xs"
          />
        </div>

        <!-- Passwords Field (No Forgot Link) -->
        <div class="text-left space-y-2">
          <label for="password" class="block text-sm font-bold text-[#111827]">
            Passwords
          </label>
          <div class="relative">
            <input
              id="password"
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              required
              autocomplete="current-password"
              class="w-full h-[52px] px-4 pr-11 rounded-2xl border border-[#E5E7EB] bg-white text-sm text-[#111827] placeholder-[#9CA3AF] focus:outline-none focus:border-[#8B95F6] focus:ring-3 focus:ring-[#8B95F6]/20 transition-all shadow-2xs"
            />
            <button
              type="button"
              class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[#9CA3AF] hover:text-[#111827] transition-colors p-1 rounded-md focus:outline-none cursor-pointer"
              :title="showPassword ? 'Sembunyikan password' : 'Lihat password'"
              tabindex="-1"
              @click="showPassword = !showPassword"
            >
              <EyeOff v-if="showPassword" class="h-4 w-4" />
              <Eye v-else class="h-4 w-4" />
            </button>
          </div>
        </div>

        <!-- Log in Button (Soft Indigo / Periwinkle matching screenshot) -->
        <button
          type="submit"
          class="w-full h-[52px] rounded-2xl bg-[#8B95F6] hover:bg-[#727EF4] text-white font-bold text-sm transition-all shadow-xs flex items-center justify-center cursor-pointer mt-2"
          :disabled="loading"
        >
          <Loader2 v-if="loading" class="mr-2 h-4 w-4 animate-spin" />
          <span>Log in</span>
        </button>

        
      </form>

      <!-- Footer: Did not have account ? Sign Up -->
      <div class="mt-8 text-center text-xs text-[#6B7280]">
        <span>Did not have account ? </span>
        <RouterLink to="/register" class="font-bold text-[#111827] hover:underline ml-1">
          Sign Up
        </RouterLink>
      </div>
    </div>
  </div>
</template>
