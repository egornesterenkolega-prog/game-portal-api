export const RUB_TO_KZT_RATE = 5.5

export function rubToKzt(value) {
  return Number(value) * RUB_TO_KZT_RATE
}

export function kztToRub(value) {
  return Math.round((Number(value) / RUB_TO_KZT_RATE) * 100) / 100
}

export function formatKzt(value) {
  const amount = Number(value)
  if (!Number.isFinite(amount)) return '—'

  return `${Math.round(rubToKzt(amount)).toLocaleString('ru-RU')} ₸`
}