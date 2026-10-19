import axios from 'axios'
import router from '@/router'

const apiBase = import.meta.env.VITE_API_URL

if (!apiBase) {
  throw new Error('VITE_API_URL belum di-set. Salin frontend/.env.example ke frontend/.env')
}

const api = axios.create({
  baseURL: `${apiBase}/api`,
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

// Interceptor untuk handle 401 (Unauthenticated)
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401 || error.response?.status === 419) {
      // Session expired atau CSRF token mismatch
      router.push({ name: 'login' })
    }
    return Promise.reject(error)
  },
)

/**
 * Fetch CSRF cookie dari Sanctum sebelum melakukan
 * request mutasi (login, register, dll).
 */
export async function getCsrfCookie(): Promise<void> {
  await axios.get(`${apiBase}/sanctum/csrf-cookie`, {
    withCredentials: true,
  })
}

export default api
