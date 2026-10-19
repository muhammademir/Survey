<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
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
  History,
  Eye,
  X,
  ChevronLeft,
  ChevronRight,
  ClipboardCheck,
  Calendar,
  ArrowRight,
} from 'lucide-vue-next'

const router = useRouter()

interface Answer {
  id: number
  question: {
    id: number
    question_text: string
  }
  option: {
    id: number
    label: string
    option_text: string
  }
}

interface SurveyHistory {
  id: number
  survey_id: number
  created_at: string
  survey: {
    id: number
    title: string
    description: string | null
    publish_start_at: string | null
    publish_end_at: string | null
  }
  answers: Answer[]
}

const historyList = ref<SurveyHistory[]>([])
const loading = ref(true)
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })

// Modal detail jawaban
const selectedHistory = ref<SurveyHistory | null>(null)
const showDetailModal = ref(false)

function openDetailModal(item: SurveyHistory) {
  selectedHistory.value = item
  showDetailModal.value = true
}

function closeDetailModal() {
  selectedHistory.value = null
  showDetailModal.value = false
}

async function fetchHistory(page = 1) {
  loading.value = true
  try {
    const res = await api.get('/surveys/my-history', { params: { page } })
    historyList.value = res.data.data
    pagination.value = {
      current_page: res.data.current_page,
      last_page: res.data.last_page,
      total: res.data.total,
    }
  } catch {
    // silent
  } finally {
    loading.value = false
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

onMounted(() => fetchHistory())
</script>

<template>
  <div class="flex-1 space-y-6 p-8 pt-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-3xl font-bold tracking-tight text-foreground flex items-center gap-2">
          <span>Riwayat Partisipasi Survey</span>
          <History class="w-5 h-5 text-primary" />
        </h2>
        <p class="text-sm text-muted-foreground">
          Daftar seluruh kuesioner yang telah Anda isi beserta rincian jawaban yang telah Anda submit.
        </p>
      </div>
      <Button variant="outline" size="sm" class="gap-1.5 shadow-sm text-xs" @click="router.push({ name: 'user-dashboard' })">
        <span>Survey Tersedia</span>
        <ArrowRight class="w-3.5 h-3.5" />
      </Button>
    </div>

    <!-- Table Card -->
    <Card class="border-border/70 shadow-sm overflow-hidden">
      <div v-if="loading" class="flex justify-center items-center py-20">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
      </div>

      <template v-else-if="historyList.length === 0">
        <div class="flex flex-col items-center justify-center py-20 px-4 text-center">
          <div class="h-12 w-12 rounded-full bg-primary/10 flex items-center justify-center text-primary mb-3">
            <ClipboardCheck class="h-6 w-6" />
          </div>
          <h3 class="text-lg font-semibold text-foreground">Belum ada riwayat survey</h3>
          <p class="text-sm text-muted-foreground max-w-sm mt-1 mb-4">
            Anda belum pernah mengisi survey apapun. Buka menu Survey Tersedia untuk mulai berpartisipasi.
          </p>
          <Button class="gap-1.5" @click="router.push({ name: 'user-dashboard' })">
            <span>Lihat Survey Tersedia</span>
            <ArrowRight class="w-4 h-4" />
          </Button>
        </div>
      </template>

      <template v-else>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead class="w-[60px]">No</TableHead>
              <TableHead class="min-w-[280px]">Judul Survey</TableHead>
              <TableHead>Waktu Partisipasi</TableHead>
              <TableHead class="text-center">Jumlah Jawaban</TableHead>
              <TableHead class="text-right">Aksi</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="(item, idx) in historyList" :key="item.id">
              <TableCell class="text-xs text-muted-foreground">
                {{ (pagination.current_page - 1) * 15 + idx + 1 }}
              </TableCell>
              <TableCell>
                <div class="font-semibold text-foreground text-sm">
                  {{ item.survey?.title || 'Survey #' + item.survey_id }}
                </div>
                <div v-if="item.survey?.description" class="text-xs text-muted-foreground line-clamp-1 max-w-md mt-0.5">
                  {{ item.survey.description }}
                </div>
              </TableCell>
              <TableCell class="text-xs text-muted-foreground">
                <div class="flex items-center gap-1.5">
                  <Calendar class="w-3.5 h-3.5 text-primary" />
                  <span>{{ formatDate(item.created_at) }}</span>
                </div>
              </TableCell>
              <TableCell class="text-center">
                <Badge variant="secondary" class="text-xs">
                  {{ item.answers?.length || 0 }} Terjawab
                </Badge>
              </TableCell>
              <TableCell class="text-right">
                <Button
                  variant="outline"
                  size="sm"
                  class="gap-1.5 text-xs h-8"
                  @click="openDetailModal(item)"
                >
                  <Eye class="w-3.5 h-3.5" />
                  <span>Lihat Jawaban Saya</span>
                </Button>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="flex items-center justify-between p-4 border-t border-border/50 bg-muted/10">
          <div class="text-xs text-muted-foreground">
            Halaman <span class="font-medium text-foreground">{{ pagination.current_page }}</span> dari <span class="font-medium text-foreground">{{ pagination.last_page }}</span>
          </div>
          <div class="flex items-center gap-2">
            <Button
              variant="outline"
              size="sm"
              class="gap-1 text-xs"
              :disabled="pagination.current_page === 1"
              @click="fetchHistory(pagination.current_page - 1)"
            >
              <ChevronLeft class="h-3.5 w-3.5" />
              <span>Sebelumnya</span>
            </Button>
            <Button
              variant="outline"
              size="sm"
              class="gap-1 text-xs"
              :disabled="pagination.current_page === pagination.last_page"
              @click="fetchHistory(pagination.current_page + 1)"
            >
              <span>Berikutnya</span>
              <ChevronRight class="h-3.5 w-3.5" />
            </Button>
          </div>
        </div>
      </template>
    </Card>

    <!-- Modal Detail Jawaban Saya -->
    <div
      v-if="showDetailModal && selectedHistory"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-background/80 backdrop-blur-sm"
      @click.self="closeDetailModal"
    >
      <Card class="w-full max-w-2xl max-h-[85vh] flex flex-col shadow-2xl border-border/80 bg-card overflow-hidden">
        <CardHeader class="flex flex-row items-center justify-between border-b border-border/60 pb-4">
          <div>
            <CardTitle class="text-lg">Jawaban Saya</CardTitle>
            <CardDescription class="line-clamp-1">
              {{ selectedHistory.survey?.title }} — Diisi pada {{ formatDate(selectedHistory.created_at) }}
            </CardDescription>
          </div>
          <Button variant="ghost" size="icon" class="h-8 w-8" @click="closeDetailModal">
            <X class="h-4 w-4" />
          </Button>
        </CardHeader>

        <CardContent class="overflow-y-auto p-6 space-y-4 flex-1">
          <div v-if="!selectedHistory.answers || selectedHistory.answers.length === 0" class="text-sm text-muted-foreground text-center py-6">
            Tidak ada rincian jawaban tersimpan.
          </div>
          <div
            v-for="(ans, aIdx) in selectedHistory.answers"
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
  </div>
</template>
