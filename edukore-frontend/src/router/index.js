import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

// Lazy-loaded views for code splitting
const LoginView             = () => import('../views/LoginView.vue')
const DashboardView         = () => import('../views/DashboardView.vue')
const CoursesList           = () => import('../views/CoursesList.vue')
const AcademicStructureView = () => import('../views/Admin/AcademicStructureView.vue')
const SectionDetailView     = () => import('../views/Admin/SectionDetailView.vue')
const CourseDetailView      = () => import('../views/CourseDetailView.vue')
const StudentPortal         = () => import('../views/Student/StudentDashboard.vue')
const ParentPortal          = () => import('../views/Parent/ParentDashboard.vue')
const ChildDetail           = () => import('../views/Parent/ChildDetail.vue')
const TeacherDashboard      = () => import('../views/Teacher/TeacherDashboard.vue')
const CourseView            = () => import('../views/Teacher/CourseView.vue')
const BillingDashboard      = () => import('../views/BillingDashboard.vue')
const SettingsView          = () => import('../views/SettingsView.vue')
const AuditLogsView         = () => import('../views/AuditLogsView.vue')
const StudentsView          = () => import('../views/StudentsView.vue')
const TeachersView          = () => import('../views/Admin/TeachersView.vue')
const ReportsView           = () => import('../views/Admin/ReportsView.vue')
const AcademicPeriodsView   = () => import('../views/Admin/AcademicPeriodsView.vue')
const FinancesView          = () => import('../views/Admin/FinancesView.vue')
const CommunityEventsView   = () => import('../views/Admin/CommunityEventsView.vue')
const AnnouncementsView     = () => import('../views/Admin/AnnouncementsView.vue')

import GradesView from '../views/Admin/GradesView.vue'

// ─── Route Definitions ────────────────────────────────────────────────────────
// meta.roles: array de roles autorizados (string en inglés/snake_case).
//             Si está vacío/ausente, cualquier usuario autenticado puede acceder.
// meta.requiresAuth: true → redirige a /login si no hay sesión.
// meta.requiresGuest: true → redirige al dashboard si ya hay sesión.
// meta.module: nombre del módulo del tenant que debe estar activo.
// ─────────────────────────────────────────────────────────────────────────────

