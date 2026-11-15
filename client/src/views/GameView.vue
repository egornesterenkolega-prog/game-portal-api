<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import api, { errorMessage } from '../api'
import { useAuthStore } from '../stores/auth'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const game = ref(null)
const loading = ref(true)
const notFound = ref(false)
const error = ref('')

const myReaction = ref(null)
const reactionError = ref('')

const comments = ref([])
const commentsMeta = ref(null)
const commentText = ref('')
const commentErrors = ref({})
const editingId = ref(null)
const editText = ref('')
const commentError = ref('')

// изменять игру может только автор, удалять — автор и администратор
const isAuthor = computed(
  () => auth.isLoggedIn && auth.user?.id === game.value?.author?.id,
)
const canDeleteGame = computed(() => isAuthor.value || (auth.isLoggedIn && auth.isAdmin))

// изменять комментарий может только его автор, удалять — автор и администратор
function canEditComment(comment) {
  return auth.isLoggedIn && auth.user?.id === comment.user?.id
}

function canDeleteComment(comment) {
  return auth.isLoggedIn && (auth.isAdmin || auth.user?.id === comment.user?.id)
}

function formatDate(value) {
  return new Date(value).toLocaleDateString('ru-RU')
}

async function loadGame() {
  try {
    const { data } = await api.get(`/games/${route.params.id}`)
    game.value = data.data ?? data
    await loadComments(1)
  } catch (e) {
    if (e.response?.status === 404) notFound.value = true
    else error.value = errorMessage(e, 'Не удалось загрузить игру')
  } finally {
    loading.value = false
  }
}

async function loadComments(page = 1) {
  const { data } = await api.get(`/games/${route.params.id}/comments`, { params: { page } })
  comments.value = page === 1 ? data.data : [...comments.value, ...data.data]
  commentsMeta.value = data.meta
}

async function react(type) {
  reactionError.value = ''
  try {
    if (myReaction.value === type) {
      await api.delete(`/games/${game.value.id}/reaction`)
      myReaction.value = null
      const { data } = await api.get(`/games/${game.value.id}`)
      game.value.rating = (data.data ?? data).rating
    } else {
      const { data } = await api.put(`/games/${game.value.id}/reaction`, { type })
      myReaction.value = type
      game.value.rating = data.game.rating
    }
  } catch (e) {
    if (e.response?.status === 404) myReaction.value = null
    else reactionError.value = errorMessage(e)
  }
}

async function addComment() {
  commentErrors.value = {}
  commentError.value = ''
  try {
    await api.post(`/games/${game.value.id}/comments`, { body: commentText.value })
    commentText.value = ''
    await loadComments(1)
  } catch (e) {
    if (e.response?.status === 422) commentErrors.value = e.response.data.errors ?? {}
    else commentError.value = errorMessage(e)
  }
}

function startEdit(comment) {
  editingId.value = comment.id
  editText.value = comment.body
  commentError.value = ''
}

async function saveEdit(comment) {
  try {
    const { data } = await api.put(`/comments/${comment.id}`, { body: editText.value })
    comment.body = (data.data ?? data).body
    editingId.value = null
  } catch (e) {
    commentError.value =
      e.response?.status === 422
        ? (e.response.data.errors?.body?.[0] ?? errorMessage(e))
        : errorMessage(e)
  }
}

async function removeComment(comment) {
  if (!confirm('Удалить комментарий?')) return
  try {
    await api.delete(`/comments/${comment.id}`)
    await loadComments(1)
  } catch (e) {
    commentError.value = errorMessage(e)
  }
}

async function removeGame() {
  if (!confirm('Удалить игру? Это действие нельзя отменить.')) return
  try {
    await api.delete(`/games/${game.value.id}`)
    router.push('/')
  } catch (e) {
    error.value = errorMessage(e, 'Не удалось удалить игру')
  }
}

onMounted(loadGame)
</script>

