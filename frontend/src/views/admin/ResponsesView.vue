<script setup lang="ts">
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { toast } from '@/stores/toast'
import api from '@/services/api'
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  ArcElement,
} from 'chart.js'
import { Bar, Doughnut } from 'vue-chartjs'
import {
  Users,
  Calendar,
  BarChart2,
  PieChart,
  Eye,
  X,
  ClipboardCheck,
  ChevronDown,
  TrendingUp,
  FolderOpen,
  ArrowRight,
  ChevronLeft,
  ChevronRight,
} from 'lucide-vue-next'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement)

const route = useRoute()
const router = useRouter()

interface SurveyOption {
  id: number
  title: string
  status: string
  responses_count: number
  publish_start_at: string | null
  publish_end_at: string | null
}

interface OptionResult {
  id: number
  label: string
  option_text: string
  count: number
  percentage: number
}

interface QuestionResult {
  id: number
  question_text: string
  options: OptionResult[]
}

interface CategoryResult {
  id: number
  name: string
  description: string | null
  questions: QuestionResult[]
}

interface SurveyInfo {
  id: number
  title: string
  description: string | null
  status: string
  total_responses: number
  publish_start_at: string | null
  publish_end_at: string | null
}

interface RespondentAnswer {
  id: number
  question: { question_text: string }
  option: { label: string; option_text: string }
}

interface Respondent {
  id: number
  created_at: string
  user: {
    id: number
    name: string
    email: string
  }
  answers: RespondentAnswer[]
}

const surveysList = ref<SurveyOption[]>([])
const selectedSurveyId = ref<number | null>(null)
const selectedSurvey = ref<SurveyInfo | null>(null)
const categories = ref<CategoryResult[]>([])
const respondents = ref<Respondent[]>([])
const respondentPagination = ref({ current_page: 1, last_page: 1, total: 0 })

const activeTab = ref<'summary' | 'respondents'>('summary')
const chartType = ref<'bar' | 'doughnut'>('bar')
const loadingSurveys = ref(true)
const loadingDetails = ref(false)
const respondentsLoading = ref(false)

// Modal detail jawaban responden
const selectedRespondent = ref<Respondent | null>(null)
const showDetailModal = ref(false)

function openDetailModal(resp: Respondent) {
  selectedRespondent.value = resp
  showDetailModal.value = true
}

function closeDetailModal() {
  selectedRespondent.value = null
  showDetailModal.value = false
}

// Chart configuration
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom' as const,
      labels: {
        color: '#64748B',
        boxWidth: 12,
        font: { size: 11, family: 'Plus Jakarta Sans' },
      },
    },
    tooltip: {
      backgroundColor: '#1E2538',
      titleColor: '#FFFFFF',
      bodyColor: '#FFFFFF',
      borderColor: '#EDF1F7',
      borderWidth: 1,
      padding: 10,
      cornerRadius: 8,
    },
  },
  scales: {
    x: {
      grid: { display: false },
      ticks: { color: '#64748B', font: { size: 10, family: 'Plus Jakarta Sans' } },
    },
    y: {
      grid: { color: '#F1F3F8' },
      ticks: { color: '#64748B', font: { size: 10, family: 'Plus Jakarta Sans' }, stepSize: 1 },
      beginAtZero: true,
    },
  },
}

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom' as const,
      labels: {
        color: '#64748B',
        boxWidth: 12,
        font: { size: 11, family: 'Plus Jakarta Sans' },
      },
    },
    tooltip: {
      backgroundColor: '#1E2538',
      titleColor: '#FFFFFF',
      bodyColor: '#FFFFFF',
      borderColor: '#EDF1F7',
      borderWidth: 1,
      padding: 10,
      cornerRadius: 8,
    },
  },
}

const palette = [
  '#4E53E9',
  '#06B6D4',
  '#10B981',
  '#F59E0B',
  '#8B5CF6',
  '#EC4899',
  '#3B82F6',
  '#84CC16',
]

function getChartData(q: QuestionResult) {
  return {
    labels: q.options.map((o) => `${o.label}. ${o.option_text}`),
    datasets: [
      {
        label: 'Jumlah Pemilih',
        data: q.options.map((o) => o.count),
        backgroundColor: q.options.map((_, i) => palette[i % palette.length]),
        borderRadius: 8,
      },
    ],
  }
}

