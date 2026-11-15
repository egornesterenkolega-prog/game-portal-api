<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()
const form = reactive({ email: '', password: '' })
const errors = ref({})
const message = ref('')
const loading = ref(false)

async function submit() {
  errors.value = {}
  message.value = ''
  loading.value = true
  try {
    await auth.login(form)
    router.push('/')
  } catch (e) {
    const status = e.response?.status
    if (status === 422) errors.value = e.response.data.errors
    else if (status === 401) message.value = 'Неверный email или пароль'
    else if (status === 403) message.value = 'Ваш аккаунт заблокирован'
    else message.value = 'Ошибка сервера, попробуйте позже'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <h1>Вход</h1>
  <form @submit.prevent="submit">
    <p v-if="message" class="error">{{ message }}</p>
    <div>
      <label>Email <input v-model="form.email" type="email" /></label>
      <p v-if="errors.email" class="error">{{ errors.email[0] }}</p>
    </div>
    <div>
      <label>Пароль <input v-model="form.password" type="password" /></label>
      <p v-if="errors.password" class="error">{{ errors.password[0] }}</p>
    </div>
    <button :disabled="loading">Войти</button>
  </form>
</template>