import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../api'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('token'))
  const user = ref(JSON.parse(localStorage.getItem('user') || 'null'))

  const isLoggedIn = computed(() => !!token.value)
  const isAdmin = computed(() => user.value?.role === 'admin')

  function setSession(data) {
    token.value = data.token
    user.value = data.user?.data ?? data.user
    localStorage.setItem('token', token.value)
    localStorage.setItem('user', JSON.stringify(user.value))
  }

  function clearSession() {
    token.value = null
    user.value = null
    localStorage.removeItem('token')
    localStorage.removeItem('user')
  }

  async function login(credentials) {
    const { data } = await api.post('/login', credentials)
    setSession(data)
  }

  async function register(form) {
    const { data } = await api.post('/register', form)
    setSession(data)
  }

  async function logout() {
    try {
      await api.post('/logout')
    } finally {
      clearSession()
    }
  }

  return { token, user, isLoggedIn, isAdmin, login, register, logout }
})