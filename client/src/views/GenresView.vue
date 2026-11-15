<script setup>
import { ref, onMounted } from 'vue'
import api, { errorMessage } from '../api'

const genres = ref([])
const newName = ref('')
const editingId = ref(null)
const editName = ref('')
const errors = ref([])
const message = ref('')

async function load() {
  const { data } = await api.get('/genres')
  genres.value = data.data ?? data
}

function nameErrors(e) {
  return e.response?.data?.errors?.name ?? [errorMessage(e)]
}

async function create() {
  errors.value = []
  message.value = ''
  try {
    await api.post('/genres', { name: newName.value })
    newName.value = ''
    await load()
  } catch (e) {
    if (e.response?.status === 422) errors.value = nameErrors(e)
    else message.value = errorMessage(e)
  }
}

function startEdit(genre) {
  editingId.value = genre.id
  editName.value = genre.name
  errors.value = []
  message.value = ''
}

async function save(genre) {
  errors.value = []
  try {
    await api.put(`/genres/${genre.id}`, { name: editName.value })
    editingId.value = null
    await load()
  } catch (e) {
    if (e.response?.status === 422) errors.value = nameErrors(e)
    else message.value = errorMessage(e)
  }
}

async function remove(genre) {
  if (!confirm(`Удалить жанр «${genre.name}»?`)) return
  message.value = ''
  try {
    await api.delete(`/genres/${genre.id}`)
    await load()
  } catch (e) {
    message.value = errorMessage(e) // при 409 сервер вернёт, что жанр используется
  }
}

onMounted(load)
</script>

<template>
  <h1>Управление жанрами</h1>

  <form class="genre-row" @submit.prevent="create">
    <input v-model="newName" type="text" placeholder="Название нового жанра" />
    <button>Добавить</button>
  </form>
  <p v-for="text in errors" :key="text" class="error">{{ text }}</p>
  <p v-if="message" class="error">{{ message }}</p>

  <div v-for="genre in genres" :key="genre.id" class="genre-row">
    <template v-if="editingId === genre.id">
      <input v-model="editName" type="text" />
      <button type="button" @click="save(genre)">Сохранить</button>
      <button type="button" @click="editingId = null">Отмена</button>
    </template>
    <template v-else>
      <span>{{ genre.name }}</span>
      <button type="button" @click="startEdit(genre)">Изменить</button>
      <button type="button" @click="remove(genre)">Удалить</button>
    </template>
  </div>
</template>