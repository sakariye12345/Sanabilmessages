export function normalizeSomaliPhone(input: string): string {
  let digits = (input || '').replace(/\D/g, '')

  if (!digits) return ''

  if (digits.startsWith('252') && digits.length >= 12) {
    return digits
  }

  if (digits.startsWith('0') && digits.length >= 9) {
    digits = digits.slice(1)
  }

  if (digits.length === 9) {
    return `252${digits}`
  }

  return digits
}

export function toE164SomaliPhone(input: string): string {
  const normalized = normalizeSomaliPhone(input)
  return normalized ? `+${normalized}` : ''
}
