<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import { toast } from '@/stores/toast'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Alert, AlertDescription } from '@/components/ui/alert'
import {
  AlertCircle,
  Loader2,
  UserPlus,
  Sparkles,
  Clock,
  ArrowRight,
  RotateCcw,
  Eye,
  EyeOff,
} from 'lucide-vue-next'

const auth = useAuthStore()
const router = useRouter()

const showPassword = ref(false)
const showPasswordConfirm = ref(false)

const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  birth_date: '',
  phone: '',
})
const errors = ref<Record<string, string[]>>({})
const error = ref('')
const loading = ref(false)
const registerSuccess = ref(false)
const successMessage = ref('')

async function handleRegister() {
  error.value = ''
  errors.value = {}
  loading.value = true
  try {
    const res = await auth.register(form.value)
    registerSuccess.value = true
    successMessage.value =
      res?.message ||
      'Akun Anda telah berhasil didaftarkan dan sedang menunggu persetujuan (acc) dari administrator.'
    toast.success(
      'Pendaftaran berhasil! Akun Anda sedang menunggu persetujuan (acc) dari admin.',
      'Menunggu Persetujuan Admin',
    )
  } catch (e: any) {
    if (e.response?.data?.errors) {
      errors.value = e.response.data.errors
    }
    error.value = e.response?.status === 429
      ? 'Terlalu banyak percobaan. Coba lagi beberapa saat.'
      : (e.response?.data?.message || 'Registrasi gagal. Mohon periksa kembali formulir Anda.')
    toast.error(error.value, 'Registrasi Gagal')
  } finally {
    loading.value = false
  }
}

function resetForm() {
  form.value = {
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    birth_date: '',
    phone: '',
  }
  registerSuccess.value = false
  errors.value = {}
  error.value = ''
}
</script>

