const UNIT_FACTORS_FROM_LAKH = {
  Rupees: 100000,
  Lakh: 1,
  Crore: 1 / 100,
}

export const AMOUNT_IN_OPTIONS = [
  { value: 'Rupees', label: 'Actual' },
  { value: 'Lakh', label: 'Lakh' },
  { value: 'Crore', label: 'Crore' },
]

export const UNIT_LABELS = {
  Rupees: 'Actual',
  Lakh: 'Lakh',
  Crore: 'Crore',
}

const UNIT_FACTORS_FROM_RUPEES = {
  Rupees: 1,
  Lakh: 1 / 100000,
  Crore: 1 / 10000000,
}

function toNumber(value) {
  if (value === null || value === undefined || value === '') return 0
  const n = typeof value === 'number' ? value : Number(String(value).replace(/,/g, ''))
  return Number.isFinite(n) ? n : 0
}

export function unitLabel(unit = 'Lakh') {
  return UNIT_LABELS[unit] ?? String(unit || '')
}

export function convertFromLakh(value, unit = 'Lakh') {
  const n = toNumber(value)
  const factor = UNIT_FACTORS_FROM_LAKH[unit] ?? 1
  return n * factor
}

export function convertFromRupees(value, unit = 'Rupees') {
  const n = toNumber(value)
  const factor = UNIT_FACTORS_FROM_RUPEES[unit] ?? 1
  return n * factor
}

export function formatIndianNumber(value, { fractionDigits = 2 } = {}) {
  const n = toNumber(value)
  return n.toLocaleString('en-IN', {
    minimumFractionDigits: fractionDigits,
    maximumFractionDigits: fractionDigits,
  })
}

/**
 * Formats an amount whose base unit is Lakhs.
 * Example: base 123.45 (Lakhs) -> unit "Crore" => 1.23, unit "Rupees" => 12,345,000.00
 */
export function formatAmountFromLakh(value, unit = 'Lakh', { fractionDigits = 2 } = {}) {
  const converted = convertFromLakh(value, unit)
  return formatIndianNumber(converted, { fractionDigits })
}

/**
 * Formats an amount whose base unit is Rupees.
 * Example: base 1234567 (Rupees) -> unit "Lakh" => 12.35, unit "Crore" => 0.12
 */
export function formatAmountFromRupees(value, unit = 'Rupees', { fractionDigits = 2 } = {}) {
  const converted = convertFromRupees(value, unit)
  return formatIndianNumber(converted, { fractionDigits })
}