async function fetchSurveysList() {
  loadingSurveys.value = true
  try {
    const res = await api.get('/admin/surveys?per_page=50')
    const data: SurveyOption[] = res.data.data || []
    surveysList.value = data

    // Check if query parameter survey_id exists
    const queryId = route.query.survey ? Number(route.query.survey) : null
    if (queryId && data.some((s) => s.id === queryId)) {
      selectedSurveyId.value = queryId
    } else if (data.length > 0) {
      // Pick first survey that has responses, or simply first survey
      const withResp = data.find((s) => s.responses_count > 0)
      selectedSurveyId.value = withResp?.id ?? data[0]?.id ?? null
    }
  } catch {
    toast.error('Gagal memuat daftar survei')
  } finally {
    loadingSurveys.value = false
  }
}

async function loadSurveyResults(id: number) {
  loadingDetails.value = true
  try {
    const res = await api.get(`/admin/surveys/${id}/results`)
    selectedSurvey.value = res.data.survey
    categories.value = res.data.categories || []
    await fetchRespondents(id, 1)
  } catch {
    toast.error('Gagal memuat hasil kuesioner')
  } finally {
    loadingDetails.value = false
  }
}

async function fetchRespondents(surveyId: number, page = 1) {
  respondentsLoading.value = true
  try {
    const res = await api.get(`/admin/surveys/${surveyId}/respondents?page=${page}`)
    respondents.value = res.data.data || []
    respondentPagination.value = {
      current_page: res.data.current_page || 1,
      last_page: res.data.last_page || 1,
      total: res.data.total || 0,
    }
  } catch {
    // silent
  } finally {
    respondentsLoading.value = false
  }
}

function formatDate(d: string | null) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}