<template>
  <div class="relative min-h-screen flex items-center justify-center overflow-y-auto bg-background px-4 py-12 sm:px-6 lg:px-8">
    <div class="absolute -top-40 -right-40 w-96 h-96 bg-primary/20 rounded-full blur-3xl pointer-events-none" />
    <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-purple-600/15 rounded-full blur-3xl pointer-events-none" />

    <Card class="w-full max-w-2xl shadow-2xl border-border/80 bg-card/95 backdrop-blur-xl relative z-10 rounded-2xl overflow-hidden">
      <!-- SUCCESS STATE -->
      <template v-if="registerSuccess">
        <CardHeader class="flex flex-col items-center text-center px-8 pt-12 pb-6 sm:px-10 space-y-4">
          <div class="mx-auto w-16 h-16 rounded-2xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-400 shadow-lg shadow-amber-500/10">
            <Clock class="w-8 h-8 text-amber-400 animate-pulse" />
          </div>
          <div class="space-y-2 w-full">
            <CardTitle class="text-2xl sm:text-3xl font-extrabold tracking-tight text-foreground">
              Pendaftaran Berhasil!
            </CardTitle>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/15 text-amber-300 border border-amber-500/30">
              <span class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span>
              <span>Menunggu Persetujuan (ACC) Admin</span>
            </div>
            <p class="text-sm sm:text-base text-muted-foreground leading-relaxed max-w-lg mx-auto pt-2">
              {{ successMessage }}
            </p>
          </div>
        </CardHeader>

        <CardContent class="px-8 pb-8 sm:px-10 space-y-4">
          <div class="p-4 rounded-xl border border-border/80 bg-background/50 space-y-2 text-left">
            <div class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Detail Akun Terdaftar:</div>
            <div class="flex items-center justify-between text-sm">
              <span class="text-muted-foreground">Nama:</span>
              <span class="font-medium text-foreground">{{ form.name }}</span>
            </div>
            <div class="flex items-center justify-between text-sm">
              <span class="text-muted-foreground">Email:</span>
              <span class="font-medium text-foreground">{{ form.email }}</span>
            </div>
            <div class="flex items-center justify-between text-sm">
              <span class="text-muted-foreground">Status:</span>
              <span class="text-amber-400 font-semibold text-xs px-2 py-0.5 rounded bg-amber-500/10">Pending Approval</span>
            </div>
          </div>

          <div class="pt-2 flex flex-col sm:flex-row gap-3">
            <Button
              type="button"
              class="flex-1 h-12 text-base font-semibold shadow-lg shadow-primary/25 rounded-xl cursor-pointer"
              @click="router.push('/login')"
            >
              <span>Ke Halaman Masuk</span>
              <ArrowRight class="ml-2 h-4 w-4" />
            </Button>
            <Button
              type="button"
              variant="outline"
              class="h-12 text-sm font-medium rounded-xl"
              @click="resetForm"
            >
              <RotateCcw class="mr-2 h-4 w-4" />
              <span>Daftar Akun Lain</span>
            </Button>
          </div>
        </CardContent>
      </template>

      <!-- REGISTRATION FORM -->
      <template v-else>
        <CardHeader class="flex flex-col items-center text-center px-8 pt-10 pb-6 sm:px-10 space-y-3">
          <div class="mx-auto w-14 h-14 rounded-2xl bg-primary/10 border border-primary/25 flex items-center justify-center text-primary shadow-sm">
            <Sparkles class="w-7 h-7 text-primary" />
          </div>
          <div class="space-y-1.5 w-full">
            <CardTitle class="text-2xl sm:text-3xl font-extrabold tracking-tight text-foreground">
              Buat Akun Baru
            </CardTitle>
            <CardDescription class="text-sm sm:text-base text-muted-foreground leading-relaxed max-w-md mx-auto">
              Lengkapi data diri Anda. Akun baru akan divalidasi dan disetujui oleh Administrator sebelum dapat digunakan.
            </CardDescription>
          </div>
        </CardHeader>

        <CardContent class="px-8 pb-8 sm:px-10 space-y-6">
          <Alert v-if="error" variant="destructive" class="border-destructive/40 bg-destructive/15">
            <AlertCircle class="h-4 w-4" />
            <AlertDescription class="font-medium text-sm">
              {{ error }}
            </AlertDescription>
          </Alert>

          <form class="space-y-6" @submit.prevent="handleRegister">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <!-- Full Name (Full Width) -->
              <div class="space-y-2 md:col-span-2 text-left">
                <Label for="reg-name" class="text-sm font-semibold tracking-tight text-foreground">
                  Nama Lengkap <span class="text-destructive">*</span>
                </Label>
                <Input
                  id="reg-name"
                  v-model="form.name"
                  type="text"
                  placeholder="Contoh: Ahmad Faisal"
                  required
                  class="h-12 text-sm sm:text-base px-4 bg-background/60 border-input focus-visible:ring-primary"
                />
                <p v-if="errors.name" class="text-xs text-destructive font-medium">{{ errors.name[0] }}</p>
              </div>

              <!-- Email (Full Width) -->
              <div class="space-y-2 md:col-span-2 text-left">
                <Label for="reg-email" class="text-sm font-semibold tracking-tight text-foreground">
                  Alamat Email <span class="text-destructive">*</span>
                </Label>
                <Input
                  id="reg-email"
                  v-model="form.email"
                  type="email"
                  placeholder="email@contoh.com"
                  required
                  class="h-12 text-sm sm:text-base px-4 bg-background/60 border-input focus-visible:ring-primary"
                />
                <p v-if="errors.email" class="text-xs text-destructive font-medium">{{ errors.email[0] }}</p>
              </div>

              <!-- Birth Date -->
              <div class="space-y-2 text-left">
                <Label for="reg-birth" class="text-sm font-semibold tracking-tight text-foreground">
                  Tanggal Lahir
                </Label>
                <Input
                  id="reg-birth"
                  v-model="form.birth_date"
                  type="date"
                  class="h-12 text-sm sm:text-base px-4 bg-background/60 border-input focus-visible:ring-primary"
                />
                <p v-if="errors.birth_date" class="text-xs text-destructive font-medium">{{ errors.birth_date[0] }}</p>
              </div>

              <!-- Phone -->
              <div class="space-y-2 text-left">
                <Label for="reg-phone" class="text-sm font-semibold tracking-tight text-foreground">
                  Nomor HP / WhatsApp
                </Label>
                <Input
                  id="reg-phone"
                  v-model="form.phone"
                  type="tel"
                  placeholder="08xxxxxxxxxx"
                  class="h-12 text-sm sm:text-base px-4 bg-background/60 border-input focus-visible:ring-primary"
                />
                <p v-if="errors.phone" class="text-xs text-destructive font-medium">{{ errors.phone[0] }}</p>
              </div>

              <!-- Password -->
              <div class="space-y-2 text-left">
                <Label for="reg-pass" class="text-sm font-semibold tracking-tight text-foreground">
                  Password <span class="text-destructive">*</span>
                </Label>
                <div class="relative">
                  <Input
                    id="reg-pass"
                    v-model="form.password"
                    :type="showPassword ? 'text' : 'password'"
                    placeholder="Minimal 12 karakter"
                    required
                    class="h-12 text-sm sm:text-base px-4 pr-11 bg-background/60 border-input focus-visible:ring-primary"
                  />
                  <button
                    type="button"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors p-1 rounded-md focus:outline-none"
                    :title="showPassword ? 'Sembunyikan password' : 'Lihat password'"
                    tabindex="-1"
                    @click="showPassword = !showPassword"
                  >
                    <EyeOff v-if="showPassword" class="h-5 w-5" />
                    <Eye v-else class="h-5 w-5" />
                  </button>
                </div>
                <p v-if="errors.password" class="text-xs text-destructive font-medium">{{ errors.password[0] }}</p>
              </div>

              <!-- Password Confirm -->
              <div class="space-y-2 text-left">
                <Label for="reg-pass-confirm" class="text-sm font-semibold tracking-tight text-foreground">
                  Ulangi Password <span class="text-destructive">*</span>
                </Label>
                <div class="relative">
                  <Input
                    id="reg-pass-confirm"
                    v-model="form.password_confirmation"
                    :type="showPasswordConfirm ? 'text' : 'password'"
                    placeholder="Konfirmasi password"
                    required
                    class="h-12 text-sm sm:text-base px-4 pr-11 bg-background/60 border-input focus-visible:ring-primary"
                  />
                  <button
                    type="button"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors p-1 rounded-md focus:outline-none"
                    :title="showPasswordConfirm ? 'Sembunyikan password' : 'Lihat password'"
                    tabindex="-1"
                    @click="showPasswordConfirm = !showPasswordConfirm"
                  >
                    <EyeOff v-if="showPasswordConfirm" class="h-5 w-5" />
                    <Eye v-else class="h-5 w-5" />
                  </button>
                </div>
              </div>
            </div>

            <Button
              type="submit"
              class="w-full h-12 text-base font-semibold shadow-lg shadow-primary/25 rounded-xl cursor-pointer mt-4"
              :disabled="loading"
            >
              <Loader2 v-if="loading" class="mr-2 h-5 w-5 animate-spin" />
              <UserPlus v-else class="mr-2 h-5 w-5" />
              <span>Daftar Akun Sekarang</span>
            </Button>
          </form>
        </CardContent>

        <CardFooter class="flex justify-center text-center border-t border-border/60 bg-muted/20 px-8 py-5 sm:px-10">
          <p class="text-sm text-muted-foreground">
            Sudah memiliki akun terdaftar?
            <RouterLink to="/login" class="text-primary font-semibold hover:underline ml-1">
              Masuk disini
            </RouterLink>
          </p>
        </CardFooter>
      </template>
    </Card>
  </div>
</template>