<template>
  <p v-if="loading">Загрузка...</p>
  <p v-else-if="notFound">Игра не найдена. <RouterLink to="/">Вернуться в каталог</RouterLink></p>
  <p v-else-if="error && !game" class="error">{{ error }}</p>

  <article v-else-if="game" class="game">
    <h1>{{ game.title }}</h1>
    <p class="meta">
      Автор:
      <RouterLink v-if="game.author" :to="`/users/${game.author.id}`">{{ game.author.name }}</RouterLink>
      · Добавлена {{ formatDate(game.created_at) }}
    </p>

    <div class="badges">
      <span class="badge">Рейтинг {{ game.rating }}%</span>
      <span class="badge price">{{ Number(game.price) === 0 ? 'Бесплатно' : game.price + ' ₽' }}</span>
      <span v-for="genre in game.genres" :key="genre.id" class="badge">{{ genre.name }}</span>
    </div>

    <div v-if="game.screenshots?.length" class="gallery">
      <img v-for="shot in game.screenshots" :key="shot.id" :src="shot.url" :alt="game.title" />
    </div>

    <p class="description">{{ game.description }}</p>

    <div class="reactions">
      <button
        type="button"
        :class="{ active: myReaction === 'like' }"
        :disabled="!auth.isLoggedIn"
        @click="react('like')"
      >
        👍 Нравится
      </button>
      <button
        type="button"
        :class="{ active: myReaction === 'dislike' }"
        :disabled="!auth.isLoggedIn"
        @click="react('dislike')"
      >
        👎 Не нравится
      </button>
      <span v-if="!auth.isLoggedIn" class="meta">
        <RouterLink to="/login">Войдите</RouterLink>, чтобы оценить игру
      </span>
    </div>
    <p v-if="reactionError" class="error">{{ reactionError }}</p>

    <div v-if="isAuthor || canDeleteGame" class="actions">
      <RouterLink v-if="isAuthor" :to="`/games/${game.id}/edit`" class="button-link">
        Изменить игру
      </RouterLink>
      <button v-if="canDeleteGame" type="button" @click="removeGame">Удалить игру</button>
    </div>
    <p v-if="error" class="error">{{ error }}</p>

    <section class="comments">
      <h2>Комментарии ({{ commentsMeta?.total ?? comments.length }})</h2>

      <form v-if="auth.isLoggedIn" class="comment-form" @submit.prevent="addComment">
        <textarea v-model="commentText" rows="3" placeholder="Напишите комментарий"></textarea>
        <p v-for="text in commentErrors.body ?? []" :key="text" class="error">{{ text }}</p>
        <button>Отправить</button>
      </form>
      <p v-else class="meta"><RouterLink to="/login">Войдите</RouterLink>, чтобы оставить комментарий</p>

      <p v-if="commentError" class="error">{{ commentError }}</p>
      <p v-if="!comments.length">Пока нет комментариев</p>

      <div v-for="comment in comments" :key="comment.id" class="comment">
        <strong>{{ comment.user?.name }}</strong>
        <span class="meta"> · {{ formatDate(comment.created_at) }}</span>

        <template v-if="editingId === comment.id">
          <textarea v-model="editText" rows="3"></textarea>
          <div class="comment-actions">
            <button type="button" @click="saveEdit(comment)">Сохранить</button>
            <button type="button" @click="editingId = null">Отмена</button>
          </div>
        </template>
        <template v-else>
          <p>{{ comment.body }}</p>
          <div v-if="canEditComment(comment) || canDeleteComment(comment)" class="comment-actions">
            <button v-if="canEditComment(comment)" type="button" @click="startEdit(comment)">
              Изменить
            </button>
            <button v-if="canDeleteComment(comment)" type="button" @click="removeComment(comment)">
              Удалить
            </button>
          </div>
        </template>
      </div>

      <button
        v-if="commentsMeta && commentsMeta.current_page < commentsMeta.last_page"
        type="button"
        @click="loadComments(commentsMeta.current_page + 1)"
      >
        Показать ещё
      </button>
    </section>
  </article>
</template>