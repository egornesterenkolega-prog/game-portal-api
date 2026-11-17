<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { loadCover } from '../coverCache'
import { formatKzt } from '../currency'

const props = defineProps({ game: { type: Object, required: true } })

const cover = ref(props.game.screenshots?.[0]?.url ?? null)
const initial = computed(() => (props.game.title ?? '?').charAt(0).toUpperCase())
const color = computed(() => `hsl(${(props.game.id * 47) % 360} 40% 26%)`)
const rating = computed(() => Math.round(Number(props.game.rating ?? 0)))
const isFree = computed(() => Number(props.game.price) === 0)
const genres = computed(() => (props.game.genres ?? []).map((g) => g.name).join(', '))

onMounted(async () => {
  if (!cover.value) cover.value = await loadCover(props.game.id)
})
</script>

<template>
  <RouterLink class="card" :to="`/games/${game.id}`">
    <div class="cover">
      <img v-if="cover" :src="cover" :alt="game.title" @error="cover = null" />
      <div v-else class="placeholder" :style="{ background: color }">{{ initial }}</div>
      <span class="rating-chip">{{ rating }}%</span>
    </div>
    <div class="info">
      <span class="genres">{{ genres }}</span>
      <h3>{{ game.title }}</h3>
      <span class="author">{{ game.author?.name }}</span>
      <span class="price-line" :class="{ free: isFree }">
        {{ isFree ? 'Бесплатно' : formatKzt(game.price) }}
      </span>
    </div>
  </RouterLink>
</template>