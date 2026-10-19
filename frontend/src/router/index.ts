import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
      meta: { guest: true },
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('@/views/RegisterView.vue'),
      meta: { guest: true },
    },
    // ===== ADMIN ROUTES =====
    {
      path: '/admin',
      component: () => import('@/layouts/AdminLayout.vue'),
      meta: { requiresAuth: true, requiresAdmin: true },
      children: [
        {
          path: '',
          name: 'admin-dashboard',
          component: () => import('@/views/admin/DashboardView.vue'),
        },
        {
          path: 'users',
          name: 'admin-users',
          component: () => import('@/views/admin/UsersView.vue'),
        },
        {
          path: 'surveys',
          name: 'admin-surveys',
          component: () => import('@/views/admin/SurveysView.vue'),
        },
        {
          path: 'surveys/create',
          name: 'admin-survey-create',
          component: () => import('@/views/admin/SurveyCreateView.vue'),
        },
        {
          path: 'surveys/:id/edit',
          name: 'admin-survey-edit',
          component: () => import('@/views/admin/SurveyEditView.vue'),
        },
        {
          path: 'surveys/:id/results',
          name: 'admin-survey-results',
          component: () => import('@/views/admin/SurveyResultsView.vue'),
        },
        {
          path: 'responses',
          name: 'admin-responses',
          component: () => import('@/views/admin/ResponsesView.vue'),
        },
      ],
    },
    // ===== USER ROUTES =====
    {
      path: '/',
      component: () => import('@/layouts/UserLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          name: 'user-dashboard',
          component: () => import('@/views/user/DashboardView.vue'),
        },
        {
          path: 'profile',
          name: 'user-profile',
          component: () => import('@/views/user/ProfileView.vue'),
        },
        {
          path: 'surveys/:id/fill',
          name: 'user-survey-fill',
          component: () => import('@/views/user/SurveyFillView.vue'),
        },
        {
          path: 'history',
          name: 'user-history',
          component: () => import('@/views/user/HistoryView.vue'),
        },
      ],
    },
  ],
})

// Navigation Guard — waits for auth init before checking access
router.beforeEach(async (to) => {
  const auth = useAuthStore()

  // Tunggu init selesai (hanya 1x saat pertama kali)
  if (!auth.initialized) {
    await auth.init()
  }

  // Halaman yang butuh login tapi belum login
  if (to.meta.requiresAuth && !auth.isLoggedIn) {
    return { name: 'login' }
  }

  // Halaman admin tapi bukan admin
  if (to.meta.requiresAdmin && !auth.isAdmin) {
    return { name: 'user-dashboard' }
  }

  // Halaman guest (login/register) tapi sudah login
  if (to.meta.guest && auth.isLoggedIn) {
    return auth.isAdmin
      ? { name: 'admin-dashboard' }
      : { name: 'user-dashboard' }
  }
})

export default router
