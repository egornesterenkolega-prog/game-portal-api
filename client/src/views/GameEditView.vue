<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api, { errorMessage } from '../api'
import { useAuthStore } from '../stores/auth'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const genres = ref([])
const game = ref(null)
const form = reactive({ title: '', description: '', price: 0, genre_ids: [] })
const errors = ref({})
const message = ref('')
const loading = ref(true)

const isAuthor = computed(() => game.value && auth.user?.id === game.value.author?.id)

function fieldErrors(name) {
  return errors.value[name] ?? []
}

onMounted(async () => {
  try {
    const [gameResponse, genresResponse] = await Promise.all([
      api.get(`/games/${route.params.id}`),
      api.get('/genres'),
    ])
    game.value = gameResponse.data.data ?? gameResponse.data
    genres.value = genresResponse.data.data ?? genresResponse.data
    form.title = game.value.title
    form.description = game.value.description
    form.price = game.value.price
    form.genre_ids = (game.value.genres ?? []).map((g) => g.id)
  } catch (e) {
    message.value = e.response?.status === 404 ? 'Игра не найдена' : errorMessage(e)
  } finally {
    loading.value = false
  }
})

async function submit() {
  errors.value = {}
  message.value = ''
  try {
    await api.put(`/games/${game.value.id}`, {
      title: form.title,
      description: form.description,
      price: form.price,
      genre_ids: form.genre_ids,
    })
    router.push(`/games/${game.value.id}`)
  } catch (e) {
    if (e.response?.status === 422) errors.value = e.response.data.errors ?? {}
    else message.value = errorMessage(e)
  }
}
</script>

<template>
  <h1>Изменить игру</h1>
  <p v-if="loading">Загрузка...</p>
  <p v-else-if="message && !game" class="error">{{ message }}</p>
  <p v-else-if="!isAuthor" class="error">Изменять игру может только её автор.</p>

  <form v-else @submit.prevent="submit">
    <p v-if="message" class="error">{{ message }}</p>

    <div>
      <label>Название <input v-model="form.title" type="text" /></label>
      <p v-for="text in fieldErrors('title')" :key="text" class="error">{{ text }}</p>
    </div>
    <div>
      <label>Описание <textarea v-model="form.description" rows="5"></textarea></label>
      <p v-for="text in fieldErrors('description')" :key="text" class="error">{{ text }}</p>
    </div>
    <div>
      <label>Цена <input v-model="form.price" type="number" min="0" step="0.01" /></label>
      <p v-for="text in fieldErrors('price')" :key="text" class="error">{{ text }}</p>
    </div>
    <div>
      <p>Жанры (от 1 до 3)</p>
      <label v-for="genre in genres" :key="genre.id" class="check">
        <input
          v-model="form.genre_ids"
          type="checkbox"
          :value="genre.id"
          :disabled="form.genre_ids.length >= 3 && !form.genre_ids.includes(genre.id)"
        />
        {{ genre.name }}
      </label>
      <p v-for="text in fieldErrors('genre_ids')" :key="text" class="error">{{ text }}</p>
    </div>

    <button>Сохранить</button>
  </form>
</template>