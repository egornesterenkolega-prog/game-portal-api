<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api'

const router = useRouter()
const genres = ref([])
const form = reactive({ title: '', description: '', price: 0, genre_ids: [] })
const screenshots = ref([])
const gameFile = ref(null)
const errors = ref({})
const message = ref('')
const loading = ref(false)

const MAX_SHOT = 5 * 1024 * 1024
const MAX_FILE = 500 * 1024 * 1024

onMounted(async () => {
  try {
    const { data } = await api.get('/genres')
    genres.value = data.data ?? data
  } catch (e) {
    message.value = 'Не удалось загрузить жанры'
  }
})

function onScreenshots(event) {
  screenshots.value = Array.from(event.target.files)
}

function onGameFile(event) {
  gameFile.value = event.target.files[0] ?? null
}

// ошибки сервера приходят как genre_ids, screenshots.0 и т. д.: собираем по префиксу поля
function fieldErrors(name) {
  return Object.entries(errors.value)
    .filter(([key]) => key === name || key.startsWith(name + '.'))
    .flatMap(([, messages]) => messages)
}

function validateClient() {
  const e = {}
  if (!form.title.trim()) e.title = ['Введите название']
  if (!form.description.trim()) e.description = ['Введите описание']
  if (form.price === '' || Number(form.price) < 0) e.price = ['Цена не может быть отрицательной']
  if (form.genre_ids.length < 1 || form.genre_ids.length > 3) {
    e.genre_ids = ['Выберите от 1 до 3 жанров']
  }
  if (!screenshots.value.length) {
    e.screenshots = ['Добавьте хотя бы один скриншот']
  } else if (
    screenshots.value.some((f) => !['image/jpeg', 'image/png'].includes(f.type) || f.size > MAX_SHOT)
  ) {
    e.screenshots = ['Скриншоты: только JPG или PNG, до 5 МБ каждый']
  }
  if (!gameFile.value) {
    e.game_file = ['Выберите ZIP-файл игры']
  } else if (!gameFile.value.name.toLowerCase().endsWith('.zip') || gameFile.value.size > MAX_FILE) {
    e.game_file = ['Файл игры: только ZIP, до 500 МБ']
  }
  return e
}

async function submit() {
  message.value = ''
  errors.value = validateClient()
  if (Object.keys(errors.value).length) return

  const data = new FormData()
  data.append('title', form.title)
  data.append('description', form.description)
  data.append('price', form.price)
  form.genre_ids.forEach((id) => data.append('genre_ids[]', id))
  screenshots.value.forEach((file) => data.append('screenshots[]', file))
  data.append('game_file', gameFile.value)

  loading.value = true
  try {
    const response = await api.post('/games', data)
    const game = response.data.data ?? response.data
    router.push(`/games/${game.id}`)
  } catch (e) {
    const status = e.response?.status
    if (status === 422) errors.value = e.response.data.errors ?? {}
        else if (status === 409 || status === 403) message.value = e.response.data.message ?? 'Публикация недоступна'
          else if (status !== 401) message.value = 'Ошибка сервера, попробуйте позже'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <h1>Опубликовать игру</h1>
  <form @submit.prevent="submit">
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
      <label>Цена (0 — бесплатно) <input v-model="form.price" type="number" min="0" step="0.01" /></label>
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

    <div>
      <label>
        Скриншоты (JPG или PNG, до 5 МБ)
        <input type="file" accept="image/jpeg,image/png" multiple @change="onScreenshots" />
      </label>
      <p v-for="text in fieldErrors('screenshots')" :key="text" class="error">{{ text }}</p>
    </div>

    <div>
      <label>
        Файл игры (ZIP, до 500 МБ)
        <input type="file" accept=".zip" @change="onGameFile" />
      </label>
      <p v-for="text in fieldErrors('game_file')" :key="text" class="error">{{ text }}</p>
    </div>

    <button :disabled="loading">{{ loading ? 'Загрузка...' : 'Опубликовать' }}</button>
  </form>
</template>