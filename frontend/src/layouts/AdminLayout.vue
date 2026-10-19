<script setup lang="ts">
import { ref, computed } from 'vue'
import { RouterView, RouterLink, useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import NotificationDropdown from '@/components/NotificationDropdown.vue'
import {
  LayoutDashboard,
  FileText,
  ClipboardCheck,
  Users,
  Search,
  Bell,
  Code2,
  ChevronDown,
  LogOut,
  User as UserIcon,
  Plus,
} from 'lucide-vue-next'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const isSidebarCollapsed = ref(false)
const showProfileMenu = ref(false)
const globalSearch = ref('')

const breadcrumb = computed(() => {
  const name = route.name?.toString() || ''
  if (name === 'admin-dashboard') {
    return [{ label: 'Dashboard', to: '/admin' }, { label: 'Analytic' }]
  }
  if (name.includes('admin-survey-create')) {
    return [{ label: 'Dashboard', to: '/admin' }, { label: 'Survey', to: '/admin/surveys' }, { label: 'Create Survey' }]
  }
  if (name.includes('admin-survey-edit')) {
    return [{ label: 'Dashboard', to: '/admin' }, { label: 'Survey', to: '/admin/surveys' }, { label: 'Edit Survey' }]
  }
  if (name.includes('admin-survey-results')) {
    return [{ label: 'Dashboard', to: '/admin' }, { label: 'Survey', to: '/admin/surveys' }, { label: 'Result' }]
  }
  if (name === 'admin-responses') {
    return [{ label: 'Dashboard', to: '/admin' }, { label: 'Responses' }]
  }
  if (name.includes('admin-survey')) {
    return [{ label: 'Dashboard', to: '/admin' }, { label: 'Survey' }]
  }
  if (name.includes('admin-user')) {
    return [{ label: 'Dashboard', to: '/admin' }, { label: 'Participant' }]
  }
  return [{ label: 'Dashboard', to: '/admin' }, { label: 'Analytic' }]
})

async function handleLogout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="min-h-screen flex bg-[#F8F9FC] text-[#1E2538] font-sans antialiased">
    <!-- Sidebar -->
    <aside
      class="border-r border-[#EDF1F7] bg-white flex flex-col justify-between shrink-0 sticky top-0 h-screen z-30 transition-all duration-300 select-none"
      :class="isSidebarCollapsed ? 'w-20' : 'w-64'"
    >
      <div>
        <!-- Brand Header -->
        <div class="h-20 flex items-center justify-between px-6 border-b border-[#F1F3F8]">
          <RouterLink to="/admin" class="flex items-center gap-3 group">
            <!-- Modern Folded Polygon Logo matching screenshot -->
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#4E53E9] to-[#393DC5] flex items-center justify-center text-white shadow-md shadow-[#4E53E9]/20 transition-transform group-hover:scale-105">
              <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 19L12 4L20 19H14.5L12 14L9.5 19H4Z" fill="white" />
                <path d="M12 4L16.5 13H19.5L12 4Z" fill="white" fill-opacity="0.35" />
              </svg>
            </div>
            <div v-if="!isSidebarCollapsed" class="flex flex-col">
              <span class="font-extrabold text-base tracking-tight text-[#1E2538]">PulsePoint<span class="text-[#4E53E9]">.</span></span>
            </div>
          </RouterLink>

          <!-- Collapse / Expand Toggle Button (< >) -->
          <button
            class="p-1.5 rounded-lg text-[#94A3B8] hover:text-[#4E53E9] hover:bg-[#F4F5FD] transition-colors cursor-pointer"
            :title="isSidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
            @click="isSidebarCollapsed = !isSidebarCollapsed"
          >
            <Code2 class="w-4 h-4" />
          </button>
        </div>

        <!-- Navigation Links -->
        <nav class="p-4 space-y-2">
          <!-- 1. Dashboard -->
          <RouterLink
            to="/admin"
            class="flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl text-sm transition-all duration-200"
            :class="
              route.name === 'admin-dashboard'
                ? 'border border-[#4E53E9] text-[#4E53E9] bg-[#F4F5FD] font-semibold shadow-xs'
                : 'text-[#64748B] hover:text-[#1E2538] hover:bg-[#F8F9FC] font-medium'
            "
            :title="isSidebarCollapsed ? 'Dashboard' : ''"
          >
            <LayoutDashboard class="w-5 h-5 shrink-0" :class="route.name === 'admin-dashboard' ? 'text-[#4E53E9]' : 'text-[#94A3B8]'" />
            <span v-if="!isSidebarCollapsed">Dashboard</span>
          </RouterLink>

          <!-- 2. Survey -->
          <RouterLink
            to="/admin/surveys"
            class="flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl text-sm transition-all duration-200"
            :class="
              route.name === 'admin-surveys' || route.name === 'admin-survey-create' || route.name === 'admin-survey-edit'
                ? 'border border-[#4E53E9] text-[#4E53E9] bg-[#F4F5FD] font-semibold shadow-xs'
                : 'text-[#64748B] hover:text-[#1E2538] hover:bg-[#F8F9FC] font-medium'
            "
            :title="isSidebarCollapsed ? 'Survey' : ''"
          >
            <FileText class="w-5 h-5 shrink-0" :class="route.name === 'admin-surveys' || route.name === 'admin-survey-create' || route.name === 'admin-survey-edit' ? 'text-[#4E53E9]' : 'text-[#94A3B8]'" />
            <span v-if="!isSidebarCollapsed">Survey</span>
          </RouterLink>

          <!-- 3. Responses -->
          <RouterLink
            to="/admin/responses"
            class="flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl text-sm transition-all duration-200"
            :class="
              route.name === 'admin-responses' || route.name === 'admin-survey-results'
                ? 'border border-[#4E53E9] text-[#4E53E9] bg-[#F4F5FD] font-semibold shadow-xs'
                : 'text-[#64748B] hover:text-[#1E2538] hover:bg-[#F8F9FC] font-medium'
            "
            :title="isSidebarCollapsed ? 'Responses' : ''"
          >
            <ClipboardCheck class="w-5 h-5 shrink-0" :class="route.name === 'admin-responses' || route.name === 'admin-survey-results' ? 'text-[#4E53E9]' : 'text-[#94A3B8]'" />
            <span v-if="!isSidebarCollapsed">Responses</span>
          </RouterLink>

          <!-- 4. Participant -->
          <RouterLink
            to="/admin/users"
            class="flex items-center gap-3.5 px-3.5 py-2.5 rounded-xl text-sm transition-all duration-200"
            :class="
              route.name === 'admin-users'
                ? 'border border-[#4E53E9] text-[#4E53E9] bg-[#F4F5FD] font-semibold shadow-xs'
                : 'text-[#64748B] hover:text-[#1E2538] hover:bg-[#F8F9FC] font-medium'
            "
            :title="isSidebarCollapsed ? 'Participant' : ''"
          >
            <Users class="w-5 h-5 shrink-0" :class="route.name === 'admin-users' ? 'text-[#4E53E9]' : 'text-[#94A3B8]'" />
            <span v-if="!isSidebarCollapsed">Participant</span>
          </RouterLink>
        </nav>
      </div>

      <!-- Bottom User Profile Pill matching screenshot -->
      <div class="p-4 border-t border-[#F1F3F8] relative">
        <div
          class="flex items-center justify-between p-2 rounded-xl hover:bg-[#F8F9FC] transition-colors cursor-pointer"
          @click="showProfileMenu = !showProfileMenu"
        >
          <div class="flex items-center gap-3 overflow-hidden">
            <!-- User Avatar Circle -->
            <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-[#4E53E9] to-[#818CF8] text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
              {{ auth.user?.name?.charAt(0).toUpperCase() || 'A' }}
            </div>
            <div v-if="!isSidebarCollapsed" class="overflow-hidden">
              <div class="text-xs font-bold text-[#1E2538] truncate">{{ auth.user?.name || 'Indra Lesmana' }}</div>
              <div class="text-[11px] text-[#94A3B8] truncate">{{ auth.user?.email || 'admin@survey.com' }}</div>
            </div>
          </div>
          <ChevronDown
            v-if="!isSidebarCollapsed"
            class="w-4 h-4 text-[#94A3B8] transition-transform duration-200"
            :class="showProfileMenu ? 'rotate-180' : ''"
          />
        </div>

        <!-- Popover Dropdown Menu -->
        <div
          v-if="showProfileMenu"
          class="absolute bottom-18 left-4 right-4 bg-white rounded-xl shadow-xl border border-[#EDF1F7] p-1.5 z-50 text-xs animate-in fade-in zoom-in-95 duration-150"
        >
          <div class="px-3 py-2 border-b border-[#F1F3F8]">
            <p class="font-bold text-[#1E2538]">{{ auth.user?.name }}</p>
            <p class="text-[11px] text-[#94A3B8]">{{ auth.user?.role?.toUpperCase() }}</p>
          </div>
          <button
            class="w-full text-left px-3 py-2 rounded-lg text-[#EF4444] hover:bg-[#FEF2F2] flex items-center gap-2 font-medium transition-colors cursor-pointer mt-1"
            @click="handleLogout"
          >
            <LogOut class="w-3.5 h-3.5" />
            <span>Keluar (Logout)</span>
          </button>
        </div>
      </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 min-w-0 flex flex-col min-h-screen">
      <!-- Top Navigation Bar matching screenshot -->
      <header class="h-20 bg-white border-b border-[#EDF1F7] px-8 flex items-center justify-between sticky top-0 z-20">
        <!-- Breadcrumb on the left -->
        <div class="flex items-center gap-2 text-sm text-[#94A3B8]">
          <template v-for="(item, idx) in breadcrumb" :key="idx">
            <RouterLink
              v-if="item.to"
              :to="item.to"
              class="hover:text-[#4E53E9] transition-colors"
            >
              {{ item.label }}
            </RouterLink>
            <span v-else class="font-bold text-[#1E2538]">{{ item.label }}</span>
            <span v-if="idx < breadcrumb.length - 1" class="text-[#CBD5E1]">&gt;</span>
          </template>
        </div>

        <!-- Center Search Bar -->
        <div class="relative w-80 max-w-sm hidden md:block">
          <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-[#94A3B8]" />
          <input
            v-model="globalSearch"
            type="text"
            placeholder="Search"
            class="w-full bg-[#F8F9FC] border border-[#EDF1F7] rounded-xl pl-10 pr-4 py-2 text-xs text-[#1E2538] placeholder-[#94A3B8] focus:outline-none focus:border-[#4E53E9] focus:bg-white transition-all shadow-xs"
          />
        </div>

        <!-- Right Action Items -->
        <div class="flex items-center gap-3">
          <!-- "Create Survey" Button matching screenshot (outline with primary indigo) -->
          <button
            class="border border-[#4E53E9] text-[#4E53E9] hover:bg-[#4E53E9] hover:text-white px-4 py-2 rounded-xl text-xs font-semibold shadow-xs transition-all flex items-center gap-1.5 cursor-pointer"
            @click="router.push({ name: 'admin-survey-create' })"
          >
            <Plus class="w-3.5 h-3.5" />
            <span>Create Survey</span>
          </button>

          <!-- Notification Dropdown Component -->
          <NotificationDropdown role="admin" />
        </div>
      </header>

      <!-- Page Content -->
      <main class="flex-1 bg-[#F8F9FC]">
        <RouterView />
      </main>
    </div>
  </div>
</template>
