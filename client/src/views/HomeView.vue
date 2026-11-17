<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import api, { errorMessage } from '../api'
import { loadCover } from '../coverCache'
import GameCard from '../components/GameCard.vue'
import { formatKzt, kztToRub } from '../currency'

const route = useRoute()

const games = ref([])
const genres = ref([])
const meta = ref(null)
const links = ref(null)
const loading = ref(false)
const error = ref('')
const showHero = ref(false)
const heroCover = ref(null)

const defaults = {
  search: '',
  author: '',
  genre_id: '',
  min_rating: '',
  price_from: '',
  price_to: '',
  sort: 'rating',
  order: 'desc',
  per_page: 20,
  page: 1,
}
const filters = reactive({ ...defaults })

async function loadGames() {
  loading.value = true
  error.value = ''
  const isDefault = Object.keys(defaults).every((key) => filters[key] === defaults[key])
  try {
    const params = {}
    for (const [key, value] of Object.entries(filters)) {
      if (value !== '' && value !== null) {
        params[key] = key === 'price_from' || key === 'price_to' ? kztToRub(value) : value
      }
    }
    const { data } = await api.get('/games', { params })
    games.value = data.data
    meta.value = data.meta
    links.value = data.links
    showHero.value = isDefault && data.data.length > 0

    // обложка для баннера: из списка, а если её нет, отдельным запросом
    heroCover.value = games.value[0]?.screenshots?.[0]?.url ?? null
    if (showHero.value && !heroCover.value) {
      loadCover(games.value[0].id).then((url) => (heroCover.value = url))
    }
  } catch (e) {
    showHero.value = false
    if (e.response?.status === 422) {
      const first = Object.values(e.response.data.errors ?? {})[0]
      error.value = first?.[0] ?? 'Проверьте параметры поиска'
    } else {
      error.value = errorMessage(e, 'Не удалось загрузить каталог')
    }
  } finally {
    loading.value = false
  }
}

function applyFilters() {
  filters.page = 1
  loadGames()
}

function resetFilters() {
  Object.assign(filters, defaults)
  loadGames()
}

function setGenre(id) {
  filters.genre_id = id
  applyFilters()
}

function goToPage(page) {
  filters.page = page
  loadGames()
}

onMounted(async () => {
  try {
    const { data } = await api.get('/genres')
    genres.value = data.data ?? data
  } catch (e) {
    genres.value = []
  }
  if (route.query.search) filters.search = String(route.query.search)
  loadGames()
})

// поиск из шапки: адрес вида /?search=...
watch(
  () => route.query.search,
  (value) => {
    filters.search = value ? String(value) : ''
    applyFilters()
  },
)
</script>

<template>
  <h1>Каталог игр</h1>

  <div class="layout">
    <aside class="sidebar">
      <form @submit.prevent="applyFilters">
        <h3>Поиск</h3>
        <input v-model="filters.search" placeholder="Название игры" />
        <input v-model="filters.author" placeholder="Автор" />

        <h3>Рейтинг и цена</h3>
        <input
          v-model="filters.min_rating"
          type="number"
          min="0"
          max="100"
          placeholder="Мин. рейтинг, %"
        />
        <div class="range">
          <input v-model="filters.price_from" type="number" min="0" placeholder="Цена от, ₸" />
          <input v-model="filters.price_to" type="number" min="0" placeholder="Цена до, ₸" />
        </div>

        <h3>Сортировка</h3>
        <select v-model="filters.sort">
          <option value="rating">По рейтингу</option>
          <option value="created_at">По дате</option>
          <option value="price">По цене</option>
          <option value="title">По названию</option>
        </select>
        <select v-model="filters.order">
          <option value="desc">По убыванию</option>
          <option value="asc">По возрастанию</option>
        </select>
        <select v-model="filters.per_page" @change="applyFilters">
          <option :value="10">10 на странице</option>
          <option :value="20">20 на странице</option>
          <option :value="50">50 на странице</option>
        </select>

        <div class="buttons">
          <button type="submit">Найти</button>
          <button type="button" @click="resetFilters">Сбросить</button>
        </div>
      </form>

      <h3>Жанры</h3>
      <div class="chips">
        <button
          type="button"
          class="chip"
          :class="{ active: filters.genre_id === '' }"
          @click="setGenre('')"
        >
          Все
        </button>
        <button
          v-for="genre in genres"
          :key="genre.id"
          type="button"
          class="chip"
          :class="{ active: filters.genre_id === genre.id }"
          @click="setGenre(genre.id)"
        >
          {{ genre.name }}
        </button>
      </div>
    </aside>

    <section>
      <div v-if="showHero && games[0]" class="hero">
        <div class="hero-cover">
          <img
            v-if="heroCover"
            :src="heroCover"
            :alt="games[0].title"
            @error="heroCover = null"
          />
          <div
            v-else
            class="placeholder"
            :style="{ background: `hsl(${(games[0].id * 47) % 360} 40% 26%)` }"
          >
            {{ games[0].title.charAt(0).toUpperCase() }}
          </div>
        </div>
        <div class="hero-body">
          <span class="label">Лучшая по рейтингу</span>
          <h2>{{ games[0].title }}</h2>
          <p>{{ games[0].description }}</p>
          <div class="hero-actions">
            <RouterLink class="button-link" :to="`/games/${games[0].id}`">Подробнее</RouterLink>
            <span class="meta">
              Рейтинг {{ Math.round(Number(games[0].rating)) }}% ·
              {{ Number(games[0].price) === 0 ? 'Бесплатно' : formatKzt(games[0].price) }}
            </span>
          </div>
        </div>
      </div>

      <p v-if="error" class="error">{{ error }}</p>
      <p v-else-if="loading">Загрузка...</p>
      <p v-else-if="!games.length">Ничего не найдено</p>

      <div class="grid">
        <GameCard v-for="game in games" :key="game.id" :game="game" />
      </div>

      <div v-if="meta && meta.last_page > 1" class="pager">
        <button type="button" :disabled="!links?.prev" @click="goToPage(meta.current_page - 1)">
          Назад
        </button>
        <span>
          Страница {{ meta.current_page }} из {{ meta.last_page }} · всего игр: {{ meta.total }}
        </span>
        <button type="button" :disabled="!links?.next" @click="goToPage(meta.current_page + 1)">
          Вперёд
        </button>
      </div>
    </section>
  </div>
</template>