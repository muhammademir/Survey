import api, { getCsrfCookie } from './api'
import type { User, LoginForm, RegisterForm } from '@/types'

interface AuthResponse {
  message: string
  user: User
}

/**
 * Login via Sanctum SPA session.
 * Fetch CSRF cookie → POST /login → session cookie set otomatis.
 */
export async function login(credentials: LoginForm): Promise<AuthResponse> {
  await getCsrfCookie()
  const { data } = await api.post<AuthResponse>('/login', credentials)
  return data
}

/**
 * Register via Sanctum SPA session.
 */
export async function register(form: RegisterForm): Promise<AuthResponse> {
  await getCsrfCookie()
  const { data } = await api.post<AuthResponse>('/register', form)
  return data
}

/**
 * Logout — destroy server session.
 */
export async function logout(): Promise<void> {
  await api.post('/logout')
}

/**
 * Ambil data user yang sedang login dari session.
 */
export async function fetchCurrentUser(): Promise<User> {
  const { data } = await api.get<{ user: User }>('/me')
  return data.user
}

/**
 * Update profil user yang sedang login.
 */
export async function updateProfile(form: Partial<User>): Promise<AuthResponse> {
  const { data } = await api.put<AuthResponse>('/profile', form)
  return data
}
