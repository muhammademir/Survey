<script setup lang="ts">
import { ref, onMounted } from 'vue'
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
import {
  ArrowLeft,
  BarChart2,
  PieChart,
  Users,
  Calendar,
  Eye,
  X,
  ChevronLeft,
  ChevronRight,
  TrendingUp,
  FolderOpen,
} from 'lucide-vue-next'

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement)

const route = useRoute()
const router = useRouter()
const surveyId = route.params.id

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

const survey = ref<SurveyInfo | null>(null)
const categories = ref<CategoryResult[]>([])
const respondents = ref<Respondent[]>([])
const respondentPagination = ref({ current_page: 1, last_page: 1, total: 0 })

const activeTab = ref<'summary' | 'respondents'>('summary')
const chartType = ref<'bar' | 'doughnut'>('bar')
const loading = ref(true)
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

// Chart options
const chartColors = [
  'rgba(99, 102, 241, 0.85)',
  'rgba(236, 72, 153, 0.85)',
  'rgba(16, 185, 129, 0.85)',
  'rgba(245, 158, 11, 0.85)',
]
const chartBorders = [
  'rgb(99, 102, 241)',
  'rgb(236, 72, 153)',
  'rgb(16, 185, 129)',
  'rgb(245, 158, 11)',
]

function getChartData(q: QuestionResult) {
  return {
    labels: q.options.map((o) => `${o.label}: ${o.option_text.substring(0, 25)}${o.option_text.length > 25 ? '...' : ''}`),
    datasets: [
      {
        label: 'Jumlah Suara',
        backgroundColor: chartColors.slice(0, q.options.length),
        borderColor: chartBorders.slice(0, q.options.length),
        borderWidth: 1,
        borderRadius: 4,
        data: q.options.map((o) => o.count),
      },
    ],
  }
}

const barOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      callbacks: {
        label: (ctx: any) => ` ${ctx.raw} suara responden`,
      },
    },
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: { precision: 0, color: '#94a3b8' },
      grid: { color: 'rgba(255, 255, 255, 0.06)' },
    },
    x: {
      ticks: { color: '#94a3b8' },
      grid: { display: false },
    },
  },
}

const doughnutOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom' as const,
      labels: { color: '#94a3b8', font: { size: 11 } },
    },
    tooltip: {
      callbacks: {
        label: (ctx: any) => ` ${ctx.label}: ${ctx.raw} suara`,
      },
    },
  },
}

async function fetchSummary() {
  loading.value = true
  try {
    const res = await api.get(`/admin/surveys/${surveyId}/results`)
    survey.value = res.data.survey
    categories.value = res.data.categories
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal memuat hasil survey')
    router.push({ name: 'admin-surveys' })
  } finally {
    loading.value = false
  }
}

async function fetchRespondents(page = 1) {
  respondentsLoading.value = true
  try {
    const res = await api.get(`/admin/surveys/${surveyId}/respondents`, { params: { page } })
    respondents.value = res.data.data
    respondentPagination.value = {
      current_page: res.data.current_page,
      last_page: res.data.last_page,
      total: res.data.total,
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
    hour: '2-digit',
    minute: '2-digit',
  })
}

onMounted(async () => {
  await fetchSummary()
  await fetchRespondents(1)
})
</script>

