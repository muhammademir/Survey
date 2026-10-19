<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'
import {
  Bell,
  CheckCheck,
  UserPlus,
  ClipboardCheck,
  Clock,
  Sparkles,
  CheckCircle2,
  X,
  ExternalLink,
} from 'lucide-vue-next'

const props = withDefaults(
  defineProps<{
    role?: 'admin' | 'user'
  }>(),
  {
    role: 'admin',
  }
)

const router = useRouter()
const auth = useAuthStore()

interface NotificationItem {
  id: string
  title: string
  message: string
  time: string
  type: 'user' | 'survey' | 'reminder' | 'system'
  route?: string
  read: boolean
}

const isOpen = ref(false)
const activeTab = ref<'all' | 'unread'>('all')
const dropdownRef = ref<HTMLElement | null>(null)

const notifications = ref<NotificationItem[]>([])

// Load read state from localStorage
const STORAGE_KEY = `pulsepoint_notifications_${props.role}`

function loadNotifications() {
  const readIds: string[] = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]')

  if (props.role === 'admin') {
    notifications.value = [
      {
        id: 'admin-pending-user',
        title: 'Pendaftaran Menunggu Persetujuan',
        message: 'Pengguna baru mendaftar dan menunggu verifikasi (ACC) akun dari Administrator.',
        time: '5 menit lalu',
        type: 'user',
        route: '/admin/users',
        read: readIds.includes('admin-pending-user'),
      },
      {
        id: 'admin-new-response',
        title: 'Respon Baru Diterima',
        message: 'Responden telah menyelesaikan pengisian survei "User Journey Exploration".',
        time: '25 menit lalu',
        type: 'survey',
        route: '/admin/surveys',
        read: readIds.includes('admin-new-response'),
      },
      {
        id: 'admin-survey-expiring',
        title: 'Pengingat Masa Tayang',
        message: 'Survei "Usability and User Interface" tersisa 2 hari sebelum periode berakhir.',
        time: '2 jam lalu',
        type: 'reminder',
        route: '/admin/surveys',
        read: readIds.includes('admin-survey-expiring'),
      },
    ]
  } else {
    notifications.value = [
      {
        id: 'user-new-survey',
        title: 'Survei Baru Tersedia',
        message: 'Kuesioner "User Journey Exploration" telah dibuka dan siap untuk Anda isi.',
        time: '10 menit lalu',
        type: 'survey',
        route: '/',
        read: readIds.includes('user-new-survey'),
      },
      {
        id: 'user-account-approved',
        title: 'Akun Terverifikasi',
        message: 'Selamat! Akun Anda telah disetujui oleh Administrator dan berstatus aktif.',
        time: '1 jam lalu',
        type: 'system',
        route: '/profile',
        read: readIds.includes('user-account-approved'),
      },
      {
        id: 'user-expiring-reminder',
        title: 'Batas Pengisian Kuesioner',
        message: 'Jangan lewatkan kesempatan untuk memberikan feedback pada survei aktif.',
        time: '1 hari lalu',
        type: 'reminder',
        route: '/',
        read: readIds.includes('user-expiring-reminder'),
      },
    ]
  }
}

// Fetch real dynamic notifications if available
async function fetchRealNotifications() {
  try {
    if (props.role === 'admin') {
      const res = await api.get('/admin/users?approval_status=pending&per_page=5')
      const pendingUsers = res.data.data || []
      const readIds: string[] = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]')

      if (pendingUsers.length > 0) {
        const topUser = pendingUsers[0]
        const pendingItem: NotificationItem = {
          id: `admin-pending-${topUser.id}`,
          title: 'Pengguna Menunggu ACC',
          message: `${topUser.name} (${topUser.email}) sedang menunggu persetujuan akun Anda.`,
          time: 'Baru saja',
          type: 'user',
          route: '/admin/users',
          read: readIds.includes(`admin-pending-${topUser.id}`),
        }
        // Insert at beginning if not already there
        if (!notifications.value.some(n => n.id === pendingItem.id)) {
          notifications.value.unshift(pendingItem)
        }
      }
    }
  } catch {
    // silent
  }
}

