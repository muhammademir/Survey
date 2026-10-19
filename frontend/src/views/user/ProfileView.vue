<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Alert, AlertDescription } from '@/components/ui/alert'
import {
  User,
  Save,
  CheckCircle2,
  AlertCircle,
  Loader2,
  Shield,
  Calendar,
  Phone,
  Mail,
} from 'lucide-vue-next'

const auth = useAuthStore()

const form = ref({
  name: '',
  email: '',
  birth_date: '',
  phone: '',
})

const loading = ref(false)
const successMessage = ref('')
const errorMessage = ref('')
const errors = ref<Record<string, string[]>>({})

function populateForm() {
  if (auth.user) {
    form.value = {
      name: auth.user.name || '',
      email: auth.user.email || '',
      birth_date: auth.user.birth_date ? auth.user.birth_date.substring(0, 10) : '',
      phone: auth.user.phone || '',
    }
  }
}

async function handleUpdateProfile() {
  loading.value = true
  successMessage.value = ''
  errorMessage.value = ''
  errors.value = {}

  try {
    const res = await auth.updateProfile(form.value)
    successMessage.value = res.message || 'Profil Anda berhasil diperbarui!'
    populateForm()
  } catch (e: any) {
    if (e.response?.data?.errors) {
      errors.value = e.response.data.errors
    }
    errorMessage.value = e.response?.data?.message || 'Gagal memperbarui profil.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  populateForm()
})
</script>

<template>
  <div class="flex-1 space-y-6 p-8 pt-6 max-w-3xl">
    <!-- Header -->
    <div>
      <h2 class="text-3xl font-bold tracking-tight text-foreground">Profil Pengguna</h2>
      <p class="text-sm text-muted-foreground">
        Kelola informasi akun dan data profil Anda.
      </p>
    </div>

    <!-- Feedback Alerts -->
    <Alert v-if="successMessage" variant="success" class="border-emerald-500/50 bg-emerald-500/10">
      <CheckCircle2 class="h-4 w-4" />
      <AlertDescription class="font-medium text-emerald-400">
        {{ successMessage }}
      </AlertDescription>
    </Alert>

    <Alert v-if="errorMessage" variant="destructive">
      <AlertCircle class="h-4 w-4" />
      <AlertDescription>{{ errorMessage }}</AlertDescription>
    </Alert>

    <!-- User Header Card -->
    <Card class="border-border/70 shadow-sm bg-gradient-to-r from-card to-card/50">
      <CardContent class="p-6">
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
          <div class="w-16 h-16 rounded-full bg-primary/20 border-2 border-primary/40 flex items-center justify-center font-bold text-2xl text-primary shadow-sm shrink-0">
            {{ auth.user?.name?.charAt(0).toUpperCase() }}
          </div>
          <div class="space-y-1 flex-1">
            <h3 class="text-xl font-bold text-foreground">{{ auth.user?.name }}</h3>
            <p class="text-xs text-muted-foreground">{{ auth.user?.email }}</p>
            <div class="flex flex-wrap items-center gap-2 pt-1">
              <Badge variant="outline" class="text-[11px] font-mono">
                ID: USR-{{ auth.user?.id?.toString().padStart(4, '0') }}
              </Badge>
              <Badge variant="secondary" class="text-[11px]">
                Role: {{ auth.user?.role?.toUpperCase() }}
              </Badge>
              <Badge :variant="auth.user?.is_active ? 'success' : 'destructive'" class="text-[11px]">
                {{ auth.user?.is_active ? 'Status: Aktif' : 'Status: Nonaktif' }}
              </Badge>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Edit Form Card -->
    <Card class="border-border/70 shadow-sm">
      <CardHeader class="pb-4 border-b border-border/40">
        <CardTitle class="text-base flex items-center gap-2">
          <User class="w-4 h-4 text-primary" />
          <span>Edit Informasi Akun</span>
        </CardTitle>
        <CardDescription>
          Data ini digunakan untuk identifikasi data responden survey secara sah.
        </CardDescription>
      </CardHeader>

      <CardContent class="p-6">
        <form class="space-y-4" @submit.prevent="handleUpdateProfile">
          <!-- User ID (Read-only) -->
          <div class="space-y-1.5">
            <Label for="profile-userid">User ID (Sistem)</Label>
            <Input
              id="profile-userid"
              type="text"
              :value="`USR-${auth.user?.id?.toString().padStart(5, '0')}`"
              disabled
              class="bg-muted/40 text-muted-foreground font-mono cursor-not-allowed"
            />
            <p class="text-[11px] text-muted-foreground">User ID digenerate otomatis oleh sistem dan bersifat permanen.</p>
          </div>

          <!-- Nama Lengkap -->
          <div class="space-y-1.5">
            <Label for="profile-name">Nama Lengkap <span class="text-destructive">*</span></Label>
            <Input
              id="profile-name"
              v-model="form.name"
              type="text"
              required
              class="bg-background/50 border-input/80 focus-visible:ring-primary"
            />
            <p v-if="errors.name" class="text-xs text-destructive font-medium">{{ errors.name[0] }}</p>
          </div>

          <!-- Email -->
          <div class="space-y-1.5">
            <Label for="profile-email">Alamat Email <span class="text-destructive">*</span></Label>
            <Input
              id="profile-email"
              v-model="form.email"
              type="email"
              required
              class="bg-background/50 border-input/80 focus-visible:ring-primary"
            />
            <p v-if="errors.email" class="text-xs text-destructive font-medium">{{ errors.email[0] }}</p>
          </div>

          <!-- Tanggal Lahir & Phone -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <Label for="profile-birth">Tanggal Lahir</Label>
              <Input
                id="profile-birth"
                v-model="form.birth_date"
                type="date"
                class="bg-background/50 border-input/80 focus-visible:ring-primary"
              />
              <p v-if="errors.birth_date" class="text-xs text-destructive font-medium">{{ errors.birth_date[0] }}</p>
            </div>

            <div class="space-y-1.5">
              <Label for="profile-phone">Nomor HP</Label>
              <Input
                id="profile-phone"
                v-model="form.phone"
                type="tel"
                placeholder="08xxxxxxxxxx"
                class="bg-background/50 border-input/80 focus-visible:ring-primary"
              />
              <p v-if="errors.phone" class="text-xs text-destructive font-medium">{{ errors.phone[0] }}</p>
            </div>
          </div>

          <div class="flex justify-end pt-4 border-t border-border/40">
            <Button type="submit" class="gap-2 shadow-md shadow-primary/20 min-w-[160px]" :disabled="loading">
              <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
              <Save v-else class="w-4 h-4" />
              <span>Simpan Perubahan</span>
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  </div>
</template>