<template>
  <div class="flex-1 space-y-6 p-8 pt-6">
    <!-- Topbar Navigation -->
    <div class="flex items-center gap-3">
      <Button variant="ghost" size="sm" class="gap-1.5 text-xs text-muted-foreground" @click="router.push({ name: 'admin-surveys' })">
        <ArrowLeft class="h-4 w-4" />
        <span>Kembali ke Daftar Survey</span>
      </Button>
    </div>

    <div v-if="loading" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
    </div>

    <template v-else-if="survey">
      <!-- Survey Header Card -->
      <Card class="border-border/70 shadow-sm bg-gradient-to-r from-card to-card/60">
        <CardContent class="p-6">
          <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div class="space-y-2 max-w-2xl">
              <div class="flex items-center gap-2">
                <Badge variant="outline" class="font-mono text-xs">ID #{{ survey.id }}</Badge>
                <Badge :variant="survey.status === 'published' ? 'success' : 'warning'">
                  {{ survey.status.toUpperCase() }}
                </Badge>
              </div>
              <h2 class="text-2xl font-bold tracking-tight text-foreground">{{ survey.title }}</h2>
              <p v-if="survey.description" class="text-sm text-muted-foreground">
                {{ survey.description }}
              </p>
              <div class="text-xs text-muted-foreground flex items-center gap-1.5 pt-1">
                <Calendar class="w-3.5 h-3.5 text-primary" />
                <span>Masa Tayang: {{ formatDate(survey.publish_start_at) }} s/d {{ formatDate(survey.publish_end_at) }}</span>
              </div>
            </div>

            <!-- Total Responden Stat -->
            <div class="rounded-xl border border-border/80 bg-background/50 p-4 text-center min-w-[160px] shadow-sm">
              <div class="text-3xl font-extrabold text-primary">{{ survey.total_responses }}</div>
              <div class="text-xs font-medium text-muted-foreground flex items-center justify-center gap-1 mt-1">
                <Users class="w-3.5 h-3.5" />
                <span>Total Responden</span>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Navigation Tabs & Chart Toggle -->
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-border/60 pb-3">
        <div class="flex items-center gap-2">
          <Button
            size="sm"
            :variant="activeTab === 'summary' ? 'default' : 'ghost'"
            class="gap-2 text-xs"
            @click="activeTab = 'summary'"
          >
            <BarChart2 class="h-4 w-4" />
            <span>Rekapitulasi & Visualisasi Grafik</span>
          </Button>
          <Button
            size="sm"
            :variant="activeTab === 'respondents' ? 'default' : 'ghost'"
            class="gap-2 text-xs"
            @click="activeTab = 'respondents'"
          >
            <Users class="h-4 w-4" />
            <span>Data Responden ({{ respondentPagination.total }})</span>
          </Button>
        </div>

        <div v-if="activeTab === 'summary'" class="flex items-center gap-1 bg-muted/40 p-1 rounded-lg border border-border/40">
          <Button
            size="sm"
            :variant="chartType === 'bar' ? 'secondary' : 'ghost'"
            class="h-7 text-xs gap-1"
            @click="chartType = 'bar'"
          >
            <BarChart2 class="w-3.5 h-3.5" />
            <span>Bar Chart</span>
          </Button>
          <Button
            size="sm"
            :variant="chartType === 'doughnut' ? 'secondary' : 'ghost'"
            class="h-7 text-xs gap-1"
            @click="chartType = 'doughnut'"
          >
            <PieChart class="w-3.5 h-3.5" />
            <span>Donut Chart</span>
          </Button>
        </div>
      </div>

      <!-- TAB 1: SUMMARY & VISUAL CHARTS -->
      <div v-if="activeTab === 'summary'" class="space-y-6">
        <div v-if="survey.total_responses === 0" class="text-center py-16">
          <div class="h-12 w-12 rounded-full bg-primary/10 flex items-center justify-center text-primary mx-auto mb-3">
            <Users class="h-6 w-6" />
          </div>
          <h3 class="text-base font-semibold text-foreground">Belum ada responden</h3>
          <p class="text-sm text-muted-foreground mt-1">Survey ini belum memiliki suara yang masuk dari peserta.</p>
        </div>

        <div v-else class="space-y-8">
          <div v-for="(cat, cIdx) in categories" :key="cat.id" class="space-y-4">
            <!-- Category Title -->
            <div class="flex items-center gap-2 border-b border-border/40 pb-2">
              <FolderOpen class="w-5 h-5 text-primary" />
              <div>
                <h3 class="text-lg font-bold text-foreground">Kategori {{ cIdx + 1 }}: {{ cat.name }}</h3>
                <p v-if="cat.description" class="text-xs text-muted-foreground">{{ cat.description }}</p>
              </div>
            </div>

            <!-- Question Cards with Table & Chart -->
            <div class="space-y-4">
              <Card v-for="(q, qIdx) in cat.questions" :key="q.id" class="border-border/70 shadow-sm">
                <CardHeader class="pb-3 border-b border-border/40 bg-muted/10">
                  <CardTitle class="text-base font-semibold">
                    {{ qIdx + 1 }}. {{ q.question_text }}
                  </CardTitle>
                </CardHeader>
                <CardContent class="p-6">
                  <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                    <!-- Table Section -->
                    <div class="rounded-lg border border-border/60 overflow-hidden shadow-xs">
                      <Table>
                        <TableHeader>
                          <TableRow>
                            <TableHead class="w-[60px]">Opsi</TableHead>
                            <TableHead>Jawaban Pilihan</TableHead>
                            <TableHead class="w-[70px] text-center">Suara</TableHead>
                            <TableHead class="w-[140px] text-right">Persentase</TableHead>
                          </TableRow>
                        </TableHeader>
                        <TableBody>
                          <TableRow v-for="opt in q.options" :key="opt.id">
                            <TableCell>
                              <Badge variant="default" class="w-6 h-6 rounded-md p-0 flex items-center justify-center text-xs font-bold">
                                {{ opt.label }}
                              </Badge>
                            </TableCell>
                            <TableCell class="font-medium text-xs text-foreground">{{ opt.option_text }}</TableCell>
                            <TableCell class="text-center font-bold text-sm">{{ opt.count }}</TableCell>
                            <TableCell class="text-right">
                              <div class="flex items-center justify-end gap-2">
                                <div class="w-20 bg-muted/60 h-2 rounded-full overflow-hidden">
                                  <div
                                    class="bg-primary h-full rounded-full transition-all duration-300"
                                    :style="{ width: opt.percentage + '%' }"
                                  />
                                </div>
                                <span class="text-xs font-semibold font-mono w-10 text-right">{{ opt.percentage }}%</span>
                              </div>
                            </TableCell>
                          </TableRow>
                        </TableBody>
                      </Table>
                    </div>

                    <!-- Chart Section -->
                    <div class="h-64 relative flex items-center justify-center p-2 bg-card/40 rounded-lg border border-border/40">
                      <Bar
                        v-if="chartType === 'bar'"
                        :data="getChartData(q)"
                        :options="barOptions"
                      />
                      <Doughnut
                        v-else
                        :data="getChartData(q)"
                        :options="doughnutOptions"
                      />
                    </div>
                  </div>
                </CardContent>
              </Card>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 2: RESPONDENTS LIST -->
      <div v-if="activeTab === 'respondents'">
        <Card class="border-border/70 shadow-sm overflow-hidden">
          <div v-if="respondentsLoading" class="flex justify-center items-center py-20">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
          </div>

          <template v-else-if="respondents.length === 0">
            <div class="py-16 text-center text-muted-foreground">
              Belum ada data partisipasi responden pada survey ini.
            </div>
          </template>

          <template v-else>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead class="w-[60px]">No</TableHead>
                  <TableHead class="w-[100px]">User ID</TableHead>
                  <TableHead>Nama Responden</TableHead>
                  <TableHead>Email</TableHead>
                  <TableHead>Waktu Partisipasi</TableHead>
                  <TableHead class="text-right">Aksi</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-for="(resp, idx) in respondents" :key="resp.id">
                  <TableCell class="text-xs text-muted-foreground">
                    {{ (respondentPagination.current_page - 1) * 15 + idx + 1 }}
                  </TableCell>
                  <TableCell>
                    <Badge variant="secondary" class="font-mono text-xs">#{{ resp.user?.id }}</Badge>
                  </TableCell>
                  <TableCell class="font-medium text-foreground">{{ resp.user?.name }}</TableCell>
                  <TableCell class="text-muted-foreground text-xs">{{ resp.user?.email }}</TableCell>
                  <TableCell class="text-xs text-muted-foreground">{{ formatDate(resp.created_at) }}</TableCell>
                  <TableCell class="text-right">
                    <Button
                      variant="outline"
                      size="sm"
                      class="gap-1.5 text-xs h-8"
                      @click="openDetailModal(resp)"
                    >
                      <Eye class="w-3.5 h-3.5" />
                      <span>Rincian Jawaban</span>
                    </Button>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>

            <!-- Pagination -->
            <div v-if="respondentPagination.last_page > 1" class="flex items-center justify-between p-4 border-t border-border/50 bg-muted/10">
              <div class="text-xs text-muted-foreground">
                Halaman {{ respondentPagination.current_page }} dari {{ respondentPagination.last_page }}
              </div>
              <div class="flex items-center gap-2">
                <Button
                  variant="outline"
                  size="sm"
                  class="gap-1 text-xs"
                  :disabled="respondentPagination.current_page === 1"
                  @click="fetchRespondents(respondentPagination.current_page - 1)"
                >
                  <ChevronLeft class="h-3.5 w-3.5" />
                  <span>Prev</span>
                </Button>
                <Button
                  variant="outline"
                  size="sm"
                  class="gap-1 text-xs"
                  :disabled="respondentPagination.current_page === respondentPagination.last_page"
                  @click="fetchRespondents(respondentPagination.current_page + 1)"
                >
                  <span>Next</span>
                  <ChevronRight class="h-3.5 w-3.5" />
                </Button>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <!-- Modal Detail Jawaban Responden -->
      <div
        v-if="showDetailModal && selectedRespondent"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-background/80 backdrop-blur-sm"
        @click.self="closeDetailModal"
      >
        <Card class="w-full max-w-2xl max-h-[85vh] flex flex-col shadow-2xl border-border/80 bg-card overflow-hidden">
          <CardHeader class="flex flex-row items-center justify-between border-b border-border/60 pb-4">
            <div>
              <CardTitle class="text-lg">Detail Jawaban Responden</CardTitle>
              <CardDescription>
                {{ selectedRespondent.user?.name }} ({{ selectedRespondent.user?.email }}) — {{ formatDate(selectedRespondent.created_at) }}
              </CardDescription>
            </div>
            <Button variant="ghost" size="icon" class="h-8 w-8" @click="closeDetailModal">
              <X class="h-4 w-4" />
            </Button>
          </CardHeader>

          <CardContent class="overflow-y-auto p-6 space-y-4 flex-1">
            <div v-if="!selectedRespondent.answers || selectedRespondent.answers.length === 0" class="text-sm text-muted-foreground text-center py-6">
              Tidak ada data rincian jawaban tersimpan.
            </div>
            <div
              v-for="(ans, aIdx) in selectedRespondent.answers"
              :key="ans.id"
              class="p-4 rounded-lg bg-muted/30 border border-border/60 space-y-2"
            >
              <div class="font-semibold text-sm text-foreground">
                {{ aIdx + 1 }}. {{ ans.question?.question_text }}
              </div>
              <div class="flex items-center gap-2">
                <Badge variant="default" class="w-6 h-6 rounded-md p-0 flex items-center justify-center text-xs font-bold">
                  {{ ans.option?.label }}
                </Badge>
                <span class="text-sm text-foreground font-medium">{{ ans.option?.option_text }}</span>
              </div>
            </div>
          </CardContent>

          <div class="p-4 border-t border-border/60 bg-muted/10 flex justify-end">
            <Button variant="secondary" size="sm" @click="closeDetailModal">
              Tutup
            </Button>
          </div>
        </Card>
      </div>
    </template>
  </div>
</template>
