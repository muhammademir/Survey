export interface User {
  id: number
  name: string
  email: string
  birth_date: string | null
  phone: string | null
  role: 'admin' | 'user'
  approval_status: 'pending' | 'approved' | 'rejected'
  is_active: boolean
  created_at?: string
}

export interface UserStats {
  total: number
  admins: number
  users: number
  pending: number
  active: number
}

// ===== Survey Types =====
export interface QuestionOption {
  id: number
  question_id: number
  label: 'A' | 'B' | 'C' | 'D'
  option_text: string
}

export interface Question {
  id: number
  survey_category_id: number
  question_text: string
  sort_order: number
  options: QuestionOption[]
}

export interface SurveyCategory {
  id: number
  survey_id: number
  name: string
  description: string | null
  sort_order: number
  questions: Question[]
}

export interface Survey {
  id: number
  title: string
  description: string | null
  status: 'draft' | 'published'
  publish_start_at: string | null
  publish_end_at: string | null
  published_at: string | null
  created_by: number
  created_at: string
  updated_at: string
  creator?: User
  categories?: SurveyCategory[]
  responses_count?: number
  already_responded?: boolean
}

// ===== Survey Response Types =====
export interface ResponseAnswer {
  id: number
  survey_response_id: number
  question_id: number
  question_option_id: number
  question?: Question
  option?: QuestionOption
}

export interface SurveyResponse {
  id: number
  survey_id: number
  user_id: number
  created_at: string
  survey?: Survey
  user?: User
  answers?: ResponseAnswer[]
}

// ===== API Response Types =====
export interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

export interface ApiMessageResponse {
  message: string
}

// ===== Form Types =====
export interface LoginForm {
  email: string
  password: string
}

export interface RegisterForm {
  name: string
  email: string
  password: string
  password_confirmation: string
  birth_date?: string
  phone?: string
}

export interface CreateUserForm {
  name: string
  email: string
  password: string
  password_confirmation: string
  role: 'admin' | 'user'
  birth_date?: string
  phone?: string
  is_active?: boolean
}

export interface UpdateUserForm {
  name: string
  email: string
  password?: string
  password_confirmation?: string
  role: 'admin' | 'user'
  birth_date?: string
  phone?: string
  is_active?: boolean
  approval_status?: 'pending' | 'approved' | 'rejected'
}

// ===== Toast Notification Types =====
export type ToastType = 'success' | 'error' | 'warning' | 'info'

export interface ToastItem {
  id: string
  type: ToastType
  title?: string
  message: string
  duration?: number
}

export interface SurveyForm {
  title: string
  description: string
  publish_start_at: string
  publish_end_at: string
  categories: SurveyCategoryForm[]
}

export interface SurveyCategoryForm {
  name: string
  description: string
  questions: SurveyQuestionForm[]
}

export interface SurveyQuestionForm {
  question_text: string
  options: SurveyOptionForm[]
}

export interface SurveyOptionForm {
  label: 'A' | 'B' | 'C' | 'D'
  option_text: string
}

export interface SubmitAnswerForm {
  question_id: number
  question_option_id: number
}

// ===== Survey Result Types =====
export interface ResultOption {
  id: number
  label: string
  option_text: string
  count: number
  percentage: number
}

export interface ResultQuestion {
  id: number
  question_text: string
  options: ResultOption[]
}

export interface ResultCategory {
  id: number
  name: string
  description: string | null
  questions: ResultQuestion[]
}

export interface SurveyResultSummary {
  survey: {
    id: number
    title: string
    description: string | null
    status: string
    total_responses: number
    publish_start_at: string | null
    publish_end_at: string | null
  }
  categories: ResultCategory[]
}
