import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

// Lazy-loaded views for code splitting
const LoginView = () => import('../views/LoginView.vue')
const DashboardView = () => import('../views/DashboardView.vue')
const CoursesList = () => import('../views/CoursesList.vue')
const StudentPortal = () => import('../views/Student/StudentDashboard.vue')
const ParentPortal = () => import('../views/Parent/ParentDashboard.vue')
const ChildDetail = () => import('../views/Parent/ChildDetail.vue')
const TeacherDashboard = () => import('../views/Teacher/TeacherDashboard.vue')
const CourseView = () => import('../views/Teacher/CourseView.vue')
const BillingDashboard = () => import('../views/BillingDashboard.vue')
const SettingsView = () => import('../views/SettingsView.vue')
const AuditLogsView = () => import('../views/AuditLogsView.vue')
const StudentsView = () => import('../views/StudentsView.vue')
const ReportsView = () => import('../views/ReportsView.vue')
const AcademicPeriodsView = () => import('../views/Admin/AcademicPeriodsView.vue')

const routes = [
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { requiresGuest: true },
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: DashboardView,
    meta: { requiresAuth: true },
  },
  {
    path: '/teacher/dashboard',
    name: 'teacher-dashboard',
    component: TeacherDashboard,
    meta: { requiresAuth: true },
  },
  {
    path: '/teacher/courses/:id',
    name: 'teacher-course',
    component: CourseView,
    meta: { requiresAuth: true },
  },
  {
    path: '/student-portal',
    name: 'student-portal',
    component: StudentPortal,
    meta: { requiresAuth: true },
  },
  {
    path: '/parent-portal',
    name: 'parent-portal',
    component: ParentPortal,
    meta: { requiresAuth: true },
  },
  {
    path: '/parent/children/:id',
    name: 'parent-child-detail',
    component: ChildDetail,
    meta: { requiresAuth: true },
  },
  {
    path: '/billing',
    name: 'billing',
    component: BillingDashboard,
    meta: { requiresAuth: true },
  },
  {
    path: '/settings',
    name: 'settings',
    component: SettingsView,
    meta: { requiresAuth: true },
  },
  {
    path: '/academic-periods',
    name: 'academic-periods',
    component: AcademicPeriodsView,
    meta: { requiresAuth: true },
  },
  {
    path: '/audit-logs',
    name: 'audit-logs',
    component: AuditLogsView,
    meta: { requiresAuth: true },
  },
  {
    path: '/students',
    name: 'students',
    component: StudentsView,
    meta: { requiresAuth: true },
  },
  {
    path: '/students/new',
    name: 'student-new',
    component: () => import('../views/StudentForm.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/student/:id',
    name: 'student-profile',
    component: () => import('../views/StudentProfile.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/course-assignments',
    name: 'course-assignments',
    component: () => import('../views/CourseAssignmentsView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/reports',
    name: 'reports',
    component: ReportsView,
    meta: { requiresAuth: true },
  },
  {
    path: '/courses',
    name: 'courses',
    component: CoursesList,
    meta: { requiresAuth: true },
  },
  {
    path: '/course/:id/attendance',
    name: 'attendance',
    component: () => import('../views/AttendanceView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/evaluation/:id/grades',
    name: 'grades',
    component: () => import('../views/GradesEntryView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/',
    redirect: '/dashboard',
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// ── Navigation Guard ─────────────────────────────────────────────────────────
router.beforeEach((to) => {
  const auth = useAuthStore()
  const isAuthenticated = !!auth.token

  if (to.meta.requiresAuth && !isAuthenticated) {
    return { name: 'login' }
  }

  // Handle role-based navigation for the dashboard/portals
  if (to.path === '/dashboard' && isAuthenticated && auth.user?.role?.name) {
    if (auth.user.role.name === 'Student') {
      return { name: 'student-portal' }
    } else if (auth.user.role.name === 'Parent') {
      return { name: 'parent-portal' }
    } else if (auth.user.role.name === 'Teacher') {
      return { name: 'teacher-dashboard' }
    }
  }

  if (to.meta.requiresGuest && isAuthenticated) {
    return { name: 'dashboard' }
  }
})

export default router