const routes = [
  // ── Auth ──────────────────────────────────────────────────────────────────
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { requiresGuest: true },
  },

  // ── Dashboard (redirige por rol en el guard) ───────────────────────────────
  {
    path: '/dashboard',
    name: 'dashboard',
    component: DashboardView,
    meta: { requiresAuth: true },
  },

  // ── Portales específicos de rol ────────────────────────────────────────────
  {
    path: '/teacher/dashboard',
    name: 'teacher-dashboard',
    component: TeacherDashboard,
    meta: { requiresAuth: true, roles: ['teacher', 'admin', 'super_admin'] },
  },
  {
    path: '/teacher/courses/:id',
    name: 'teacher-course',
    component: CourseView,
    meta: { requiresAuth: true, roles: ['teacher', 'admin', 'super_admin'] },
  },
  {
    path: '/student-portal',
    name: 'student-portal',
    component: StudentPortal,
    meta: { requiresAuth: true, roles: ['student'] },
  },
  {
    path: '/parent-portal',
    name: 'parent-portal',
    component: ParentPortal,
    meta: { requiresAuth: true, roles: ['parent'] },
  },
  {
    path: '/parent/children/:id',
    name: 'parent-child-detail',
    component: ChildDetail,
    meta: { requiresAuth: true, roles: ['parent'] },
  },

  // ── Administración (solo admin / super_admin) ──────────────────────────────
  {
    path: '/students',
    name: 'students',
    component: StudentsView,
    meta: { requiresAuth: true, roles: ['admin', 'super_admin'] },
  },
  {
    path: '/students/new',
    name: 'student-new',
    component: () => import('../views/StudentForm.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'super_admin'] },
  },
  {
    path: '/student/:id',
    name: 'student-profile',
    component: () => import('../views/StudentProfile.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'super_admin'] },
  },
  {
    path: '/teachers',
    name: 'teachers',
    component: TeachersView,
    meta: { requiresAuth: true, roles: ['admin', 'super_admin'] },
  },
  {
    path: '/academic-structure',
    name: 'academic-structure',
    component: AcademicStructureView,
    meta: { requiresAuth: true, roles: ['admin', 'super_admin'] },
  },
  {
    path: '/academic-structure/sections/:id',
    name: 'section-detail',
    component: SectionDetailView,
    meta: { requiresAuth: true, roles: ['admin', 'super_admin'] },
  },
  {
    path: '/academic-periods',
    name: 'academic-periods',
    component: AcademicPeriodsView,
    meta: { requiresAuth: true, roles: ['admin', 'super_admin'] },
  },
  {
    path: '/settings',
    name: 'settings',
    component: SettingsView,
    meta: { requiresAuth: true, roles: ['admin', 'super_admin'] },
  },
  {
    path: '/audit-logs',
    name: 'audit-logs',
    component: AuditLogsView,
    meta: { requiresAuth: true, roles: ['admin', 'super_admin'] },
  },
  {
    path: '/reports',
    name: 'reports',
    component: ReportsView,
    meta: { requiresAuth: true, roles: ['admin', 'super_admin'] },
  },
  {
    path: '/events',
    name: 'community-events',
    component: CommunityEventsView,
    meta: { requiresAuth: true, roles: ['admin', 'super_admin'] },
  },
  {
    path: '/announcements',
    name: 'announcements',
    component: AnnouncementsView,
    meta: { requiresAuth: true, roles: ['admin', 'super_admin'] },
  },

  // ── Cursos (lectura: todos los autenticados; gestión: admin) ──────────────
  {
    path: '/courses',
    name: 'courses',
    component: CoursesList,
    meta: { requiresAuth: true },
  },
  {
    path: '/courses/:id',
    name: 'course-detail',
    component: CourseDetailView,
    meta: { requiresAuth: true },
  },
  {
    path: '/course-assignments',
    name: 'course-assignments',
    component: () => import('../views/CourseAssignmentsView.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'super_admin'] },
  },

  // ── Asistencia, Horarios y Calificaciones (admin + teacher) ─────────────────────────
  {
    path: '/grades',
    name: 'grades',
    component: GradesView,
    meta: { requiresAuth: true, roles: ['admin', 'super_admin', 'teacher'] },
  },
  {
    path: '/schedules',
    name: 'schedules',
    component: () => import('../views/Admin/SchedulesView.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'super_admin', 'teacher'] },
  },
  {
    path: '/daily-attendance',
    name: 'daily-attendance',
    component: () => import('../views/Admin/AttendanceDailyView.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'super_admin', 'teacher'] },
  },
  {
    path: '/course/:id/attendance',
    name: 'attendance',
    component: () => import('../views/AttendanceView.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'super_admin', 'teacher'] },
  },
  {
    path: '/evaluation/:id/grades',
    name: 'grades-entry',
    component: () => import('../views/GradesEntryView.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'super_admin', 'teacher'] },
  },

  // ── Finanzas (admin, módulo activo requerido) ──────────────────────────────
  {
    path: '/billing',
    name: 'billing',
    component: BillingDashboard,
    meta: { requiresAuth: true, roles: ['admin', 'super_admin'], module: 'finances' },
  },
  {
    path: '/finances',
    name: 'finances',
    component: FinancesView,
    meta: { requiresAuth: true, roles: ['admin', 'super_admin'], module: 'finances' },
  },

  // ── Familias (solo admin) ──────────────────────────────────────────────────
  {
    path: '/families',
    name: 'families',
    component: () => import('../views/Admin/Families/FamiliesIndex.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'super_admin'] },
  },
  {
    path: '/families/:id',
    name: 'family-detail',
    component: () => import('../views/Admin/Families/FamilyDetailView.vue'),
    meta: { requiresAuth: true, roles: ['admin', 'super_admin'] },
  },
  {
    path: '/parents/new',
    name: 'parent-new',
    component: () => import('../views/Admin/ParentRegistrationView.vue'),
    meta: { requiresAuth: true },
  },

  // ── Raíz ──────────────────────────────────────────────────────────────────
  {
    path: '/',
    redirect: '/dashboard',
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// ── Navigation Guard ──────────────────────────────────────────────────────────
//
// Orden de evaluación:
//  1. Ruta pública no protegida → pasar siempre.
//  2. requiresAuth y no autenticado → /login.
//  3. requiresGuest y autenticado → /dashboard.
//  4. meta.roles definido y el rol del usuario no está incluido → /403.
//  5. meta.module definido y el módulo está desactivado → /dashboard (silencioso).
//  6. Redirección semántica del /dashboard según el rol del usuario.
//
router.beforeEach((to) => {
  const auth            = useAuthStore()
  const isAuthenticated = !!auth.token

  // ── PARCHE SPA CRASH ──────────────────────────────────────────────────────
  // Optional chaining en cascada: auth.user?.roles?.[0]?.name cubre:
  //  - auth.user === null/undefined (sesión no hidratada, error de red)
  //  - roles === undefined (respuesta del backend sin el array)
  //  - roles.length === 0 (usuario sin rol asignado — estado inválido)
  //  - roles[0].name === undefined (objeto malformado)
  // El fallback 'guest' garantiza que nunca se produzca un TypeError y que
  // la rama de roles del guard siempre falle limpiamente para este usuario.
  const userRole = auth.user?.roles?.[0]?.name || auth.user?.role?.name || 'guest'

  // 2. Redirigir a login si la ruta requiere autenticación
  if (to.meta.requiresAuth && !isAuthenticated) {
    return { name: 'login' }
  }

  // 3. Redirigir al dashboard si ya está autenticado y trata de ir al login
  if (to.meta.requiresGuest && isAuthenticated) {
    return { name: 'dashboard' }
  }

  if (isAuthenticated) {
    // 4. Control de acceso por rol (sin alert — silencioso y seguro)
    // Como userRole nunca es null, solo necesitamos verificar includes().
    // Un usuario con rol 'guest' nunca pasará ninguna ruta protegida.
    if (to.meta.roles && to.meta.roles.length > 0) {
      const hasAccess = to.meta.roles.some(role => {
        if (role === 'admin' || role === 'super_admin') return auth.isAdmin;
        if (role === 'teacher') return auth.isTeacher;
        if (role === 'student') return auth.isStudent;
        if (role === 'parent') return auth.isParent;
        return false;
      });
      
      if (!hasAccess) {
        return { name: 'dashboard' }
      }
    }

    // 5. Control de módulos del tenant (sin alert — silencioso)
    if (to.meta.module) {
      const modules = auth.user?.tenant?.active_modules ?? {}
      if (modules[to.meta.module] === false) {
        // El módulo está desactivado en este tenant → redirige silenciosamente.
        return { name: 'dashboard' }
      }
    }

    // 6. Redirección semántica del /dashboard según el rol
    if (to.path === '/dashboard' && userRole) {
      if (userRole === 'student') return { name: 'student-portal' }
      if (userRole === 'parent')  return { name: 'parent-portal' }
      if (userRole === 'teacher') return { name: 'teacher-dashboard' }
    }
  }
})

export default router
