<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { toast } from '@/stores/toast'
import api from '@/services/api'
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'
import { Alert, AlertDescription } from '@/components/ui/alert'
import {
  ArrowLeft,
  Plus,
  Trash2,
  FolderPlus,
  Loader2,
  AlertCircle,
  Pencil,
} from 'lucide-vue-next'

const router = useRouter()
const route = useRoute()

interface Option {
  label: string
  option_text: string
}

interface Question {
  question_text: string
  options: Option[]
}

interface Category {
  name: string
  description: string
  questions: Question[]
}

const form = ref({
  title: '',
  description: '',
  publish_start_at: '',
  publish_end_at: '',
  categories: [] as Category[],
})

const error = ref('')
const loading = ref(false)
const pageLoading = ref(true)

function createOption(label: string): Option {
  return { label, option_text: '' }
}

function createQuestion(): Question {
  return {
    question_text: '',
    options: [createOption('A'), createOption('B'), createOption('C'), createOption('D')],
  }
}

function createCategory(): Category {
  return {
    name: '',
    description: '',
    questions: [createQuestion()],
  }
}

function addCategory() {
  form.value.categories.push(createCategory())
}

function removeCategory(index: number) {
  if (form.value.categories.length > 1) {
    form.value.categories.splice(index, 1)
  }
}

function addQuestion(catIndex: number) {
  const cat = form.value.categories[catIndex]
  if (cat) {
    cat.questions.push(createQuestion())
  }
}

function removeQuestion(catIndex: number, qIndex: number) {
  const cat = form.value.categories[catIndex]
  if (cat && cat.questions.length > 1) {
    cat.questions.splice(qIndex, 1)
  }
}

function formatDateTimeLocal(d: string | null): string {
  if (!d) return ''
  const date = new Date(d)
  const offset = date.getTimezoneOffset()
  const local = new Date(date.getTime() - offset * 60000)
  return local.toISOString().slice(0, 16)
}

onMounted(async () => {
  try {
    const res = await api.get(`/admin/surveys/${route.params.id}`)
    const survey = res.data.survey

    if (survey.status !== 'draft') {
      toast.warning('Survey yang sudah dipublish tidak dapat diedit.')
      router.push({ name: 'admin-surveys' })
      return
    }

    form.value = {
      title: survey.title,
      description: survey.description || '',
      publish_start_at: formatDateTimeLocal(survey.publish_start_at),
      publish_end_at: formatDateTimeLocal(survey.publish_end_at),
      categories: survey.categories.map((c: any) => ({
        name: c.name,
        description: c.description || '',
        questions: c.questions.map((q: any) => ({
          question_text: q.question_text,
          options: q.options.map((o: any) => ({
            label: o.label,
            option_text: o.option_text,
          })),
        })),
      })),
    }
  } catch (e: any) {
    toast.error(e.response?.data?.message || 'Gagal memuat survey')
    router.push({ name: 'admin-surveys' })
  } finally {
    pageLoading.value = false
  }
})

