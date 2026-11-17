<script setup>
import { ref, computed, onMounted } from 'vue'
import api, { errorMessage } from '../api'
import { formatKzt } from '../currency'

const stats = ref(null)
const loading = ref(true)
const error = ref('')

const genreMax = computed(() =>
  Math.max(1, ...(stats.value?.genres ?? []).map((g) => g.games_count ?? 0)),
)
const monthMax = computed(() =>
  Math.max(1, ...(stats.value?.games_by_month ?? []).map((m) => m.total ?? 0)),
)

function num(value, digits = 2) {
  if (value === null || value === undefined) return '—'
  return Number(value).toLocaleString('ru-RU', { maximumFractionDigits: digits })
}

function price(value) {
  if (value === null || value === undefined) return '—'
  return formatKzt(value)
}

onMounted(async () => {
  try {
    const { data } = await api.get('/stats')
    stats.value = data.data ?? data
  } catch (e) {
    error.value = errorMessage(e, 'Не удалось загрузить статистику')
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <h1>Статистика портала</h1>
  <p v-if="loading">Загрузка...</p>
  <p v-else-if="error" class="error">{{ error }}</p>

  <template v-else-if="stats">
    <div class="stat-grid">
      <div class="stat"><span>Пользователи</span><b>{{ num(stats.totals?.users, 0) }}</b></div>
      <div class="stat"><span>Игры</span><b>{{ num(stats.totals?.games, 0) }}</b></div>
      <div class="stat"><span>Комментарии</span><b>{{ num(stats.totals?.comments, 0) }}</b></div>
      <div class="stat"><span>Оценки</span><b>{{ num(stats.totals?.reactions, 0) }}</b></div>
    </div>

    <h2>Игры</h2>
    <div class="stat-grid">
      <div class="stat"><span>Бесплатные</span><b>{{ num(stats.games?.free, 0) }}</b></div>
      <div class="stat"><span>Платные</span><b>{{ num(stats.games?.paid, 0) }}</b></div>
      <div class="stat"><span>Средняя цена</span><b>{{ price(stats.games?.avg_price) }}</b></div>
      <div class="stat"><span>Максимальная цена</span><b>{{ price(stats.games?.max_price) }}</b></div>
      <div class="stat"><span>Средний рейтинг</span><b>{{ num(stats.games?.avg_rating) }}%</b></div>
    </div>

    <h2>Игры по жанрам</h2>
    <div v-for="genre in stats.genres" :key="genre.id" class="bar-row">
      <span class="bar-label">{{ genre.name }}</span>
      <div class="bar-track">
        <div class="bar" :style="{ width: ((genre.games_count ?? 0) / genreMax) * 100 + '%' }"></div>
      </div>
      <span class="bar-value">{{ genre.games_count }}</span>
    </div>

    <h2>Публикации по месяцам</h2>
    <p v-if="!stats.games_by_month?.length" class="meta">Пока нет данных</p>
    <div v-for="item in stats.games_by_month" :key="item.month" class="bar-row">
      <span class="bar-label">{{ item.month }}</span>
      <div class="bar-track">
        <div class="bar" :style="{ width: ((item.total ?? 0) / monthMax) * 100 + '%' }"></div>
      </div>
      <span class="bar-value">{{ item.total }}</span>
    </div>

    <div class="stat-columns">
      <section>
        <h2>Самые оценённые игры</h2>
        <p v-if="!stats.top_games?.length" class="meta">Пока нет данных</p>
        <div v-for="game in stats.top_games" :key="game.id" class="top-row">
          <RouterLink :to="`/games/${game.id}`">{{ game.title }}</RouterLink>
          <span class="meta">
            оценок: {{ game.reactions_count ?? '—' }}, комментариев: {{ game.comments_count ?? '—' }}
          </span>
        </div>
      </section>
      <section>
        <h2>Самые активные авторы</h2>
        <p v-if="!stats.top_authors?.length" class="meta">Пока нет данных</p>
        <div v-for="author in stats.top_authors" :key="author.id" class="top-row">
          <RouterLink :to="`/users/${author.id}`">{{ author.name }}</RouterLink>
          <span class="meta">игр: {{ author.games_count ?? '—' }}</span>
        </div>
      </section>
    </div>
  </template>
</template>