<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Alert, AlertDescription } from '@/components/ui/alert'
import {
  ArrowLeft,
  CheckCircle2,
  AlertCircle,
  Loader2,
  Sparkles,
  Send,
  History,
  FolderOpen,
} from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const surveyId = route.params.id

interface Option {
  id: number
  label: string
  option_text: string
}

interface Question {
  id: number
  question_text: string
  options: Option[]
}

interface Category {
  id: number
  name: string
  description: string | null
  questions: Question[]
}

interface Survey {
  id: number
  title: string
  description: string | null
  publish_start_at: string | null
  publish_end_at: string | null
  categories: Category[]
}

const survey = ref<Survey | null>(null)
const loading = ref(true)
const submitting = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

// Map of question_id -> selected question_option_id
const selectedAnswers = ref<Record<number, number>>({})

const allQuestions = computed(() => {
  if (!survey.value) return []
  return survey.value.categories.flatMap((cat) => cat.questions)
})

const totalQuestionsCount = computed(() => allQuestions.value.length)
const answeredCount = computed(() => Object.keys(selectedAnswers.value).length)
const progressPercentage = computed(() => {
  if (totalQuestionsCount.value === 0) return 0
  return Math.round((answeredCount.value / totalQuestionsCount.value) * 100)
})

async function fetchSurvey() {
  loading.value = true
  errorMessage.value = ''
  try {
    const res = await api.get(`/surveys/${surveyId}/fill`)
    survey.value = res.data.survey
  } catch (e: any) {
    errorMessage.value = e.response?.data?.message || 'Survey tidak dapat diakses atau sudah berakhir.'
  } finally {
    loading.value = false
  }
}

async function handleSubmit() {
  errorMessage.value = ''

  const missing = allQuestions.value.filter((q) => !selectedAnswers.value[q.id])
  if (missing.length > 0) {
    errorMessage.value = `Mohon jawab semua pertanyaan terlebih dahulu. Masih ada ${missing.length} pertanyaan belum terisi.`
    const firstUnanswered = missing[0]
    if (firstUnanswered) {
      const firstUnansweredEl = document.getElementById(`question-${firstUnanswered.id}`)
      if (firstUnansweredEl) {
        firstUnansweredEl.scrollIntoView({ behavior: 'smooth', block: 'center' })
      }
    }
    return
  }

  submitting.value = true
  try {
    const payload = {
      answers: Object.entries(selectedAnswers.value).map(([qId, optId]) => ({
        question_id: Number(qId),
        question_option_id: optId,
      })),
    }

    const res = await api.post(`/surveys/${surveyId}/submit`, payload)
    successMessage.value = res.data.message || 'Jawaban survey berhasil dikirim!'
  } catch (e: any) {
    errorMessage.value = e.response?.data?.message || 'Gagal mengirim jawaban survey.'
  } finally {
    submitting.value = false
  }
}

onMounted(() => fetchSurvey())
</script>

