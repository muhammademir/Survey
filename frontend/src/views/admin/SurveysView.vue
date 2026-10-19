<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { toast } from '@/stores/toast'
import api from '@/services/api'
import { Card, CardContent } from '@/components/ui/card'
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
  Plus,
  BarChart2,
  Pencil,
  Trash2,
  Rocket,
  Calendar,
  ClipboardList,
  ChevronLeft,
  ChevronRight,
} from 'lucide-vue-next'

const router = useRouter()

interface Survey {
  id: number
  title: string
  status: string
  publish_start_at: string | null
  publish_end_at: string | null
  responses_count: number
  created_at: string
  creator?: { name: string }
}

const surveys = ref<Survey[]>([])
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(true)
const statusFilter = ref('')

async function fetchSurveys(page = 1) {
  loading.value = true
  try {
    const params: any = { page }
    if (statusFilter.value) params.status = statusFilter.value
    const res = await api.get('/admin/surveys', { params })
    surveys.value = res.data.data
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

async function publishSurvey(survey: Survey) {
  if (!confirm(`Publish survey "${survey.title}"? Setelah di-publish, survey tidak dapat diedit lagi untuk menjaga integritas data.`)) return
  try {
    const res = await api.patch(`/admin/surveys/${survey.id}/publish`)
    toast.success(res.data?.message || 'Survey berhasil dipublikasikan!')
    await fetchSurveys(pagination.value.current_page)
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal mempublikasikan survey')
  }
}

async function deleteSurvey(survey: Survey) {
  if (!confirm(`Hapus survey "${survey.title}" beserta seluruh data pertanyaannya?`)) return
  try {
    const res = await api.delete(`/admin/surveys/${survey.id}`)
    toast.success(res.data?.message || 'Survey berhasil dihapus!')
    await fetchSurveys(pagination.value.current_page)
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal menghapus survey')
  }
}

function getSurveyBadge(survey: Survey) {
  if (survey.status === 'draft') {
    return { label: 'Draft', variant: 'warning' as const }
  }
  const now = new Date()
  if (survey.publish_end_at && new Date(survey.publish_end_at) < now) {
    return { label: 'Kadaluarsa', variant: 'destructive' as const }
  }
  if (survey.publish_start_at && new Date(survey.publish_start_at) > now) {
    return { label: 'Terjadwal', variant: 'info' as const }
  }
  return { label: 'Aktif Tayang', variant: 'success' as const }
}

function formatDate(d: string | null) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

onMounted(() => fetchSurveys())
</script>

<template>
  <div class="flex-1 space-y-6 p-8 pt-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-3xl font-bold tracking-tight text-foreground">Kelola Survey & Kuesioner</h2>
        <p class="text-sm text-muted-foreground">
          Buat, ubah, terbitkan kuisioner multi-kategori, serta pantau statistik hasil responden.
        </p>
      </div>
      <Button class="gap-2 shadow-sm" @click="router.push({ name: 'admin-survey-create' })">
        <Plus class="h-4 w-4" />
        <span>Buat Survey Baru</span>
      </Button>
    </div>

    <!-- Filter Card -->
    <Card class="border-border/70 shadow-sm">
      <CardContent class="p-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <span class="text-xs font-medium text-muted-foreground">Filter Status:</span>
          <Button
            size="sm"
            :variant="statusFilter === '' ? 'default' : 'ghost'"
            class="text-xs h-8"
            @click="statusFilter = ''; fetchSurveys(1)"
          >
            Semua ({{ pagination.total }})
          </Button>
          <Button
            size="sm"
            :variant="statusFilter === 'draft' ? 'default' : 'ghost'"
            class="text-xs h-8"
            @click="statusFilter = 'draft'; fetchSurveys(1)"
          >
            Draft
          </Button>
          <Button
            size="sm"
            :variant="statusFilter === 'published' ? 'default' : 'ghost'"
            class="text-xs h-8"
            @click="statusFilter = 'published'; fetchSurveys(1)"
          >
            Terpublikasi
          </Button>
        </div>
      </CardContent>
    </Card>

    <!-- Table Card -->
    <Card class="border-border/70 shadow-sm overflow-hidden">
      <div v-if="loading" class="flex justify-center items-center py-20">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
      </div>

      <template v-else-if="surveys.length === 0">
        <div class="flex flex-col items-center justify-center py-16 px-4 text-center">
          <div class="h-12 w-12 rounded-full bg-primary/10 flex items-center justify-center text-primary mb-3">
            <ClipboardList class="h-6 w-6" />
          </div>
          <h3 class="text-lg font-semibold text-foreground">Belum ada survey ditemukan</h3>
          <p class="text-sm text-muted-foreground max-w-sm mt-1 mb-4">
            Mulai buat survey pertama Anda dengan kategori pertanyaan pilihan ganda (A, B, C, D).
          </p>
          <Button class="gap-2" @click="router.push({ name: 'admin-survey-create' })">
            <Plus class="h-4 w-4" />
            <span>Buat Survey Pertama</span>
          </Button>
        </div>
      </template>

      <template v-else>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead class="w-[80px]">ID</TableHead>
              <TableHead class="min-w-[240px]">Judul Survey</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Masa Tayang</TableHead>
              <TableHead class="text-center">Responden</TableHead>
              <TableHead>Dibuat</TableHead>
              <TableHead class="text-right">Aksi</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-for="survey in surveys" :key="survey.id">
              <TableCell class="font-mono text-xs text-muted-foreground">
                #{{ survey.id }}
              </TableCell>
              <TableCell>
                <div class="font-semibold text-foreground text-sm line-clamp-1">{{ survey.title }}</div>
                <div class="text-[11px] text-muted-foreground flex items-center gap-1 mt-0.5">
                  <Calendar class="w-3 h-3" />
                  <span>Mulai: {{ formatDate(survey.publish_start_at) }}</span>
                </div>
              </TableCell>
              <TableCell>
                <Badge :variant="getSurveyBadge(survey).variant" class="text-xs">
                  {{ getSurveyBadge(survey).label }}
                </Badge>
              </TableCell>
              <TableCell class="text-xs text-muted-foreground">
                {{ formatDate(survey.publish_start_at) }} — {{ formatDate(survey.publish_end_at) }}
              </TableCell>
              <TableCell class="text-center">
                <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-semibold bg-muted text-foreground">
                  {{ survey.responses_count }}
                </span>
              </TableCell>
              <TableCell class="text-xs text-muted-foreground">
                {{ formatDate(survey.created_at) }}
              </TableCell>
              <TableCell class="text-right">
                <div class="flex items-center justify-end gap-1.5">
                  <!-- Draft Actions -->
                  <Button
                    v-if="survey.status === 'draft'"
                    variant="outline"
                    size="sm"
                    class="h-8 gap-1 text-xs"
                    @click="router.push({ name: 'admin-survey-edit', params: { id: survey.id } })"
                  >
                    <Pencil class="h-3.5 w-3.5" />
                    <span>Edit</span>
                  </Button>
                  <Button
                    v-if="survey.status === 'draft'"
                    variant="success"
                    size="sm"
                    class="h-8 gap-1 text-xs"
                    @click="publishSurvey(survey)"
                  >
                    <Rocket class="h-3.5 w-3.5" />
                    <span>Publish</span>
                  </Button>

                  <!-- Published Actions -->
                  <Button
                    v-if="survey.status === 'published'"
                    variant="default"
                    size="sm"
                    class="h-8 gap-1.5 text-xs shadow-sm"
                    @click="router.push({ name: 'admin-survey-results', params: { id: survey.id } })"
                  >
                    <BarChart2 class="h-3.5 w-3.5" />
                    <span>Lihat Hasil & Grafik</span>
                  </Button>

                  <Button
                    variant="ghost"
                    size="icon"
                    class="h-8 w-8 text-destructive hover:bg-destructive/10"
                    title="Hapus Survey"
                    @click="deleteSurvey(survey)"
                  >
                    <Trash2 class="h-4 w-4" />
                  </Button>
                </div>
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
              @click="fetchSurveys(pagination.current_page - 1)"
            >
              <ChevronLeft class="h-3.5 w-3.5" />
              <span>Prev</span>
            </Button>
            <Button
              variant="outline"
              size="sm"
              class="gap-1 text-xs"
              :disabled="pagination.current_page === pagination.last_page"
              @click="fetchSurveys(pagination.current_page + 1)"
            >
              <span>Next</span>
              <ChevronRight class="h-3.5 w-3.5" />
            </Button>
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>
