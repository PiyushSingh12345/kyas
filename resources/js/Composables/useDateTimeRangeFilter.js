import { ref } from 'vue'

/**
 * Date/time range filter state for MIS report API query params.
 * Params: date_from, time_from, date_to, time_to
 */
export function useDateTimeRangeFilter() {
  const dateFrom = ref('')
  const timeFrom = ref('')
  const dateTo = ref('')
  const timeTo = ref('')

  const buildQueryString = (baseParams = {}) => {
    const params = new URLSearchParams()
    Object.entries(baseParams).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== '') {
        params.set(key, String(value))
      }
    })
    if (dateFrom.value) params.set('date_from', dateFrom.value)
    if (timeFrom.value) params.set('time_from', timeFrom.value)
    if (dateTo.value) params.set('date_to', dateTo.value)
    if (timeTo.value) params.set('time_to', timeTo.value)
    const qs = params.toString()
    return qs
  }

  const appendToUrl = (url, baseParams = {}) => {
    const qs = buildQueryString(baseParams)
    if (!qs) return url
    return `${url}${url.includes('?') ? '&' : '?'}${qs}`
  }

  const clearDateTimeRange = () => {
    dateFrom.value = ''
    timeFrom.value = ''
    dateTo.value = ''
    timeTo.value = ''
  }

  const hasDateTimeFilter = () => !!(dateFrom.value || dateTo.value)

  const filterSummary = () => {
    if (!hasDateTimeFilter()) return ''
    const from = [dateFrom.value, timeFrom.value].filter(Boolean).join(' ')
    const to = [dateTo.value, timeTo.value].filter(Boolean).join(' ')
    if (from && to) return `${from} to ${to}`
    if (from) return `from ${from}`
    return `until ${to}`
  }

  return {
    dateFrom,
    timeFrom,
    dateTo,
    timeTo,
    buildQueryString,
    appendToUrl,
    clearDateTimeRange,
    hasDateTimeFilter,
    filterSummary,
  }
}