function formatDateTime(d: string | null) {
  if (!d) return '—'
  return new Date(d).toLocaleString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

watch(selectedSurveyId, (newId) => {
  if (newId) {
    loadSurveyResults(newId)
  }
})

onMounted(() => {
  fetchSurveysList()
})
</script>

<template>
  <div class="p-8 max-w-7xl mx-auto space-y-6 font-sans">
    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-[#1E2538] tracking-tight">
          Responses & Hasil Survei
        </h1>
        <p class="text-xs sm:text-sm text-[#64748B] mt-1">
          Pantau tanggapan kuesioner, grafik visual distribusi jawaban, serta rincian partisipasi responden.
        </p>
      </div>

      <!-- Quick Selector Dropdown -->
      <div v-if="surveysList.length > 0" class="flex items-center gap-2">
        <label class="text-xs font-bold text-[#64748B] hidden sm:block">Pilih Survei:</label>
        <div class="relative">
          <select
            v-model="selectedSurveyId"
            class="h-10 pl-3 pr-8 rounded-xl border border-[#EDF1F7] bg-white text-xs font-semibold text-[#1E2538] focus:outline-none focus:border-[#4E53E9] shadow-2xs appearance-none cursor-pointer"
          >
            <option v-for="s in surveysList" :key="s.id" :value="s.id">
              {{ s.title }} ({{ s.responses_count }} respon)
            </option>
          </select>
          <ChevronDown class="w-4 h-4 text-[#94A3B8] absolute right-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loadingSurveys" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#4E53E9]"></div>
    </div>

    <!-- Empty State if no surveys exist -->
    <div
      v-else-if="surveysList.length === 0"
      class="bg-white rounded-3xl border border-[#EDF1F7] p-12 text-center shadow-xs"
    >
      <div class="w-12 h-12 rounded-2xl bg-[#EEF1FD] text-[#4E53E9] flex items-center justify-center mx-auto mb-3">
        <ClipboardCheck class="w-6 h-6" />
      </div>
      <h3 class="font-bold text-base text-[#1E2538]">Belum ada survei yang dibuat</h3>
      <p class="text-xs text-[#64748B] mt-1 max-w-sm mx-auto">
        Silakan buat dan publikasikan survei terlebih dahulu untuk mulai mengumpulkan tanggapan responden.
      </p>
      <button
        class="mt-4 px-4 py-2 bg-[#4E53E9] text-white rounded-xl text-xs font-semibold hover:bg-[#393DC5] cursor-pointer"
        @click="router.push({ name: 'admin-survey-create' })"
      >
        Buat Survei Baru
      </button>
    </div>

    <!-- Main Content when surveys exist -->
    <template v-else>
      <!-- Survey Summary Header Banner -->
      <div
        v-if="selectedSurvey"
        class="bg-white rounded-2xl border border-[#EDF1F7] p-6 shadow-xs flex flex-col md:flex-row items-start md:items-center justify-between gap-6"
      >
        <div class="space-y-2">
          <div class="flex items-center gap-2.5 flex-wrap">
            <span
              v-if="selectedSurvey.status === 'published'"
              class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-semibold bg-[#ECFDF5] text-[#059669] border border-[#A7F3D0]/60"
            >
              <span class="w-1.5 h-1.5 rounded-full bg-[#10B981]"></span>
              Aktif Tayang
            </span>
            <span
              v-else
              class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-semibold bg-[#F1F5F9] text-[#64748B] border border-[#CBD5E1]/60"
            >
              <span class="w-1.5 h-1.5 rounded-full bg-[#94A3B8]"></span>
              Draft
            </span>

            <span class="text-xs text-[#94A3B8] flex items-center gap-1">
              <Calendar class="w-3.5 h-3.5" />
              {{ formatDate(selectedSurvey.publish_start_at) }} — {{ formatDate(selectedSurvey.publish_end_at) }}
            </span>
          </div>

          <h2 class="text-xl font-bold text-[#1E2538] tracking-tight">
            {{ selectedSurvey.title }}
          </h2>
          <p class="text-xs text-[#64748B] max-w-2xl line-clamp-2">
            {{ selectedSurvey.description || 'Tidak ada deskripsi rincian kuesioner.' }}
          </p>
        </div>

        <!-- Metric Pill -->
        <div class="flex items-center gap-4 shrink-0 bg-[#F8F9FC] px-5 py-3 rounded-2xl border border-[#EDF1F7]">
          <div class="w-10 h-10 rounded-xl bg-[#EEF1FD] text-[#4E53E9] flex items-center justify-center font-bold">
            <Users class="w-5 h-5" />
          </div>
          <div>
            <div class="text-[11px] font-semibold text-[#64748B]">Total Responden Masuk</div>
            <div class="text-2xl font-extrabold text-[#1E2538]">
              {{ selectedSurvey.total_responses || 0 }}
              <span class="text-xs font-normal text-[#94A3B8]">orang</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Navigation Tabs: Grafik vs Responden -->
      <div class="flex items-center justify-between border-b border-[#EDF1F7] pt-2">
        <div class="flex items-center gap-2">
          <button
            class="px-5 py-2.5 rounded-t-xl text-xs font-bold transition-all border-b-2 cursor-pointer flex items-center gap-2"
            :class="
              activeTab === 'summary'
                ? 'border-[#4E53E9] text-[#4E53E9] bg-white shadow-xs'
                : 'border-transparent text-[#64748B] hover:text-[#1E2538]'
            "
            @click="activeTab = 'summary'"
          >
            <BarChart2 class="w-4 h-4" />
            <span>Grafik & Analisis Hasil</span>
          </button>

          <button
            class="px-5 py-2.5 rounded-t-xl text-xs font-bold transition-all border-b-2 cursor-pointer flex items-center gap-2"
            :class="
              activeTab === 'respondents'
                ? 'border-[#4E53E9] text-[#4E53E9] bg-white shadow-xs'
                : 'border-transparent text-[#64748B] hover:text-[#1E2538]'
            "
            @click="activeTab = 'respondents'"
          >
            <Users class="w-4 h-4" />
            <span>Daftar Responden ({{ selectedSurvey?.total_responses || 0 }})</span>
          </button>
        </div>

        <!-- Chart Type Switcher (only on summary tab) -->
        <div v-if="activeTab === 'summary'" class="flex items-center gap-1.5 pb-2">
          <button
            class="px-2.5 py-1 rounded-lg text-xs font-semibold flex items-center gap-1 cursor-pointer transition-all"
            :class="
              chartType === 'bar'
                ? 'bg-white border border-[#EDF1F7] text-[#4E53E9] shadow-2xs'
                : 'text-[#94A3B8] hover:text-[#1E2538]'
            "
            @click="chartType = 'bar'"
          >
            <BarChart2 class="w-3.5 h-3.5" />
            <span>Batang</span>
          </button>
          <button
            class="px-2.5 py-1 rounded-lg text-xs font-semibold flex items-center gap-1 cursor-pointer transition-all"
            :class="
              chartType === 'doughnut'
                ? 'bg-white border border-[#EDF1F7] text-[#4E53E9] shadow-2xs'
                : 'text-[#94A3B8] hover:text-[#1E2538]'
            "
            @click="chartType = 'doughnut'"
          >
            <PieChart class="w-3.5 h-3.5" />
            <span>Doughnut</span>
          </button>
        </div>
      </div>

      <!-- Loading Details -->
      <div v-if="loadingDetails" class="flex justify-center items-center py-20">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#4E53E9]"></div>
      </div>

      <!-- TAB 1: SUMMARY & CHARTS -->
      <div v-else-if="activeTab === 'summary'" class="space-y-6">
        <div
          v-if="!selectedSurvey?.total_responses"
          class="bg-white rounded-2xl border border-[#EDF1F7] p-12 text-center"
        >
          <div class="w-12 h-12 rounded-full bg-[#F8F9FC] text-[#94A3B8] flex items-center justify-center mx-auto mb-3">
            <Users class="w-6 h-6" />
          </div>
          <h3 class="font-bold text-base text-[#1E2538]">Belum ada data respon</h3>
          <p class="text-xs text-[#64748B] mt-1 max-w-sm mx-auto">
            Survei ini belum memiliki responden yang mengisi. Grafik analitik akan otomatis terisi saat responden mulai mengirimkan jawaban.
          </p>
        </div>

        <!-- Render Question Charts per Category -->
        <div v-else v-for="cat in categories" :key="cat.id" class="space-y-4">
          <div class="border-b border-[#F1F3F8] pb-2">
            <h3 class="text-base font-bold text-[#1E2538]">{{ cat.name }}</h3>
            <p v-if="cat.description" class="text-xs text-[#64748B]">{{ cat.description }}</p>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <div
              v-for="(q, qIndex) in cat.questions"
              :key="q.id"
              class="bg-white rounded-2xl border border-[#EDF1F7] p-6 shadow-xs flex flex-col justify-between"
            >
              <div>
                <span class="text-[11px] font-bold text-[#4E53E9] bg-[#EEF1FD] px-2 py-0.5 rounded-md">
                  Pertanyaan #{{ qIndex + 1 }}
                </span>
                <h4 class="font-bold text-sm text-[#1E2538] mt-2 leading-snug">
                  {{ q.question_text }}
                </h4>
              </div>

              <!-- Chart Visual -->
              <div class="h-56 mt-4">
                <Bar
                  v-if="chartType === 'bar'"
                  :data="getChartData(q)"
                  :options="chartOptions"
                />
                <Doughnut
                  v-else
                  :data="getChartData(q)"
                  :options="doughnutOptions"
                />
              </div>

              <!-- Options Percentage Table -->
              <div class="mt-4 pt-3 border-t border-[#F1F3F8] space-y-1.5">
                <div
                  v-for="(opt, optIndex) in q.options"
                  :key="opt.id"
                  class="flex items-center justify-between text-xs"
                >
                  <div class="flex items-center gap-2 truncate pr-2">
                    <span
                      class="w-2.5 h-2.5 rounded-full shrink-0"
                      :style="{ backgroundColor: palette[optIndex % palette.length] }"
                    />
                    <span class="text-[#1E2538] font-medium truncate">
                      {{ opt.label }}. {{ opt.option_text }}
                    </span>
                  </div>
                  <div class="flex items-center gap-2 shrink-0 text-[#64748B]">
                    <span class="font-bold text-[#1E2538]">{{ opt.count }}</span>
                    <span>({{ opt.percentage }}%)</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 2: RESPONDENTS LIST -->
      <div v-else-if="activeTab === 'respondents'" class="space-y-4">
        <div
          v-if="respondents.length === 0"
          class="bg-white rounded-2xl border border-[#EDF1F7] p-12 text-center"
        >
          <div class="w-12 h-12 rounded-full bg-[#F8F9FC] text-[#94A3B8] flex items-center justify-center mx-auto mb-3">
            <Users class="w-6 h-6" />
          </div>
          <h3 class="font-bold text-base text-[#1E2538]">Belum ada responden</h3>
          <p class="text-xs text-[#64748B] mt-1 max-w-sm mx-auto">
            Daftar responden yang telah berpartisipasi akan ditampilkan di sini.
          </p>
        </div>

        <div v-else class="bg-white rounded-2xl border border-[#EDF1F7] shadow-xs overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead class="bg-[#F8F9FC] border-b border-[#EDF1F7] text-[#64748B] font-bold uppercase tracking-wider">
                <tr>
                  <th class="px-6 py-3.5">ID</th>
                  <th class="px-6 py-3.5">Nama Responden</th>
                  <th class="px-6 py-3.5">Email</th>
                  <th class="px-6 py-3.5">Tanggal Mengisi</th>
                  <th class="px-6 py-3.5 text-right">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-[#F1F3F8]">
                <tr
                  v-for="resp in respondents"
                  :key="resp.id"
                  class="hover:bg-[#F8F9FC] transition-colors"
                >
                  <td class="px-6 py-4 font-mono text-[#94A3B8]">#{{ resp.id }}</td>
                  <td class="px-6 py-4 font-bold text-[#1E2538] flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-full bg-gradient-to-tr from-[#4E53E9] to-[#818CF8] text-white flex items-center justify-center font-bold text-xs shrink-0">
                      {{ resp.user?.name?.charAt(0).toUpperCase() || 'U' }}
                    </div>
                    <span>{{ resp.user?.name || 'Responden Anonim' }}</span>
                  </td>
                  <td class="px-6 py-4 text-[#64748B]">{{ resp.user?.email || '—' }}</td>
                  <td class="px-6 py-4 text-[#64748B]">{{ formatDateTime(resp.created_at) }}</td>
                  <td class="px-6 py-4 text-right">
                    <button
                      class="px-3 py-1.5 rounded-lg border border-[#4E53E9] text-[#4E53E9] hover:bg-[#4E53E9] hover:text-white font-semibold text-xs transition-all cursor-pointer shadow-2xs inline-flex items-center gap-1.5"
                      @click="openDetailModal(resp)"
                    >
                      <Eye class="w-3.5 h-3.5" />
                      <span>Rincian Jawaban</span>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div
            v-if="respondentPagination.last_page > 1"
            class="px-6 py-3 border-t border-[#EDF1F7] flex items-center justify-between text-xs text-[#64748B]"
          >
            <span>Total {{ respondentPagination.total }} responden</span>
            <div class="flex items-center gap-2">
              <button
                class="px-2.5 py-1 rounded-lg border border-[#EDF1F7] hover:bg-[#F8F9FC] disabled:opacity-40 cursor-pointer"
                :disabled="respondentPagination.current_page === 1"
                @click="selectedSurveyId && fetchRespondents(selectedSurveyId, respondentPagination.current_page - 1)"
              >
                <ChevronLeft class="w-3.5 h-3.5" />
              </button>
              <span>{{ respondentPagination.current_page }} dari {{ respondentPagination.last_page }}</span>
              <button
                class="px-2.5 py-1 rounded-lg border border-[#EDF1F7] hover:bg-[#F8F9FC] disabled:opacity-40 cursor-pointer"
                :disabled="respondentPagination.current_page === respondentPagination.last_page"
                @click="selectedSurveyId && fetchRespondents(selectedSurveyId, respondentPagination.current_page + 1)"
              >
                <ChevronRight class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- Modal Detail Jawaban Responden -->
    <div
      v-if="showDetailModal && selectedRespondent"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-xs animate-in fade-in duration-150"
    >
      <div class="bg-white rounded-3xl max-w-lg w-full max-h-[85vh] flex flex-col shadow-2xl border border-[#EDF1F7] overflow-hidden">
        <!-- Modal Header -->
        <div class="px-6 py-5 border-b border-[#F1F3F8] flex items-center justify-between bg-white">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-[#4E53E9] to-[#818CF8] text-white flex items-center justify-center font-bold text-sm shrink-0">
              {{ selectedRespondent.user?.name?.charAt(0).toUpperCase() }}
            </div>
            <div>
              <h3 class="font-bold text-sm text-[#1E2538]">{{ selectedRespondent.user?.name }}</h3>
              <p class="text-xs text-[#64748B]">{{ selectedRespondent.user?.email }} &bull; {{ formatDateTime(selectedRespondent.created_at) }}</p>
            </div>
          </div>
          <button
            class="p-1 rounded-lg text-[#94A3B8] hover:text-[#1E2538] hover:bg-[#F8F9FC] cursor-pointer"
            @click="closeDetailModal"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Modal Answers List -->
        <div class="p-6 overflow-y-auto space-y-4">
          <div
            v-for="(ans, idx) in selectedRespondent.answers"
            :key="ans.id"
            class="p-4 rounded-2xl bg-[#F8F9FC] border border-[#EDF1F7] space-y-2 text-xs"
          >
            <div class="font-bold text-[#1E2538] flex items-start gap-2">
              <span class="text-[#4E53E9]">Q{{ idx + 1 }}.</span>
              <span>{{ ans.question?.question_text }}</span>
            </div>
            <div class="pl-5 flex items-center gap-2 font-semibold text-[#059669]">
              <span class="w-1.5 h-1.5 rounded-full bg-[#10B981]"></span>
              <span>{{ ans.option?.label }}. {{ ans.option?.option_text }}</span>
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-4 border-t border-[#F1F3F8] flex justify-end bg-white">
          <button
            class="px-5 py-2 rounded-xl bg-[#1E2538] text-white hover:bg-[#334155] font-semibold text-xs transition-colors cursor-pointer"
            @click="closeDetailModal"
          >
            Tutup
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
