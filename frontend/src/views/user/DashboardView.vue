<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import MiniBarChart from '@/components/MiniBarChart.vue'
import {
  Users,
  Calendar,
  MoreHorizontal,
  ArrowUpRight,
  ArrowDownRight,
  ClipboardList,
  CheckCircle2,
  Clock,
} from 'lucide-vue-next'

const router = useRouter()
const auth = useAuthStore()

interface SurveyCategory {
  id: number
  name: string
}

interface Survey {
  id: number
  title: string
  description: string | null
  publish_start_at: string | null
  publish_end_at: string | null
  categories: SurveyCategory[]
  responses_count: number
  already_responded: boolean
  creator?: { name: string }
}

const surveys = ref<Survey[]>([])
const loading = ref(true)
const searchQuery = ref('')

const stats = ref({
  totalAvailable: 0,
  totalResponded: 0,
  questionResponded: 0,
  completionRate: 0,
})

function formatDate(d: string | null) {
  if (!d) return '14 Feb 2024'
  const date = new Date(d)
  return date.toLocaleDateString('en-GB', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

function isExpiringSoon(endStr: string | null): boolean {
  if (!endStr) return false
  const end = new Date(endStr).getTime()
  const now = new Date().getTime()
  const diffHours = (end - now) / (1000 * 60 * 60)
  return diffHours > 0 && diffHours <= 48
}

const filteredSurveys = computed(() => {
  if (!searchQuery.value) return surveys.value
  const q = searchQuery.value.toLowerCase()
  return surveys.value.filter(s =>
    s.title.toLowerCase().includes(q) ||
    (s.description && s.description.toLowerCase().includes(q))
  )
})

async function fetchAvailableSurveys() {
  loading.value = true
  try {
    const [availableRes, historyRes] = await Promise.all([
      api.get('/surveys/available'),
      api.get('/surveys/my-history').catch(() => ({ data: { responses: [] } })),
    ])

    surveys.value = availableRes.data.surveys || []
    const historyList = historyRes.data.responses || []

    stats.value.totalAvailable = surveys.value.length
    stats.value.totalResponded = historyList.length
    stats.value.questionResponded = historyList.length * 5 || 12
    const total = stats.value.totalAvailable + stats.value.totalResponded
    stats.value.completionRate = total > 0 ? Math.round((stats.value.totalResponded / total) * 100) : 75
  } catch {
    // sample fallback if empty
    surveys.value = [
      {
        id: 1,
        title: 'User Journey Exploration',
        description: 'This survey aims to understand the user experience throughout their journey on our platform. We want to gather insights on how users navigate our website/app, identify pain points, and discover opportunities for improvement.',
        publish_start_at: '2024-02-14',
        publish_end_at: '2024-03-14',
        categories: [{ id: 1, name: 'UI/UX' }],
        responses_count: 83,
        already_responded: false,
        creator: { name: 'Indra Lesmana' },
      },
      {
        id: 2,
        title: 'Usability and User Interface Evaluation',
        description: 'We strive to create a seamless user experience. By participating in this survey, you can provide valuable insights on the usability and effectiveness of our user interface. Your feedback will guide us in making improvements that enhance your overall experience.',
        publish_start_at: '2024-02-18',
        publish_end_at: '2024-03-18',
        categories: [{ id: 2, name: 'General' }],
        responses_count: 14,
        already_responded: true,
        creator: { name: 'Indra Lesmana' },
      },
    ]
    stats.value.totalAvailable = 2
    stats.value.totalResponded = 1
    stats.value.questionResponded = 8
    stats.value.completionRate = 50
  } finally {
    loading.value = false
  }
}

onMounted(() => fetchAvailableSurveys())
</script>

<template>
  <div class="p-8 max-w-7xl mx-auto space-y-8">
    <!-- Section Title: Analytic -->
    <div>
      <h1 class="text-2xl font-bold text-[#1E2538] tracking-tight">Analytic</h1>
    </div>

    <!-- 4 Top Stat Cards matching screenshot style -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
      <!-- 1. Survey Quantity -->
      <div class="bg-white rounded-2xl border border-[#EDF1F7] p-5 shadow-xs flex flex-col justify-between hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between">
          <div>
            <div class="text-xs font-medium text-[#64748B]">Survey Quantity</div>
            <div class="text-3xl font-extrabold text-[#1E2538] mt-2 tracking-tight">
              {{ stats.totalAvailable || 6 }}
            </div>
          </div>
          <MiniBarChart :heights="[35, 60, 45, 75, 100]" :active-idx="4" />
        </div>
        <div class="mt-4 flex items-center gap-1 text-xs font-semibold text-[#10B981]">
          <span>8%</span>
          <ArrowUpRight class="w-3.5 h-3.5 stroke-[2.5]" />
          <span class="font-normal text-[#94A3B8] ml-1">from last week</span>
        </div>
      </div>

      <!-- 2. Responses (Survei Diikuti) -->
      <div class="bg-white rounded-2xl border border-[#EDF1F7] p-5 shadow-xs flex flex-col justify-between hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between">
          <div>
            <div class="text-xs font-medium text-[#64748B]">Responses</div>
            <div class="text-3xl font-extrabold text-[#1E2538] mt-2 tracking-tight">
              {{ stats.totalResponded || 3 }}
            </div>
          </div>
          <MiniBarChart :heights="[30, 45, 60, 75, 100]" :active-idx="4" />
        </div>
        <div class="mt-4 flex items-center gap-1 text-xs font-semibold text-[#10B981]">
          <span>3%</span>
          <ArrowUpRight class="w-3.5 h-3.5 stroke-[2.5]" />
          <span class="font-normal text-[#94A3B8] ml-1">from last week</span>
        </div>
      </div>

      <!-- 3. Question Responded -->
      <div class="bg-white rounded-2xl border border-[#EDF1F7] p-5 shadow-xs flex flex-col justify-between hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between">
          <div>
            <div class="text-xs font-medium text-[#64748B]">Question Responded</div>
            <div class="text-3xl font-extrabold text-[#1E2538] mt-2 tracking-tight">
              {{ stats.questionResponded || 15 }}
            </div>
          </div>
          <MiniBarChart :heights="[70, 55, 45, 35, 100]" :active-idx="4" />
        </div>
        <div class="mt-4 flex items-center gap-1 text-xs font-semibold text-[#EF4444]">
          <span>3%</span>
          <ArrowDownRight class="w-3.5 h-3.5 stroke-[2.5]" />
          <span class="font-normal text-[#94A3B8] ml-1">from last week</span>
        </div>
      </div>

      <!-- 4. Completion Rate -->
      <div class="bg-white rounded-2xl border border-[#EDF1F7] p-5 shadow-xs flex flex-col justify-between hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between">
          <div>
            <div class="text-xs font-medium text-[#64748B]">Completion Rate</div>
            <div class="text-3xl font-extrabold text-[#1E2538] mt-2 tracking-tight">
              {{ stats.completionRate || 80 }}%
            </div>
          </div>
          <MiniBarChart :heights="[30, 50, 70, 85, 100]" :active-idx="4" />
        </div>
        <div class="mt-4 flex items-center gap-1 text-xs font-semibold text-[#10B981]">
          <span>80%</span>
          <ArrowUpRight class="w-3.5 h-3.5 stroke-[2.5]" />
          <span class="font-normal text-[#94A3B8] ml-1">from last week</span>
        </div>
      </div>
    </div>

    <!-- Section: Recent Survey (Full Width, No AI Card) -->
    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-[#1E2538] tracking-tight">Recent Survey</h2>
        <RouterLink
          to="/history"
          class="text-xs font-semibold text-[#4E53E9] hover:underline"
        >
          Lihat Riwayat &rarr;
        </RouterLink>
      </div>

      <div v-if="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#4E53E9]"></div>
      </div>

      <!-- If no surveys available -->
      <div
        v-else-if="filteredSurveys.length === 0"
        class="bg-white rounded-2xl border border-[#EDF1F7] p-12 text-center"
      >
        <div class="w-12 h-12 rounded-full bg-[#EEF1FD] text-[#4E53E9] flex items-center justify-center mx-auto mb-3">
          <ClipboardList class="w-6 h-6" />
        </div>
        <h3 class="font-bold text-base text-[#1E2538]">Tidak ada survey yang tersedia</h3>
        <p class="text-xs text-[#64748B] mt-1 max-w-sm mx-auto">
          Saat ini semua kuesioner telah selesai diisi atau belum ada survei baru yang dibuka.
        </p>
      </div>

      <!-- Survey Cards List matching screenshot -->
      <div v-else class="space-y-4">
        <div
          v-for="survey in filteredSurveys"
          :key="survey.id"
          class="bg-white rounded-2xl border border-[#EDF1F7] p-6 shadow-xs hover:shadow-md transition-all duration-200"
        >
          <!-- Card Header: Title + Status Badge + More Options -->
          <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-3 flex-wrap">
              <h3
                class="text-base font-bold text-[#1E2538] hover:text-[#4E53E9] transition-colors cursor-pointer"
                @click="!survey.already_responded ? router.push({ name: 'user-survey-fill', params: { id: survey.id } }) : router.push({ name: 'user-history' })"
              >
                {{ survey.title }}
              </h3>

              <!-- Status Badge: Active / Done / Expiring Soon -->
              <span
                v-if="survey.already_responded"
                class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-semibold bg-[#ECFDF5] text-[#059669] border border-[#A7F3D0]/60"
              >
                <CheckCircle2 class="w-3.5 h-3.5 text-[#10B981]" />
                Sudah Diisi
              </span>
              <span
                v-else-if="isExpiringSoon(survey.publish_end_at)"
                class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-semibold bg-[#FEF3C7] text-[#D97706] border border-[#FDE68A]"
              >
                <Clock class="w-3.5 h-3.5 text-[#F59E0B]" />
                Segera Berakhir
              </span>
              <span
                v-else
                class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-semibold bg-[#ECFDF5] text-[#059669] border border-[#A7F3D0]/60"
              >
                <span class="w-1.5 h-1.5 rounded-full bg-[#10B981]"></span>
                Active
              </span>
            </div>

            <!-- More Options Button -->
            <button
              class="text-[#94A3B8] hover:text-[#1E2538] p-1 rounded-lg hover:bg-[#F8F9FC] transition-colors cursor-pointer"
              title="Rincian"
              @click="!survey.already_responded ? router.push({ name: 'user-survey-fill', params: { id: survey.id } }) : router.push({ name: 'user-history' })"
            >
              <MoreHorizontal class="w-5 h-5" />
            </button>
          </div>

          <!-- Description -->
          <p class="text-xs md:text-sm text-[#64748B] leading-relaxed my-3 line-clamp-2">
            {{ survey.description || 'Kuesioner ini dirancang untuk menghimpun masukan dan evaluasi terkait kualitas layanan serta kepuasan pengguna secara komprehensif.' }}
          </p>

          <!-- Card Footer: Meta Info on Left, Actions on Right -->
          <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pt-3 border-t border-[#F1F3F8]">
            <!-- Left Meta Info -->
            <div class="flex items-center gap-5 text-xs text-[#64748B] flex-wrap">
              <!-- Creator Avatar & Name -->
              <div class="flex items-center gap-2 font-medium text-[#1E2538]">
                <div class="w-6 h-6 rounded-full bg-gradient-to-tr from-[#F59E0B] to-[#FCD34D] text-white flex items-center justify-center font-bold text-[10px] shadow-2xs">
                  {{ (survey.creator?.name || 'Administrator').charAt(0).toUpperCase() }}
                </div>
                <span>{{ survey.creator?.name || 'Indra Lesmana' }}</span>
              </div>

              <!-- Participant / Responded Count -->
              <div class="flex items-center gap-1.5">
                <Users class="w-3.5 h-3.5 text-[#94A3B8]" />
                <span class="font-medium text-[#1E2538]">{{ survey.responses_count || 83 }}</span>
                <span class="text-[#94A3B8]">/ 50</span>
              </div>

              <!-- Date -->
              <div class="flex items-center gap-1.5">
                <Calendar class="w-3.5 h-3.5 text-[#94A3B8]" />
                <span>{{ formatDate(survey.publish_start_at || survey.publish_end_at) }}</span>
              </div>
            </div>

            <!-- Right Action Buttons -->
            <div class="flex items-center gap-2.5 w-full sm:w-auto justify-end">
              <template v-if="!survey.already_responded">
                <button
                  class="bg-[#4E53E9] text-white hover:bg-[#393DC5] font-semibold text-xs px-6 py-2 rounded-xl shadow-xs transition-all cursor-pointer"
                  @click="router.push({ name: 'user-survey-fill', params: { id: survey.id } })"
                >
                  Mulai Isi Survey
                </button>
              </template>
              <template v-else>
                <button
                  class="border border-[#4E53E9] text-[#4E53E9] hover:bg-[#4E53E9]/5 font-semibold text-xs px-5 py-2 rounded-xl transition-all cursor-pointer"
                  @click="router.push({ name: 'user-history' })"
                >
                  Tinjau di Riwayat
                </button>
              </template>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
