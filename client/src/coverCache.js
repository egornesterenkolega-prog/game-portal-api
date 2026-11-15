import api from './api'

const cache = new Map()

// загружает первый скриншот игры; повторные вызовы берут результат из кеша
export function loadCover(id) {
  if (!cache.has(id)) {
    cache.set(
      id,
      api
        .get(`/games/${id}`)
        .then(({ data }) => (data.data ?? data).screenshots?.[0]?.url ?? null)
        .catch(() => null),
    )
  }
  return cache.get(id)
} 