const unreadCount = computed(() => notifications.value.filter(n => !n.read).length)

const displayedNotifications = computed(() => {
  if (activeTab.value === 'unread') {
    return notifications.value.filter(n => !n.read)
  }
  return notifications.value
})

function toggleDropdown() {
  isOpen.value = !isOpen.value
}

function closeDropdown() {
  isOpen.value = false
}

function markAllAsRead() {
  notifications.value.forEach(n => {
    n.read = true
  })
  const allIds = notifications.value.map(n => n.id)
  localStorage.setItem(STORAGE_KEY, JSON.stringify(allIds))
}

function handleNotificationClick(item: NotificationItem) {
  item.read = true
  const readIds: string[] = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]')
  if (!readIds.includes(item.id)) {
    readIds.push(item.id)
    localStorage.setItem(STORAGE_KEY, JSON.stringify(readIds))
  }
  closeDropdown()
  if (item.route) {
    router.push(item.route)
  }
}

function removeNotification(e: Event, id: string) {
  e.stopPropagation()
  notifications.value = notifications.value.filter(n => n.id !== id)
}

function handleClickOutside(e: MouseEvent) {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target as Node)) {
    closeDropdown()
  }
}

onMounted(() => {
  loadNotifications()
  fetchRealNotifications()
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
  <div ref="dropdownRef" class="relative inline-block text-left select-none">
    <!-- Bell Trigger Button -->
    <button
      class="w-9 h-9 rounded-xl border border-[#EDF1F7] bg-[#F8F9FC] hover:bg-white text-[#64748B] hover:text-[#4E53E9] flex items-center justify-center transition-all cursor-pointer relative shadow-2xs hover:shadow-xs active:scale-95"
      :class="isOpen ? 'bg-white text-[#4E53E9] border-[#4E53E9]/30 shadow-xs' : ''"
      title="Notifikasi"
      @click="toggleDropdown"
    >
      <Bell class="w-4 h-4 transition-transform duration-200" :class="isOpen ? 'scale-110' : ''" />

      <!-- Red Pulsing Notification Badge -->
      <span
        v-if="unreadCount > 0"
        class="absolute -top-1 -right-1 flex h-4 min-w-4 px-1 items-center justify-center rounded-full bg-[#EF4444] text-[10px] font-bold text-white shadow-xs animate-in zoom-in-75 duration-200"
      >
        {{ unreadCount }}
      </span>
    </button>

    <!-- Dropdown Popover Panel -->
    <transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="transform scale-95 opacity-0 -translate-y-2"
      enter-to-class="transform scale-100 opacity-100 translate-y-0"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="transform scale-100 opacity-100 translate-y-0"
      leave-to-class="transform scale-95 opacity-0 -translate-y-2"
    >
      <div
        v-if="isOpen"
        class="absolute right-0 mt-2.5 w-84 sm:w-96 rounded-2xl bg-white shadow-2xl border border-[#EDF1F7] p-0 z-50 overflow-hidden font-sans"
      >
        <!-- Header -->
        <div class="px-5 py-4 border-b border-[#F1F3F8] flex items-center justify-between bg-white">
          <div class="flex items-center gap-2.5">
            <h3 class="font-bold text-sm text-[#1E2538] tracking-tight">Notifikasi</h3>
            <span
              v-if="unreadCount > 0"
              class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-[#EEF1FD] text-[#4E53E9]"
            >
              {{ unreadCount }} Baru
            </span>
          </div>

          <button
            v-if="unreadCount > 0"
            class="text-xs font-semibold text-[#4E53E9] hover:text-[#393DC5] flex items-center gap-1 cursor-pointer transition-colors"
            @click="markAllAsRead"
          >
            <CheckCheck class="w-3.5 h-3.5" />
            <span>Tandai dibaca</span>
          </button>
        </div>

        <!-- Filter Tabs -->
        <div class="px-5 pt-3 pb-2 flex items-center gap-2 border-b border-[#F8F9FD] bg-[#FCFCFD]">
          <button
            class="px-3 py-1 rounded-lg text-xs font-semibold transition-all cursor-pointer"
            :class="
              activeTab === 'all'
                ? 'bg-white text-[#4E53E9] shadow-2xs border border-[#EDF1F7]'
                : 'text-[#94A3B8] hover:text-[#1E2538]'
            "
            @click="activeTab = 'all'"
          >
            Semua ({{ notifications.length }})
          </button>
          <button
            class="px-3 py-1 rounded-lg text-xs font-semibold transition-all cursor-pointer"
            :class="
              activeTab === 'unread'
                ? 'bg-white text-[#4E53E9] shadow-2xs border border-[#EDF1F7]'
                : 'text-[#94A3B8] hover:text-[#1E2538]'
            "
            @click="activeTab = 'unread'"
          >
            Belum Dibaca ({{ unreadCount }})
          </button>
        </div>

        <!-- Notification List -->
        <div class="max-h-80 overflow-y-auto divide-y divide-[#F1F3F8]">
          <div
            v-if="displayedNotifications.length === 0"
            class="py-12 px-6 text-center text-[#94A3B8]"
          >
            <div class="w-10 h-10 rounded-full bg-[#F8F9FC] text-[#94A3B8] flex items-center justify-center mx-auto mb-2">
              <Bell class="w-5 h-5" />
            </div>
            <p class="text-xs font-medium text-[#1E2538]">Tidak ada notifikasi baru</p>
            <p class="text-[11px] text-[#94A3B8] mt-0.5">Semua aktivitas telah Anda tinjau.</p>
          </div>

          <div
            v-for="item in displayedNotifications"
            :key="item.id"
            class="p-4 hover:bg-[#F8F9FC] transition-colors cursor-pointer flex items-start gap-3 relative group"
            :class="!item.read ? 'bg-[#FAFBFF]' : ''"
            @click="handleNotificationClick(item)"
          >
            <!-- Notification Icon -->
            <div
              class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 shadow-2xs mt-0.5"
              :class="{
                'bg-[#FEF3C7] text-[#D97706] border border-[#FDE68A]': item.type === 'user',
                'bg-[#EEF1FD] text-[#4E53E9] border border-[#E0E7FF]': item.type === 'survey',
                'bg-[#ECFDF5] text-[#059669] border border-[#A7F3D0]': item.type === 'system',
                'bg-[#F1F5F9] text-[#64748B] border border-[#E2E8F0]': item.type === 'reminder',
              }"
            >
              <UserPlus v-if="item.type === 'user'" class="w-4 h-4" />
              <ClipboardCheck v-else-if="item.type === 'survey'" class="w-4 h-4" />
              <CheckCircle2 v-else-if="item.type === 'system'" class="w-4 h-4" />
              <Clock v-else class="w-4 h-4" />
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0 pr-4">
              <div class="flex items-center gap-2">
                <span class="font-bold text-xs text-[#1E2538] truncate">{{ item.title }}</span>
                <span
                  v-if="!item.read"
                  class="w-1.5 h-1.5 rounded-full bg-[#4E53E9] shrink-0"
                />
              </div>
              <p class="text-xs text-[#64748B] leading-relaxed mt-1 line-clamp-2">
                {{ item.message }}
              </p>
              <span class="text-[10px] text-[#94A3B8] font-medium mt-1.5 block">
                {{ item.time }}
              </span>
            </div>

            <!-- Dismiss Button -->
            <button
              class="opacity-0 group-hover:opacity-100 p-1 text-[#94A3B8] hover:text-[#EF4444] rounded-md transition-all cursor-pointer absolute right-3 top-3"
              title="Hapus notifikasi"
              @click="(e) => removeNotification(e, item.id)"
            >
              <X class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>

        <!-- Footer -->
        <div class="p-3 bg-[#FCFCFD] border-t border-[#F1F3F8] text-center">
          <button
            class="text-xs font-semibold text-[#4E53E9] hover:underline cursor-pointer"
            @click="closeDropdown"
          >
            Tutup
          </button>
        </div>
      </div>
    </transition>
  </div>
</template>
