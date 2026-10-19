<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { toast } from '@/stores/toast'
import * as userService from '@/services/userService'
import type { User, CreateUserForm, UpdateUserForm, UserStats } from '@/types'
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card'
import {
  Table,
  TableHeader,
  TableHead,
  TableBody,
  TableRow,
  TableCell,
} from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Search,
  Users,
  UserCheck,
  UserX,
  ChevronLeft,
  ChevronRight,
  Shield,
  ShieldPlus,
  User as UserIcon,
  CheckCircle,
  XCircle,
  Clock,
  Pencil,
  Trash2,
  X,
  AlertTriangle,
  Loader2,
  Check,
  Filter,
  Eye,
  EyeOff,
} from 'lucide-vue-next'

const authStore = useAuthStore()

const showCreatePassword = ref(false)
const showCreatePasswordConfirm = ref(false)
const showEditPassword = ref(false)
const showEditPasswordConfirm = ref(false)

const users = ref<User[]>([])
const stats = ref<UserStats>({
  total: 0,
  admins: 0,
  users: 0,
  pending: 0,
  active: 0,
})
const pagination = ref({ current_page: 1, last_page: 1, total: 0, per_page: 15 })
const search = ref('')
const activeTab = ref<'all' | 'admin' | 'pending' | 'user'>('all')
const loading = ref(true)

// ===== Modal States =====
const isCreateAdminOpen = ref(false)
const createLoading = ref(false)
const createErrors = ref<Record<string, string[]>>({})
const createForm = reactive<CreateUserForm>({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'admin',
  phone: '',
  birth_date: '',
  is_active: true,
})

const isEditOpen = ref(false)
const editLoading = ref(false)
const editErrors = ref<Record<string, string[]>>({})
const editingUserId = ref<number | null>(null)
const editForm = reactive<UpdateUserForm>({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'user',
  phone: '',
  birth_date: '',
  is_active: true,
  approval_status: 'approved',
})

const isDeleteOpen = ref(false)
const deleteLoading = ref(false)
const deletingUser = ref<User | null>(null)

// ===== Fetch Data =====
async function fetchUsers(page = 1) {
  loading.value = true
  try {
    const params: any = { page }
    if (search.value.trim()) params.search = search.value.trim()

    if (activeTab.value === 'admin') {
      params.role = 'admin'
    } else if (activeTab.value === 'pending') {
      params.approval_status = 'pending'
    } else if (activeTab.value === 'user') {
      params.role = 'user'
    }

    const res = await userService.getUsers(params)
    users.value = res.data
    pagination.value = {
      current_page: res.current_page,
      last_page: res.last_page,
      total: res.total,
      per_page: res.per_page,
    }
    if (res.stats) {
      stats.value = res.stats
    }
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal memuat data pengguna.')
  } finally {
    loading.value = false
  }
}

function handleTabChange(tab: 'all' | 'admin' | 'pending' | 'user') {
  activeTab.value = tab
  fetchUsers(1)
}

function handleSearch() {
  fetchUsers(1)
}

function resetSearch() {
  search.value = ''
  fetchUsers(1)
}

// ===== Actions: Approval (ACC / Tolak) =====
async function handleApprove(user: User) {
  try {
    const res = await userService.approveUser(user.id)
    user.approval_status = 'approved'
    user.is_active = true
    toast.success(res.message || `Akun ${user.name} berhasil disetujui (ACC).`)
    fetchUsers(pagination.value.current_page)
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal menyetujui akun pengguna.')
  }
}

async function handleReject(user: User) {
  if (!confirm(`Tolak pendaftaran akun untuk ${user.name}?`)) return
  try {
    const res = await userService.rejectUser(user.id)
    user.approval_status = 'rejected'
    user.is_active = false
    toast.info(res.message || `Pendaftaran akun ${user.name} telah ditolak.`)
    fetchUsers(pagination.value.current_page)
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal menolak akun pengguna.')
  }
}

// ===== Actions: Toggle Status =====
async function handleToggleStatus(user: User) {
  try {
    const res = await userService.toggleUserStatus(user.id)
    user.is_active = res.user.is_active
    toast.success(res.message)
    if (stats.value) {
      stats.value.active += user.is_active ? 1 : -1
    }
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal mengubah status akun.')
  }
}

