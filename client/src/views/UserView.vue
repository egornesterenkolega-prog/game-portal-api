<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import api, { errorMessage } from '../api'
import { useAuthStore } from '../stores/auth'
import GameCard from '../components/GameCard.vue'

const route = useRoute()
const auth = useAuthStore()

const profile = ref(null)
const loading = ref(true)
const notFound = ref(false)
const error = ref('')

const name = ref('')
const nameErrors = ref([])
const saved = ref(false)

const isOwner = computed(() => auth.isLoggedIn && auth.user?.id === profile.value?.id)
// блокировать может администратор, но не самого себя
const canModerate = computed(
  () => auth.isLoggedIn && auth.isAdmin && auth.user?.id !== profile.value?.id,
)
const games = computed(() => profile.value?.games?.data ?? profile.value?.games ?? [])

async function loadProfile(silent = false) {
  if (!silent) loading.value = true
  notFound.value = false
  error.value = ''
  try {
    const { data } = await api.get(`/users/${route.params.id}`)
    profile.value = data.data ?? data
    name.value = profile.value.name
  } catch (e) {
    if (e.response?.status === 404) notFound.value = true
    else error.value = errorMessage(e, 'Не удалось загрузить профиль')
  } finally {
    loading.value = false
  }
}

async function saveName() {
  nameErrors.value = []
  saved.value = false
  try {
    const { data } = await api.patch('/me', { name: name.value })
    const updated = data.data ?? data
    profile.value.name = updated.name
    auth.user = { ...auth.user, name: updated.name }
    localStorage.setItem('user', JSON.stringify(auth.user))
    saved.value = true
  } catch (e) {
    if (e.response?.status === 422) nameErrors.value = e.response.data.errors?.name ?? []
    else error.value = errorMessage(e)
  }
}

async function toggleBlock() {
  error.value = ''
  const action = profile.value.is_blocked ? 'unblock' : 'block'
  try {
    await api.post(`/users/${profile.value.id}/${action}`)
    await loadProfile(true)
  } catch (e) {
    error.value = errorMessage(e)
  }
}

onMounted(() => loadProfile())
watch(() => route.params.id, () => loadProfile())
</script>

<template>
  <p v-if="loading">Загрузка...</p>
  <p v-else-if="notFound">Пользователь не найден. <RouterLink to="/">В каталог</RouterLink></p>
  <p v-else-if="error && !profile" class="error">{{ error }}</p>

  <section v-else-if="profile">
    <h1>{{ profile.name }}</h1>
    <p class="meta">
      Роль: {{ profile.role === 'admin' ? 'администратор' : 'пользователь' }} · На портале с
      {{ new Date(profile.created_at).toLocaleDateString('ru-RU') }}
    </p>

    <p v-if="profile.is_blocked" class="error">Аккаунт заблокирован</p>
    <button v-if="canModerate" type="button" @click="toggleBlock">
      {{ profile.is_blocked ? 'Разблокировать' : 'Заблокировать' }}
    </button>

    <form v-if="isOwner" class="name-form" @submit.prevent="saveName">
      <label>Имя <input v-model="name" type="text" /></label>
      <p v-for="text in nameErrors" :key="text" class="error">{{ text }}</p>
      <button>Сохранить имя</button>
      <span v-if="saved" class="meta">Сохранено</span>
    </form>
    <p v-if="error" class="error">{{ error }}</p>

    <h2>Игры автора ({{ games.length }})</h2>
    <p v-if="!games.length">Пока нет игр</p>
    <div class="grid">
      <GameCard v-for="game in games" :key="game.id" :game="game" />
    </div>
  </section>
</template>