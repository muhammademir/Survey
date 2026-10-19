import api from './api'
import type {
  Survey,
  SurveyForm,
  PaginatedResponse,
  SurveyResultSummary,
  SurveyResponse,
} from '@/types'

// ===== Admin Survey Management =====

export async function getAdminSurveys(params?: {
  page?: number
  status?: string
  search?: string
}): Promise<PaginatedResponse<Survey>> {
  const { data } = await api.get<PaginatedResponse<Survey>>('/admin/surveys', { params })
  return data
}

export async function getAdminSurvey(id: number): Promise<Survey> {
  const { data } = await api.get<{ survey: Survey }>(`/admin/surveys/${id}`)
  return data.survey
}

export async function createSurvey(form: SurveyForm): Promise<{ message: string; survey: Survey }> {
  const { data } = await api.post('/admin/surveys', form)
  return data
}

export async function updateSurvey(
  id: number,
  form: SurveyForm,
): Promise<{ message: string; survey: Survey }> {
  const { data } = await api.put(`/admin/surveys/${id}`, form)
  return data
}

export async function publishSurvey(id: number): Promise<{ message: string; survey: Survey }> {
  const { data } = await api.patch(`/admin/surveys/${id}/publish`)
  return data
}

export async function deleteSurvey(id: number): Promise<{ message: string }> {
  const { data } = await api.delete(`/admin/surveys/${id}`)
  return data
}

// ===== Admin Survey Results =====

export async function getSurveyResults(id: number): Promise<SurveyResultSummary> {
  const { data } = await api.get<SurveyResultSummary>(`/admin/surveys/${id}/results`)
  return data
}

export async function getSurveyRespondents(
  id: number,
): Promise<PaginatedResponse<SurveyResponse>> {
  const { data } = await api.get<PaginatedResponse<SurveyResponse>>(
    `/admin/surveys/${id}/respondents`,
  )
  return data
}

// ===== User Survey =====

export async function getAvailableSurveys(): Promise<Survey[]> {
  const { data } = await api.get<{ surveys: Survey[] }>('/surveys/available')
  return data.surveys
}

export async function getSurveyForFill(id: number): Promise<Survey> {
  const { data } = await api.get<{ survey: Survey }>(`/surveys/${id}/fill`)
  return data.survey
}

export async function submitSurveyResponse(
  surveyId: number,
  answers: { question_id: number; question_option_id: number }[],
): Promise<{ message: string }> {
  const { data } = await api.post(`/surveys/${surveyId}/submit`, { answers })
  return data
}

export async function getMyHistory(): Promise<PaginatedResponse<SurveyResponse>> {
  const { data } = await api.get<PaginatedResponse<SurveyResponse>>('/surveys/my-history')
  return data
}
