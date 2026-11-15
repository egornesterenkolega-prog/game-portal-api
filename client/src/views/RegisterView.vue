<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()
const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})
const errors = ref({})
const message = ref('')
const loading = ref(false)

async function submit() {
  errors.value = {}
  message.value = ''
  loading.value = true
  try {
    await auth.register(form)
    router.push('/')
  } catch (e) {
    if (e.response?.status === 422) errors.value = e.response.data.errors
    else message.value = 'Ошибка сервера, попробуйте позже'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <h1>Регистрация</h1>
  <form @submit.prevent="submit">
    <p v-if="message" class="error">{{ message }}</p>
    <div>
      <label>Имя <input v-model="form.name" type="text" /></label>
      <p v-if="errors.name" class="error">{{ errors.name[0] }}</p>
    </div>
    <div>
      <label>Email <input v-model="form.email" type="email" /></label>
      <p v-if="errors.email" class="error">{{ errors.email[0] }}</p>
    </div>
    <div>
      <label>Пароль <input v-model="form.password" type="password" /></label>
      <p v-if="errors.password" class="error">{{ errors.password[0] }}</p>
    </div>
    <div>
      <label>
        Повторите пароль
        <input v-model="form.password_confirmation" type="password" />
      </label>
    </div>
    <button :disabled="loading">Зарегистрироваться</button>
  </form>
</template>