async function handleSubmit() {
  error.value = ''
  loading.value = true
  try {
    await api.put(`/admin/surveys/${route.params.id}`, form.value)
    toast.success('Survey berhasil diperbarui!')
    router.push({ name: 'admin-surveys' })
  } catch (e: any) {
    error.value = e.response?.data?.message || 'Gagal menyimpan perubahan survey'
    toast.error(error.value)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="flex-1 space-y-6 p-8 pt-6 max-w-5xl mx-auto">
    <!-- Topbar Navigation -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <Button variant="ghost" size="sm" class="gap-1.5 text-xs text-muted-foreground" @click="router.push({ name: 'admin-surveys' })">
          <ArrowLeft class="h-4 w-4" />
          <span>Kembali</span>
        </Button>
        <h2 class="text-2xl font-bold tracking-tight text-foreground">Edit Survey (Draft)</h2>
      </div>
    </div>

    <div v-if="pageLoading" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
    </div>

    <template v-else>
      <Alert v-if="error" variant="destructive">
        <AlertCircle class="h-4 w-4" />
        <AlertDescription>{{ error }}</AlertDescription>
      </Alert>

      <form class="space-y-6" @submit.prevent="handleSubmit">
        <!-- General Information Card -->
        <Card class="border-border/70 shadow-sm">
          <CardHeader class="pb-4 border-b border-border/40">
            <CardTitle class="text-base flex items-center gap-2">
              <Pencil class="w-4 h-4 text-primary" />
              <span>Informasi & Masa Tayang Survey</span>
            </CardTitle>
            <CardDescription>
              Ubah judul, deskripsi, dan jadwal penayangan kuesioner sebelum dipublikasikan.
            </CardDescription>
          </CardHeader>
          <CardContent class="p-6 space-y-4">
            <!-- Title -->
            <div class="space-y-1.5">
              <Label for="survey-edit-title">Judul Survey <span class="text-destructive">*</span></Label>
              <Input
                id="survey-edit-title"
                v-model="form.title"
                type="text"
                required
                class="bg-background/50 border-input/80 focus-visible:ring-primary"
              />
            </div>

            <!-- Description -->
            <div class="space-y-1.5">
              <Label for="survey-edit-desc">Deskripsi Survey</Label>
              <textarea
                id="survey-edit-desc"
                v-model="form.description"
                class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring min-h-[90px]"
              />
            </div>

            <!-- Date Range -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
              <div class="space-y-1.5">
                <Label for="edit_publish_start">Tanggal Mulai Tayang <span class="text-destructive">*</span></Label>
                <Input
                  id="edit_publish_start"
                  v-model="form.publish_start_at"
                  type="datetime-local"
                  required
                  class="bg-background/50 border-input/80 focus-visible:ring-primary cursor-pointer"
                />
              </div>

              <div class="space-y-1.5">
                <Label for="edit_publish_end">Tanggal Selesai Tayang <span class="text-destructive">*</span></Label>
                <Input
                  id="edit_publish_end"
                  v-model="form.publish_end_at"
                  type="datetime-local"
                  required
                  class="bg-background/50 border-input/80 focus-visible:ring-primary cursor-pointer"
                />
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Categories & Questions Loop -->
        <div class="space-y-6">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-foreground">Kelompok Kategori & Pertanyaan (A, B, C, D)</h3>
            <Button
              type="button"
              variant="outline"
              size="sm"
              class="gap-1.5 border-dashed"
              @click="addCategory"
            >
              <FolderPlus class="w-4 h-4 text-primary" />
              <span>Tambah Kategori</span>
            </Button>
          </div>

          <Card
            v-for="(cat, cIdx) in form.categories"
            :key="cIdx"
            class="border-border/80 shadow-sm overflow-hidden"
          >
            <!-- Category Header -->
            <CardHeader class="bg-muted/20 border-b border-border/50 p-4">
              <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2 flex-1">
                  <Badge variant="default" class="text-xs font-semibold px-2 py-0.5">
                    Kategori {{ cIdx + 1 }}
                  </Badge>
                  <Input
                    v-model="cat.name"
                    type="text"
                    required
                    class="bg-background/60 font-semibold text-sm max-w-sm h-8"
                  />
                </div>

                <Button
                  v-if="form.categories.length > 1"
                  type="button"
                  variant="ghost"
                  size="sm"
                  class="text-destructive hover:bg-destructive/10 h-8 gap-1 text-xs"
                  @click="removeCategory(cIdx)"
                >
                  <Trash2 class="w-3.5 h-3.5" />
                  <span>Hapus Kategori</span>
                </Button>
              </div>

              <div class="pt-2">
                <Input
                  v-model="cat.description"
                  type="text"
                  placeholder="Deskripsi kategori..."
                  class="bg-background/40 text-xs h-7 border-border/40"
                />
              </div>
            </CardHeader>

            <!-- Questions -->
            <CardContent class="p-6 space-y-6">
              <div
                v-for="(q, qIdx) in cat.questions"
                :key="qIdx"
                class="p-4 rounded-xl border border-border/60 bg-card/60 space-y-4"
              >
                <div class="flex items-start justify-between gap-3">
                  <div class="flex items-center gap-2 flex-1">
                    <span class="text-xs font-bold text-muted-foreground w-6">#{{ qIdx + 1 }}</span>
                    <Input
                      v-model="q.question_text"
                      type="text"
                      required
                      class="bg-background/50 font-medium text-sm flex-1"
                    />
                  </div>

                  <Button
                    v-if="cat.questions.length > 1"
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="h-8 w-8 text-destructive hover:bg-destructive/10 shrink-0"
                    @click="removeQuestion(cIdx, qIdx)"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </Button>
                </div>

                <!-- Options A, B, C, D -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pl-8">
                  <div
                    v-for="(opt, oIdx) in q.options"
                    :key="oIdx"
                    class="flex items-center gap-2"
                  >
                    <Badge variant="secondary" class="font-bold w-6 h-6 flex items-center justify-center p-0 shrink-0">
                      {{ opt.label }}
                    </Badge>
                    <Input
                      v-model="opt.option_text"
                      type="text"
                      required
                      class="h-8 text-xs bg-background/40"
                    />
                  </div>
                </div>
              </div>

              <Button
                type="button"
                variant="secondary"
                size="sm"
                class="w-full gap-1.5 border-dashed border-border text-xs"
                @click="addQuestion(cIdx)"
              >
                <Plus class="w-3.5 h-3.5" />
                <span>Tambah Pertanyaan pada Kategori Ini</span>
              </Button>
            </CardContent>
          </Card>
        </div>

        <!-- Submit Footer -->
        <div class="flex items-center justify-between pt-4 border-t border-border/60">
          <Button type="button" variant="ghost" @click="router.push({ name: 'admin-surveys' })">
            Batal
          </Button>
          <Button type="submit" size="lg" class="gap-2 min-w-[200px] shadow-md shadow-primary/25" :disabled="loading">
            <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
            <span v-else>Simpan Perubahan</span>
          </Button>
        </div>
      </form>
    </template>
  </div>
</template>