// ===== Modal: Create Admin Account =====
function openCreateAdminModal() {
  createForm.name = ''
  createForm.email = ''
  createForm.password = ''
  createForm.password_confirmation = ''
  createForm.role = 'admin'
  createForm.phone = ''
  createForm.birth_date = ''
  createForm.is_active = true
  createErrors.value = {}
  showCreatePassword.value = false
  showCreatePasswordConfirm.value = false
  isCreateAdminOpen.value = true
}

async function submitCreateAdmin() {
  createLoading.value = true
  createErrors.value = {}
  try {
    const res = await userService.createUser(createForm)
    toast.success(res.message || 'Akun administrator baru berhasil dibuat!')
    isCreateAdminOpen.value = false
    fetchUsers(1)
  } catch (e: any) {
    if (e.response?.data?.errors) {
      createErrors.value = e.response.data.errors
    }
    toast.error(e.response?.data?.message || 'Gagal menambahkan akun admin.')
  } finally {
    createLoading.value = false
  }
}

// ===== Modal: Edit User =====
function openEditModal(user: User) {
  editingUserId.value = user.id
  editForm.name = user.name
  editForm.email = user.email
  editForm.password = ''
  editForm.password_confirmation = ''
  editForm.role = user.role as 'admin' | 'user'
  editForm.phone = user.phone || ''
  editForm.birth_date = user.birth_date ? user.birth_date.split('T')[0] : ''
  editForm.is_active = user.is_active
  editForm.approval_status = user.approval_status
  editErrors.value = {}
  showEditPassword.value = false
  showEditPasswordConfirm.value = false
  isEditOpen.value = true
}

async function submitEdit() {
  if (!editingUserId.value) return
  editLoading.value = true
  editErrors.value = {}
  try {
    const payload: UpdateUserForm = {
      name: editForm.name,
      email: editForm.email,
      role: editForm.role,
      phone: editForm.phone || undefined,
      birth_date: editForm.birth_date || undefined,
      is_active: editForm.is_active,
      approval_status: editForm.approval_status,
    }
    if (editForm.password && editForm.password.trim()) {
      payload.password = editForm.password.trim()
      payload.password_confirmation = editForm.password_confirmation?.trim()
    }

    const res = await userService.updateUser(editingUserId.value, payload)
    toast.success(res.message || 'Data akun berhasil diperbarui!')
    isEditOpen.value = false
    fetchUsers(pagination.value.current_page)
  } catch (e: any) {
    if (e.response?.data?.errors) {
      editErrors.value = e.response.data.errors
    }
    toast.error(e.response?.data?.message || 'Gagal memperbarui data akun.')
  } finally {
    editLoading.value = false
  }
}

// ===== Modal: Delete User =====
function openDeleteModal(user: User) {
  deletingUser.value = user
  isDeleteOpen.value = true
}

async function submitDelete() {
  if (!deletingUser.value) return
  deleteLoading.value = true
  try {
    const res = await userService.deleteUser(deletingUser.value.id)
    toast.success(res.message || 'Akun berhasil dihapus.')
    isDeleteOpen.value = false
    deletingUser.value = null
    fetchUsers(pagination.value.current_page)
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal menghapus akun.')
  } finally {
    deleteLoading.value = false
  }
}

onMounted(() => fetchUsers())
</script>

