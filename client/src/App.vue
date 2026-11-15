<script setup>
import { ref } from 'vue'
import { RouterLink, RouterView, useRouter } from 'vue-router'
import { useAuthStore } from './stores/auth'

const auth = useAuthStore()
const router = useRouter()
const query = ref('')

async function logout() {
  try {
    await auth.logout()
  } finally {
    router.push('/login')
  }
}

function search() {
  const text = query.value.trim()
  router.push({ name: 'home', query: text ? { search: text } : {} })
}
</script>

<template>
  <header>
    <nav>
      <RouterLink class="brand" to="/">ИГРОВОЙ ПОРТАЛ</RouterLink>
      <RouterLink to="/">Каталог</RouterLink>
      <template v-if="auth.isLoggedIn">
        <RouterLink to="/games/new">Опубликовать</RouterLink>
        <RouterLink v-if="auth.isAdmin" to="/admin/genres">Жанры</RouterLink>
        <RouterLink v-if="auth.isAdmin" to="/admin/stats">Статистика</RouterLink>
      </template>

      <form class="nav-search" @submit.prevent="search">
        <input v-model="query" type="search" placeholder="Поиск игр" />
      </form>

      <template v-if="auth.isLoggedIn">
        <RouterLink :to="`/users/${auth.user?.id}`">{{ auth.user?.name }}</RouterLink>
        <button @click="logout">Выйти</button>
      </template>
      <template v-else>
        <RouterLink to="/login">Вход</RouterLink>
        <RouterLink class="cta" to="/register">Регистрация</RouterLink>
      </template>
    </nav>
  </header>
  <main>
    <RouterView />
  </main>
</template>