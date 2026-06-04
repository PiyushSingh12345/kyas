import { ref, computed } from 'vue'
import { formatAmountFromLakh, formatAmountFromRupees, unitLabel } from '../utils/amountFormat'

/**
 * Shared Amount In filter state for MIS reports.
 */
export function useAmountIn(baseUnit = 'Lakh', defaultUnit = baseUnit) {
  const amountIn = ref(defaultUnit)
  const amountInText = computed(() => unitLabel(amountIn.value))

  const formatAmount = (value, options = {}) => {
    if (baseUnit === 'Rupees') {
      return formatAmountFromRupees(value, amountIn.value, { fractionDigits: 2, ...options })
    }
    // default base = Lakhs
    return formatAmountFromLakh(value, amountIn.value, { fractionDigits: 2, ...options })
  }

  return { amountIn, amountInText, formatAmount }
}