<template>
  <div class="flex-1 space-y-6 p-6 md:p-8 pt-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-foreground flex items-center gap-2.5">
          <span>Kelola Pengguna & Admin</span>
          <span
            v-if="stats.pending > 0"
            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-500/20 text-amber-300 border border-amber-500/30 animate-pulse"
          >
            <Clock class="w-3 h-3" />
            {{ stats.pending }} Menunggu ACC
          </span>
        </h2>
        <p class="text-sm text-muted-foreground mt-1">
          Daftar akun, persetujuan pendaftaran anggota kuesioner (ACC), dan manajemen akun administrator.
        </p>
      </div>

      <!-- Action Button: Tambah Akun Admin -->
      <Button
        type="button"
        class="gap-2 shadow-lg shadow-primary/20 rounded-xl cursor-pointer self-start sm:self-auto"
        @click="openCreateAdminModal"
      >
        <ShieldPlus class="h-4 w-4" />
        <span>Tambah Akun Admin</span>
      </Button>
    </div>

    <!-- Quick Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <!-- Total Pengguna -->
      <Card class="border-border/70 shadow-sm bg-card/60 backdrop-blur-sm">
        <CardContent class="p-4 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary">
            <Users class="w-5 h-5" />
          </div>
          <div>
            <p class="text-xs font-medium text-muted-foreground">Total Pengguna</p>
            <p class="text-xl font-bold text-foreground">{{ stats.total }}</p>
          </div>
        </CardContent>
      </Card>

      <!-- Total Admin -->
      <Card class="border-border/70 shadow-sm bg-card/60 backdrop-blur-sm">
        <CardContent class="p-4 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400">
            <Shield class="w-5 h-5" />
          </div>
          <div>
            <p class="text-xs font-medium text-muted-foreground">Administrator</p>
            <p class="text-xl font-bold text-foreground">{{ stats.admins }}</p>
          </div>
        </CardContent>
      </Card>

      <!-- Menunggu Persetujuan (Clickable to switch tab) -->
      <Card
        class="border-border/70 shadow-sm bg-card/60 backdrop-blur-sm cursor-pointer transition-all hover:border-amber-500/40"
        :class="{ 'ring-2 ring-amber-500/40 bg-amber-500/5': activeTab === 'pending' }"
        @click="handleTabChange('pending')"
      >
        <CardContent class="p-4 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-400">
            <Clock class="w-5 h-5" />
          </div>
          <div>
            <p class="text-xs font-medium text-muted-foreground">Menunggu ACC</p>
            <p class="text-xl font-bold text-amber-400">{{ stats.pending }}</p>
          </div>
        </CardContent>
      </Card>

      <!-- Pengguna Aktif -->
      <Card class="border-border/70 shadow-sm bg-card/60 backdrop-blur-sm">
        <CardContent class="p-4 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
            <UserCheck class="w-5 h-5" />
          </div>
          <div>
            <p class="text-xs font-medium text-muted-foreground">Akun Aktif</p>
            <p class="text-xl font-bold text-foreground">{{ stats.active }}</p>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Filter Tabs & Search Card -->
    <Card class="border-border/70 shadow-sm">
      <CardContent class="p-4 space-y-4">
        <!-- Tabs -->
        <div class="flex flex-wrap items-center gap-2 border-b border-border/50 pb-3">
          <Button
            type="button"
            size="sm"
            :variant="activeTab === 'all' ? 'default' : 'ghost'"
            class="rounded-lg text-xs gap-1.5 h-8"
            @click="handleTabChange('all')"
          >
            <Users class="h-3.5 w-3.5" />
            <span>Semua ({{ stats.total }})</span>
          </Button>

          <Button
            type="button"
            size="sm"
            :variant="activeTab === 'pending' ? 'default' : 'ghost'"
            class="rounded-lg text-xs gap-1.5 h-8"
            :class="activeTab === 'pending' ? 'bg-amber-600 text-white hover:bg-amber-700' : 'text-amber-400 hover:bg-amber-500/10'"
            @click="handleTabChange('pending')"
          >
            <Clock class="h-3.5 w-3.5" />
            <span>Menunggu ACC ({{ stats.pending }})</span>
          </Button>

          <Button
            type="button"
            size="sm"
            :variant="activeTab === 'admin' ? 'default' : 'ghost'"
            class="rounded-lg text-xs gap-1.5 h-8"
            @click="handleTabChange('admin')"
          >
            <Shield class="h-3.5 w-3.5" />
            <span>Admin ({{ stats.admins }})</span>
          </Button>

          <Button
            type="button"
            size="sm"
            :variant="activeTab === 'user' ? 'default' : 'ghost'"
            class="rounded-lg text-xs gap-1.5 h-8"
            @click="handleTabChange('user')"
          >
            <UserIcon class="h-3.5 w-3.5" />
            <span>Pengguna Biasa ({{ stats.users }})</span>
          </Button>
        </div>

        <!-- Search Bar -->
        <form class="flex items-center gap-3" @submit.prevent="handleSearch">
          <div class="relative flex-1">
            <Search class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
            <Input
              v-model="search"
              type="text"
              placeholder="Cari berdasarkan nama, email, atau nomor HP..."
              class="pl-9 bg-background/50 border-input/80 focus-visible:ring-primary h-10 text-sm"
            />
          </div>
          <Button type="submit" variant="default" class="gap-1.5 shadow-sm h-10 px-4">
            <Search class="h-4 w-4" />
            <span>Cari</span>
          </Button>
          <Button
            v-if="search"
            type="button"
            variant="ghost"
            size="sm"
            class="h-10 text-xs"
            @click="resetSearch"
          >
            Reset
          </Button>
        </form>
      </CardContent>
    </Card>

    <!-- Table Card -->
    <Card class="border-border/70 shadow-sm overflow-hidden">
      <div v-if="loading" class="flex justify-center items-center py-20">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
      </div>

      <template v-else>
        <div class="overflow-x-auto">
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead class="w-[70px]">ID</TableHead>
                <TableHead>Nama Lengkap</TableHead>
                <TableHead>Email</TableHead>
                <TableHead>Nomor HP</TableHead>
                <TableHead>Peran</TableHead>
                <TableHead>Persetujuan (ACC)</TableHead>
                <TableHead>Status</TableHead>
                <TableHead class="text-right">Aksi</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="user in users" :key="user.id">
                <TableCell class="font-mono text-xs text-muted-foreground">
                  #{{ user.id.toString().padStart(4, '0') }}
                </TableCell>

                <!-- Nama -->
                <TableCell class="font-medium text-foreground">
                  <div class="flex items-center gap-2.5">
                    <div
                      class="w-8 h-8 rounded-full flex items-center justify-center font-semibold text-xs border"
                      :class="user.role === 'admin'
                        ? 'bg-purple-500/15 border-purple-500/30 text-purple-300'
                        : 'bg-primary/10 border-primary/20 text-primary'"
                    >
                      {{ user.name.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                      <div class="flex items-center gap-1.5">
                        <span class="font-semibold">{{ user.name }}</span>
                        <span
                          v-if="user.id === authStore.user?.id"
                          class="text-[10px] bg-primary/20 text-primary font-bold px-1.5 py-0.2 rounded"
                        >
                          Anda
                        </span>
                      </div>
                      <span class="text-[11px] text-muted-foreground block">
                        {{ user.birth_date ? new Date(user.birth_date).toLocaleDateString('id-ID') : 'Tgl lahir: —' }}
                      </span>
                    </div>
                  </div>
                </TableCell>

                <!-- Email -->
                <TableCell class="text-muted-foreground text-xs sm:text-sm font-mono">
                  {{ user.email }}
                </TableCell>

                <!-- Telepon -->
                <TableCell class="text-muted-foreground text-xs">
                  {{ user.phone || '—' }}
                </TableCell>

                <!-- Peran -->
                <TableCell>
                  <Badge
                    :variant="user.role === 'admin' ? 'default' : 'secondary'"
                    class="gap-1 text-xs"
                    :class="user.role === 'admin' ? 'bg-purple-600/30 text-purple-200 border-purple-500/40' : ''"
                  >
                    <Shield v-if="user.role === 'admin'" class="w-3 h-3 text-purple-400" />
                    <UserIcon v-else class="w-3 h-3" />
                    <span>{{ user.role.toUpperCase() }}</span>
                  </Badge>
                </TableCell>

                <!-- Persetujuan Status (Pending / Approved / Rejected) -->
                <TableCell>
                  <!-- Pending -->
                  <div
                    v-if="user.approval_status === 'pending'"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-500/15 text-amber-300 border border-amber-500/30"
                  >
                    <Clock class="w-3 h-3 text-amber-400 animate-pulse" />
                    <span>Menunggu ACC</span>
                  </div>

                  <!-- Approved -->
                  <div
                    v-else-if="user.approval_status === 'approved'"
                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-500/15 text-emerald-300 border border-emerald-500/30"
                  >
                    <CheckCircle class="w-3 h-3 text-emerald-400" />
                    <span>Disetujui</span>
                  </div>

                  <!-- Rejected -->
                  <div
                    v-else-if="user.approval_status === 'rejected'"
                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-500/15 text-rose-300 border border-rose-500/30"
                  >
                    <XCircle class="w-3 h-3 text-rose-400" />
                    <span>Ditolak</span>
                  </div>
                </TableCell>

                <!-- Status Aktif / Nonaktif -->
                <TableCell>
                  <Badge
                    :variant="user.is_active ? 'success' : 'destructive'"
                    class="text-xs font-medium"
                  >
                    {{ user.is_active ? 'Aktif' : 'Nonaktif' }}
                  </Badge>
                </TableCell>

                <!-- Aksi -->
                <TableCell class="text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    <!-- ACC (Setujui) & Tolak Buttons untuk User Pending -->
                    <template v-if="user.approval_status === 'pending'">
                      <Button
                        type="button"
                        size="sm"
                        class="h-7 px-2.5 text-xs font-semibold gap-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md shadow-sm"
                        title="Setujui dan Aktifkan Akun"
                        @click="handleApprove(user)"
                      >
                        <Check class="h-3.5 w-3.5" />
                        <span>ACC</span>
                      </Button>

                      <Button
                        type="button"
                        variant="destructive"
                        size="sm"
                        class="h-7 px-2 text-xs gap-1 rounded-md"
                        title="Tolak Pendaftaran"
                        @click="handleReject(user)"
                      >
                        <X class="h-3.5 w-3.5" />
                        <span>Tolak</span>
                      </Button>
                    </template>

                    <!-- Edit User -->
                    <Button
                      type="button"
                      variant="ghost"
                      size="sm"
                      class="h-8 w-8 p-0 text-muted-foreground hover:text-foreground hover:bg-muted"
                      title="Edit Akun"
                      @click="openEditModal(user)"
                    >
                      <Pencil class="h-3.5 w-3.5" />
                    </Button>

                    <!-- Toggle Status (Hanya untuk non-self) -->
                    <Button
                      v-if="user.id !== authStore.user?.id"
                      type="button"
                      variant="ghost"
                      size="sm"
                      class="h-8 w-8 p-0"
                      :class="user.is_active ? 'text-amber-400 hover:text-amber-300 hover:bg-amber-500/10' : 'text-emerald-400 hover:text-emerald-300 hover:bg-emerald-500/10'"
                      :title="user.is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun'"
                      @click="handleToggleStatus(user)"
                    >
                      <UserX v-if="user.is_active" class="h-3.5 w-3.5" />
                      <UserCheck v-else class="h-3.5 w-3.5" />
                    </Button>

                    <!-- Delete User (Hanya jika bukan akun sendiri) -->
                    <Button
                      v-if="user.id !== authStore.user?.id"
                      type="button"
                      variant="ghost"
                      size="sm"
                      class="h-8 w-8 p-0 text-rose-400 hover:text-rose-300 hover:bg-rose-500/10"
                      title="Hapus Akun"
                      @click="openDeleteModal(user)"
                    >
                      <Trash2 class="h-3.5 w-3.5" />
                    </Button>
                  </div>
                </TableCell>
              </TableRow>

              <!-- Empty state -->
              <TableRow v-if="users.length === 0">
                <TableCell colspan="8" class="text-center py-16 text-muted-foreground">
                  <div class="flex flex-col items-center justify-center space-y-2">
                    <Users class="h-10 w-10 text-muted-foreground/40 mb-1" />
                    <p class="font-medium text-foreground">Tidak ada data pengguna yang ditemukan</p>
                    <p class="text-xs text-muted-foreground">
                      {{ search ? 'Coba gunakan kata kunci pencarian yang lain.' : 'Belum ada pengguna dalam kategori ini.' }}
                    </p>
                  </div>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="flex items-center justify-between p-4 border-t border-border/50 bg-muted/10">
          <div class="text-xs text-muted-foreground">
            Halaman <span class="font-medium text-foreground">{{ pagination.current_page }}</span> dari <span class="font-medium text-foreground">{{ pagination.last_page }}</span>
            ({{ pagination.total }} total)
          </div>
          <div class="flex items-center gap-2">
            <Button
              variant="outline"
              size="sm"
              class="gap-1 text-xs h-8"
              :disabled="pagination.current_page === 1"
              @click="fetchUsers(pagination.current_page - 1)"
            >
              <ChevronLeft class="h-3.5 w-3.5" />
              <span>Sebelumnya</span>
            </Button>
            <Button
              variant="outline"
              size="sm"
              class="gap-1 text-xs h-8"
              :disabled="pagination.current_page === pagination.last_page"
              @click="fetchUsers(pagination.current_page + 1)"
            >
              <span>Berikutnya</span>
              <ChevronRight class="h-3.5 w-3.5" />
            </Button>
          </div>
        </div>
      </template>
    </Card>

    <!-- ============================================================== -->
    <!-- MODAL: TAMBAH AKUN ADMINISTRATOR                              -->
    <!-- ============================================================== -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-if="isCreateAdminOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
      >
        <div
          class="relative w-full max-w-lg rounded-2xl border border-border bg-card p-6 shadow-2xl space-y-5"
          @click.stop
        >
          <!-- Header -->
          <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-purple-500/15 border border-purple-500/30 flex items-center justify-center text-purple-400">
                <ShieldPlus class="w-5 h-5" />
              </div>
              <div>
                <h3 class="text-lg font-bold text-foreground">Tambah Akun Administrator</h3>
                <p class="text-xs text-muted-foreground">Buat akun admin baru dengan hak akses penuh.</p>
              </div>
            </div>
            <button
              type="button"
              class="text-muted-foreground hover:text-foreground p-1 rounded-md"
              @click="isCreateAdminOpen = false"
            >
              <X class="w-5 h-5" />
            </button>
          </div>

          <!-- Form -->
          <form class="space-y-4" @submit.prevent="submitCreateAdmin">
            <!-- Nama -->
            <div class="space-y-1.5 text-left">
              <Label class="text-xs font-semibold">Nama Lengkap <span class="text-destructive">*</span></Label>
              <Input
                v-model="createForm.name"
                type="text"
                placeholder="Contoh: Admin Operasional"
                required
                class="h-10 text-sm bg-background/50"
              />
              <p v-if="createErrors.name" class="text-xs text-destructive">{{ createErrors.name[0] }}</p>
            </div>

            <!-- Email -->
            <div class="space-y-1.5 text-left">
              <Label class="text-xs font-semibold">Email Admin <span class="text-destructive">*</span></Label>
              <Input
                v-model="createForm.email"
                type="email"
                placeholder="admin@perusahaan.com"
                required
                class="h-10 text-sm bg-background/50"
              />
              <p v-if="createErrors.email" class="text-xs text-destructive">{{ createErrors.email[0] }}</p>
            </div>

            <!-- Password & Confirmation -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div class="space-y-1.5 text-left">
                <Label class="text-xs font-semibold">Password <span class="text-destructive">*</span></Label>
                <div class="relative">
                  <Input
                    v-model="createForm.password"
                    :type="showCreatePassword ? 'text' : 'password'"
                    placeholder="Min. 12 karakter"
                    required
                    class="h-10 text-sm bg-background/50 pr-10"
                  />
                  <button
                    type="button"
                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors p-1 rounded focus:outline-none"
                    :title="showCreatePassword ? 'Sembunyikan password' : 'Lihat password'"
                    tabindex="-1"
                    @click="showCreatePassword = !showCreatePassword"
                  >
                    <EyeOff v-if="showCreatePassword" class="h-4 w-4" />
                    <Eye v-else class="h-4 w-4" />
                  </button>
                </div>
                <p v-if="createErrors.password" class="text-xs text-destructive">{{ createErrors.password[0] }}</p>
              </div>
              <div class="space-y-1.5 text-left">
                <Label class="text-xs font-semibold">Konfirmasi Password <span class="text-destructive">*</span></Label>
                <div class="relative">
                  <Input
                    v-model="createForm.password_confirmation"
                    :type="showCreatePasswordConfirm ? 'text' : 'password'"
                    placeholder="Ulangi password"
                    required
                    class="h-10 text-sm bg-background/50 pr-10"
                  />
                  <button
                    type="button"
                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors p-1 rounded focus:outline-none"
                    :title="showCreatePasswordConfirm ? 'Sembunyikan password' : 'Lihat password'"
                    tabindex="-1"
                    @click="showCreatePasswordConfirm = !showCreatePasswordConfirm"
                  >
                    <EyeOff v-if="showCreatePasswordConfirm" class="h-4 w-4" />
                    <Eye v-else class="h-4 w-4" />
                  </button>
                </div>
              </div>
            </div>

            <!-- Phone & Birth Date -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div class="space-y-1.5 text-left">
                <Label class="text-xs font-semibold">Nomor WhatsApp / HP</Label>
                <Input
                  v-model="createForm.phone"
                  type="tel"
                  placeholder="08xxxxxxxxxx"
                  class="h-10 text-sm bg-background/50"
                />
              </div>
              <div class="space-y-1.5 text-left">
                <Label class="text-xs font-semibold">Tanggal Lahir</Label>
                <Input
                  v-model="createForm.birth_date"
                  type="date"
                  class="h-10 text-sm bg-background/50"
                />
              </div>
            </div>

            <!-- Buttons -->
            <div class="flex items-center justify-end gap-3 pt-3 border-t border-border">
              <Button
                type="button"
                variant="outline"
                size="sm"
                class="h-10 px-4"
                @click="isCreateAdminOpen = false"
              >
                Batal
              </Button>
              <Button
                type="submit"
                class="h-10 px-5 gap-2 shadow-md shadow-primary/20"
                :disabled="createLoading"
              >
                <Loader2 v-if="createLoading" class="h-4 w-4 animate-spin" />
                <ShieldPlus v-else class="h-4 w-4" />
                <span>Simpan Admin Baru</span>
              </Button>
            </div>
          </form>
        </div>
      </div>
    </Transition>

    <!-- ============================================================== -->
    <!-- MODAL: EDIT AKUN                                              -->
    <!-- ============================================================== -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-if="isEditOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
      >
        <div
          class="relative w-full max-w-lg rounded-2xl border border-border bg-card p-6 shadow-2xl space-y-5 max-h-[90vh] overflow-y-auto"
          @click.stop
        >
          <!-- Header -->
          <div class="flex items-start justify-between">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary">
                <Pencil class="w-5 h-5" />
              </div>
              <div>
                <h3 class="text-lg font-bold text-foreground">Edit Data Akun</h3>
                <p class="text-xs text-muted-foreground">Perbarui informasi, peran, atau status akun.</p>
              </div>
            </div>
            <button
              type="button"
              class="text-muted-foreground hover:text-foreground p-1 rounded-md"
              @click="isEditOpen = false"
            >
              <X class="w-5 h-5" />
            </button>
          </div>

          <!-- Form -->
          <form class="space-y-4" @submit.prevent="submitEdit">
            <!-- Nama -->
            <div class="space-y-1.5 text-left">
              <Label class="text-xs font-semibold">Nama Lengkap <span class="text-destructive">*</span></Label>
              <Input
                v-model="editForm.name"
                type="text"
                required
                class="h-10 text-sm bg-background/50"
              />
              <p v-if="editErrors.name" class="text-xs text-destructive">{{ editErrors.name[0] }}</p>
            </div>

            <!-- Email -->
            <div class="space-y-1.5 text-left">
              <Label class="text-xs font-semibold">Alamat Email <span class="text-destructive">*</span></Label>
              <Input
                v-model="editForm.email"
                type="email"
                required
                class="h-10 text-sm bg-background/50"
              />
              <p v-if="editErrors.email" class="text-xs text-destructive">{{ editErrors.email[0] }}</p>
            </div>

            <!-- Peran & Persetujuan -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div class="space-y-1.5 text-left">
                <Label class="text-xs font-semibold">Peran (Role)</Label>
                <select
                  v-model="editForm.role"
                  class="w-full h-10 px-3 rounded-md border border-input bg-background/50 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary"
                  :disabled="editingUserId === authStore.user?.id"
                >
                  <option value="admin">Administrator</option>
                  <option value="user">Pengguna Biasa</option>
                </select>
                <p v-if="editingUserId === authStore.user?.id" class="text-[11px] text-muted-foreground italic">
                  Tidak dapat mengubah peran akun sendiri
                </p>
              </div>

              <div class="space-y-1.5 text-left">
                <Label class="text-xs font-semibold">Status Persetujuan (ACC)</Label>
                <select
                  v-model="editForm.approval_status"
                  class="w-full h-10 px-3 rounded-md border border-input bg-background/50 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-primary"
                >
                  <option value="approved">Disetujui (Approved)</option>
                  <option value="pending">Menunggu Persetujuan (Pending)</option>
                  <option value="rejected">Ditolak (Rejected)</option>
                </select>
              </div>
            </div>

            <!-- Status Aktif Checkbox -->
            <div class="flex items-center gap-2 p-3 rounded-lg border border-border/70 bg-background/30">
              <input
                id="edit-is-active"
                v-model="editForm.is_active"
                type="checkbox"
                class="w-4 h-4 rounded border-input text-primary focus:ring-primary"
                :disabled="editingUserId === authStore.user?.id"
              />
              <Label for="edit-is-active" class="text-xs font-medium cursor-pointer">
                Akun Aktif (Dapat masuk ke aplikasi)
              </Label>
            </div>

            <!-- Phone & Birth Date -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div class="space-y-1.5 text-left">
                <Label class="text-xs font-semibold">Nomor WhatsApp / HP</Label>
                <Input
                  v-model="editForm.phone"
                  type="tel"
                  placeholder="08xxxxxxxxxx"
                  class="h-10 text-sm bg-background/50"
                />
              </div>
              <div class="space-y-1.5 text-left">
                <Label class="text-xs font-semibold">Tanggal Lahir</Label>
                <Input
                  v-model="editForm.birth_date"
                  type="date"
                  class="h-10 text-sm bg-background/50"
                />
              </div>
            </div>

            <!-- Ganti Password (Opsional) -->
            <div class="space-y-2 text-left p-3 rounded-lg border border-border/50 bg-muted/10">
              <Label class="text-xs font-semibold text-muted-foreground">Ubah Password (Opsional)</Label>
              <div class="relative">
                <Input
                  v-model="editForm.password"
                  :type="showEditPassword ? 'text' : 'password'"
                  placeholder="Kosongkan jika tidak ingin mengubah password"
                  class="h-10 text-sm bg-background/50 pr-10"
                />
                <button
                  type="button"
                  class="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors p-1 rounded focus:outline-none"
                  :title="showEditPassword ? 'Sembunyikan password' : 'Lihat password'"
                  tabindex="-1"
                  @click="showEditPassword = !showEditPassword"
                >
                  <EyeOff v-if="showEditPassword" class="h-4 w-4" />
                  <Eye v-else class="h-4 w-4" />
                </button>
              </div>

              <div class="relative mt-2">
                <Input
                  v-model="editForm.password_confirmation"
                  :type="showEditPasswordConfirm ? 'text' : 'password'"
                  placeholder="Ulangi password baru"
                  class="h-10 text-sm bg-background/50 pr-10"
                />
                <button
                  type="button"
                  class="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition-colors p-1 rounded focus:outline-none"
                  :title="showEditPasswordConfirm ? 'Sembunyikan password' : 'Lihat password'"
                  tabindex="-1"
                  @click="showEditPasswordConfirm = !showEditPasswordConfirm"
                >
                  <EyeOff v-if="showEditPasswordConfirm" class="h-4 w-4" />
                  <Eye v-else class="h-4 w-4" />
                </button>
              </div>

              <p v-if="editErrors.password" class="text-xs text-destructive">{{ editErrors.password[0] }}</p>
              <p class="text-[11px] text-muted-foreground">Minimal 12 karakter bila diisi.</p>
            </div>

            <!-- Buttons -->
            <div class="flex items-center justify-end gap-3 pt-3 border-t border-border">
              <Button
                type="button"
                variant="outline"
                size="sm"
                class="h-10 px-4"
                @click="isEditOpen = false"
              >
                Batal
              </Button>
              <Button
                type="submit"
                class="h-10 px-5 gap-2 shadow-md shadow-primary/20"
                :disabled="editLoading"
              >
                <Loader2 v-if="editLoading" class="h-4 w-4 animate-spin" />
                <span>Simpan Perubahan</span>
              </Button>
            </div>
          </form>
        </div>
      </div>
    </Transition>

    <!-- ============================================================== -->
    <!-- MODAL: KONFIRMASI HAPUS                                       -->
    <!-- ============================================================== -->
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-if="isDeleteOpen && deletingUser"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
      >
        <div
          class="relative w-full max-w-md rounded-2xl border border-destructive/40 bg-card p-6 shadow-2xl space-y-4"
          @click.stop
        >
          <div class="flex items-start gap-3">
            <div class="w-10 h-10 rounded-xl bg-destructive/15 border border-destructive/30 flex items-center justify-center text-destructive flex-shrink-0">
              <AlertTriangle class="w-5 h-5" />
            </div>
            <div>
              <h3 class="text-lg font-bold text-foreground">Hapus Akun Pengguna</h3>
              <p class="text-xs text-muted-foreground mt-0.5">
                Tindakan ini tidak dapat dibatalkan. Riwayat data terkait akun ini akan terhapus.
              </p>
            </div>
          </div>

          <div class="p-3 rounded-lg border border-border bg-background/50 space-y-1 text-xs">
            <div class="flex justify-between">
              <span class="text-muted-foreground">Nama:</span>
              <span class="font-semibold text-foreground">{{ deletingUser.name }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Email:</span>
              <span class="font-mono text-foreground">{{ deletingUser.email }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-muted-foreground">Peran:</span>
              <span class="font-semibold uppercase text-foreground">{{ deletingUser.role }}</span>
            </div>
          </div>

          <div class="flex items-center justify-end gap-3 pt-2">
            <Button
              type="button"
              variant="outline"
              size="sm"
              class="h-9 px-4"
              @click="isDeleteOpen = false"
            >
              Batal
            </Button>
            <Button
              type="button"
              variant="destructive"
              size="sm"
              class="h-9 px-4 gap-1.5"
              :disabled="deleteLoading"
              @click="submitDelete"
            >
              <Loader2 v-if="deleteLoading" class="h-4 w-4 animate-spin" />
              <Trash2 v-else class="h-4 w-4" />
              <span>Hapus Sekarang</span>
            </Button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>