<template>
  <div class="flex-1 space-y-6 p-8 pt-6 max-w-4xl mx-auto">
    <!-- Topbar Navigation -->
    <div class="flex items-center gap-3">
      <Button variant="ghost" size="sm" class="gap-1.5 text-xs text-muted-foreground" @click="router.push({ name: 'user-dashboard' })">
        <ArrowLeft class="h-4 w-4" />
        <span>Kembali ke Dashboard</span>
      </Button>
    </div>

    <div v-if="loading" class="flex justify-center items-center py-20">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
    </div>

    <!-- Error State (e.g. expired or already submitted) -->
    <Card v-else-if="errorMessage && !survey" class="border-destructive/40 shadow-sm text-center p-8">
      <div class="w-12 h-12 rounded-full bg-destructive/10 text-destructive flex items-center justify-center mx-auto mb-3">
        <AlertCircle class="h-6 w-6" />
      </div>
      <CardTitle class="text-xl">Akses Survey Tidak Tersedia</CardTitle>
      <CardDescription class="max-w-md mx-auto mt-2 mb-6">
        {{ errorMessage }}
      </CardDescription>
      <div class="flex justify-center gap-3">
        <Button variant="default" @click="router.push({ name: 'user-dashboard' })">
          Daftar Survey Tersedia
        </Button>
        <Button variant="outline" @click="router.push({ name: 'user-history' })">
          Lihat Riwayat
        </Button>
      </div>
    </Card>

    <!-- Success State -->
    <Card v-else-if="successMessage" class="border-emerald-500/40 shadow-xl text-center p-8 bg-gradient-to-b from-card to-card/50">
      <div class="w-16 h-16 rounded-full bg-emerald-500/10 text-emerald-400 flex items-center justify-center mx-auto mb-4 border border-emerald-500/20">
        <CheckCircle2 class="h-8 w-8" />
      </div>
      <CardTitle class="text-2xl font-bold text-emerald-400">
        {{ successMessage }}
      </CardTitle>
      <CardDescription class="max-w-md mx-auto mt-2 mb-6 text-sm">
        Terima kasih atas partisipasi dan suara Anda. Data Anda telah tersimpan dengan aman ke dalam sistem kami.
      </CardDescription>
      <div class="flex justify-center gap-3">
        <Button variant="default" class="gap-1.5 shadow-sm" @click="router.push({ name: 'user-history' })">
          <History class="w-4 h-4" />
          <span>Lihat Riwayat Partisipasi</span>
        </Button>
        <Button variant="outline" @click="router.push({ name: 'user-dashboard' })">
          Kembali ke Dashboard
        </Button>
      </div>
    </Card>

    <!-- Survey Form -->
    <div v-else-if="survey" class="space-y-6">
      <!-- Survey Header Card -->
      <Card class="border-border/70 shadow-sm bg-gradient-to-r from-card to-card/60">
        <CardContent class="p-6 space-y-4">
          <div class="space-y-1">
            <Badge variant="outline" class="font-mono text-xs mb-1">KUESIONER #{{ survey.id }}</Badge>
            <h2 class="text-2xl font-bold tracking-tight text-foreground">{{ survey.title }}</h2>
            <p v-if="survey.description" class="text-sm text-muted-foreground">
              {{ survey.description }}
            </p>
          </div>

          <!-- Progress Bar -->
          <div class="pt-3 border-t border-border/50 space-y-2">
            <div class="flex items-center justify-between text-xs">
              <span class="font-medium text-foreground">
                Progress: {{ answeredCount }} dari {{ totalQuestionsCount }} Pertanyaan Terjawab
              </span>
              <Badge :variant="answeredCount === totalQuestionsCount ? 'success' : 'default'">
                {{ progressPercentage }}%
              </Badge>
            </div>
            <div class="w-full bg-muted/60 h-2 rounded-full overflow-hidden">
              <div
                class="bg-primary h-full rounded-full transition-all duration-300"
                :style="{ width: progressPercentage + '%' }"
              />
            </div>
          </div>
        </CardContent>
      </Card>

      <Alert v-if="errorMessage" variant="destructive">
        <AlertCircle class="h-4 w-4" />
        <AlertDescription>{{ errorMessage }}</AlertDescription>
      </Alert>

      <form class="space-y-8" @submit.prevent="handleSubmit">
        <!-- Categories Loop -->
        <div v-for="(cat, cIdx) in survey.categories" :key="cat.id" class="space-y-4">
          <div class="flex items-center gap-2 border-b border-border/40 pb-2">
            <FolderOpen class="w-5 h-5 text-primary" />
            <div>
              <h3 class="text-lg font-bold text-foreground">Bagian {{ cIdx + 1 }}: {{ cat.name }}</h3>
              <p v-if="cat.description" class="text-xs text-muted-foreground">{{ cat.description }}</p>
            </div>
          </div>

          <!-- Questions Loop -->
          <div class="space-y-4">
            <Card
              v-for="(q, qIdx) in cat.questions"
              :id="`question-${q.id}`"
              :key="q.id"
              class="border-border/70 shadow-sm transition-all duration-200"
              :class="selectedAnswers[q.id] ? 'border-primary/50 shadow-primary/5' : ''"
            >
              <CardContent class="p-6 space-y-4">
                <div class="flex items-start justify-between gap-3">
                  <h4 class="font-semibold text-base text-foreground flex-1 leading-relaxed">
                    <span class="text-primary font-bold mr-1">{{ qIdx + 1 }}.</span>
                    {{ q.question_text }}
                  </h4>
                  <Badge
                    :variant="selectedAnswers[q.id] ? 'success' : 'outline'"
                    class="shrink-0 text-[11px]"
                  >
                    {{ selectedAnswers[q.id] ? '✓ Terjawab' : 'Wajib Diisi' }}
                  </Badge>
                </div>

                <!-- Radio Options (A, B, C, D) -->
                <div class="space-y-2 pt-1">
                  <label
                    v-for="opt in q.options"
                    :key="opt.id"
                    class="flex items-center gap-3 p-3 rounded-lg border transition-all cursor-pointer"
                    :class="
                      selectedAnswers[q.id] === opt.id
                        ? 'border-primary bg-primary/10 shadow-sm'
                        : 'border-border/60 hover:bg-muted/30 hover:border-border'
                    "
                  >
                    <input
                      v-model="selectedAnswers[q.id]"
                      type="radio"
                      :name="`question-${q.id}`"
                      :value="opt.id"
                      class="text-primary focus:ring-primary h-4 w-4 shrink-0"
                    />
                    <Badge
                      :variant="selectedAnswers[q.id] === opt.id ? 'default' : 'secondary'"
                      class="w-6 h-6 rounded-md p-0 flex items-center justify-center font-bold text-xs shrink-0"
                    >
                      {{ opt.label }}
                    </Badge>
                    <span class="text-sm font-medium text-foreground flex-1">
                      {{ opt.option_text }}
                    </span>
                  </label>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>

        <!-- Submit Card -->
        <Card class="border-border/70 shadow-md bg-muted/20">
          <CardContent class="p-6 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
              <div class="font-semibold text-sm text-foreground">Pastikan seluruh pertanyaan telah terisi.</div>
              <div class="text-xs text-muted-foreground">Setelah dikirim, tanggapan Anda tidak dapat diubah kembali.</div>
            </div>

            <Button
              type="submit"
              size="lg"
              class="gap-2 shadow-md shadow-primary/25 min-w-[200px]"
              :disabled="submitting || answeredCount !== totalQuestionsCount"
            >
              <Loader2 v-if="submitting" class="w-4 h-4 animate-spin" />
              <Send v-else class="w-4 h-4" />
              <span>Kirim Jawaban Survey</span>
            </Button>
          </CardContent>
        </Card>
      </form>
    </div>
  </div>
</template>
