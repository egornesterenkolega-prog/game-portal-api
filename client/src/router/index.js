import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'
import GameView from '../views/GameView.vue'
import GameFormView from '../views/GameFormView.vue'
import GameEditView from '../views/GameEditView.vue'
import GenresView from '../views/GenresView.vue'
import UserView from '../views/UserView.vue'
import { useAuthStore } from '../stores/auth'
import StatsView from '../views/StatsView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/', name: 'home', component: HomeView },
    { path: '/login', name: 'login', component: LoginView, meta: { guestOnly: true } },
    { path: '/register', name: 'register', component: RegisterView, meta: { guestOnly: true } },
    { path: '/games/new', name: 'game-create', component: GameFormView, meta: { requiresAuth: true } },
    { path: '/games/:id/edit', name: 'game-edit', component: GameEditView, meta: { requiresAuth: true } },
    { path: '/games/:id', name: 'game', component: GameView },
    { path: '/users/:id', name: 'user', component: UserView },
    { path: '/admin/stats', name: 'admin-stats', component: StatsView, meta: { requiresAuth: true, requiresAdmin: true } },
    {
      path: '/admin/genres',
      name: 'admin-genres',
      component: GenresView,
      meta: { requiresAuth: true, requiresAdmin: true },
    },
  ],
})

router.beforeEach((to) => {
  const auth = useAuthStore()
  if (to.meta.requiresAuth && !auth.isLoggedIn) return { name: 'login' }
  if (to.meta.requiresAdmin && !auth.isAdmin) return { name: 'home' }
  if (to.meta.guestOnly && auth.isLoggedIn) return { name: 'home' }
})

export default router