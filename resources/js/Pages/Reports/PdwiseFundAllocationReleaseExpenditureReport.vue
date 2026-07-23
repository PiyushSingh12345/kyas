<template>
  <div class="wrapper">
    <Sidebar />
    <div class="main-panel">
      <Header />
      <div class="container">
        <div class="page-inner allinsideform">
          <div class="page-header">
            <h3 class="fw-bold mb-3">MIS Reports &amp; Dashboards</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home">
                <a href="#"><i class="icon-home"></i></a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">PD-wise Fund Allocation, Release and Expenditure Report for the state</a>
              </li>
            </ul>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <div class="card-title d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span>
                      PD-wise Fund Allocation, Release and Expenditure Report for the state
                      for FY {{ displayFinancialYear }} (₹ In {{ amountInText }})
                    </span>
                    <div class="d-flex gap-2">
                      <button
                        type="button"
                        class="btn btn-success btn-sm"
                        @click="exportToExcel"
                        :disabled="loading || !!error || rows.length === 0"
                      >
                        <i class="fas fa-file-excel me-1"></i>Excel
                      </button>
                      <button
                        type="button"
                        class="btn btn-secondary btn-sm"
                        @click="exportToCSV"
                        :disabled="loading || !!error || rows.length === 0"
                      >
                        <i class="fas fa-file-csv me-1"></i>CSV
                      </button>
                    </div>
                  </div>
                </div>

                <div class="card-body">
                  <div class="row mb-4">
                    <div class="col-12">
                      <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                          <h6 class="mb-0">
                            <i class="fas fa-filter me-2"></i>Filters
                          </h6>
                        </div>
                        <div class="card-body">
                          <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                              <label for="stateSelect" class="form-label fw-bold">State</label>
                              <select
                                id="stateSelect"
                                class="form-select"
                                v-model="selectedStateId"
                                @change="fetchReportData"
                              >
                                <option
                                  v-for="state in states"
                                  :key="state.state_id"
                                  :value="String(state.state_id)"
                                >
                                  {{ state.state_name }}
                                </option>
                              </select>
                            </div>

                            <div class="col-md-3">
                              <label for="financialYear" class="form-label fw-bold">Financial Year</label>
                              <select
                                id="financialYear"
                                class="form-select"
                                v-model="selectedFinancialYear"
                                @change="fetchReportData"
                              >
                                <option value="2026-27">2026-2027</option>
                                <option value="2025-26">2025-2026</option>
                                <option value="2024-25">2024–2025</option>
                                <option value="2023-24">2023–2024</option>
                                <option value="2022-23">2022–2023</option>
                              </select>
                            </div>

                            <AmountInFilter v-model="amountIn" col-class="col-md-3" input-id="amountInSelect" />

                            <div class="col-md-3">
                              <button type="button" class="btn btn-outline-secondary" @click="clearFilters">
                                <i class="fas fa-undo me-1"></i>Reset
                              </button>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div v-if="loading" class="text-center py-5">
                    <div class="spinner-border" role="status">
                      <span class="visually-hidden">Loading...</span>
                    </div>
                  </div>

                  <div v-else-if="error" class="alert alert-danger">
                    {{ error }}
                  </div>

                  <div
                    v-else
                    ref="reportTableScrollWrapper"
                    class="report-table-scroll-wrapper"
                    @scroll="onTableWrapperScroll"
                  >
                    <div class="table-responsive" id="reportTable">
                      <table class="table table-bordered pdwise-fund-table mb-0">
                        <thead>
                          <tr class="title-row">
                            <th colspan="5" class="text-center state-title">
                              {{ stateName || '—' }}
                            </th>
                            <th class="text-end unit-title">(Rs. in {{ amountInText }})</th>
                          </tr>
                          <tr>
                            <th class="text-center col-sl">S. No.</th>
                            <th class="col-component">Component-Wise</th>
                            <th class="text-center">AAP Allocation</th>
                            <th class="text-center">Central Share Released (SNA SPARSH)</th>
                            <th class="text-center">Exp. against Mother Sanction</th>
                            <th class="text-center">Expenditure (%)</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-if="rows.length === 0">
                            <td colspan="6" class="text-center text-muted py-4">
                              No data available for the selected state and financial year.
                            </td>
                          </tr>
                          <tr v-for="row in rows" :key="row.pd_id">
                            <td class="text-center">{{ row.sl_no }}</td>
                            <td class="col-component">{{ row.component_name }}</td>
                            <td class="text-center">{{ formatCell(row.aap_allocation) }}</td>
                            <td class="text-center">{{ formatCell(row.central_share_released) }}</td>
                            <td class="text-center">{{ formatCell(row.expenditure) }}</td>
                            <td class="text-center">{{ formatPct(row.expenditure_pct) }}</td>
                          </tr>
                          <tr v-if="rows.length > 0" class="total-row">
                            <td></td>
                            <td class="fw-bold">Total</td>
                            <td class="text-center fw-bold">{{ formatCell(totals.aap_allocation) }}</td>
                            <td class="text-center fw-bold">{{ formatCell(totals.central_share_released) }}</td>
                            <td class="text-center fw-bold">{{ formatCell(totals.expenditure) }}</td>
                            <td class="text-center fw-bold">{{ formatPct(totals.expenditure_pct) }}</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- Fixed horizontal scrollbar at bottom of viewport -->
        <div
          v-show="showFixedScrollBar"
          ref="fixedScrollBar"
          class="fixed-horizontal-scrollbar"
          @scroll="onFixedScrollBarScroll"
        >
          <div ref="fixedScrollBarInner" class="fixed-horizontal-scrollbar-inner"></div>
        </div>
        <Footer />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, onUpdated, nextTick } from 'vue'
import * as XLSX from 'xlsx'
import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'
import AmountInFilter from '../../Components/Reports/AmountInFilter.vue'
import { useAmountIn } from '../../Composables/useAmountIn'

const loading = ref(true)
const error = ref(null)
const states = ref([])
const selectedStateId = ref('')
const defaultStateId = ref('')
const stateName = ref('')
const selectedFinancialYear = ref('2026-27')
const rows = ref([])
const totals = ref({
  aap_allocation: 0,
  central_share_released: 0,
  expenditure: 0,
  expenditure_pct: null,
})

const reportTableScrollWrapper = ref(null)
const fixedScrollBar = ref(null)
const fixedScrollBarInner = ref(null)
const showFixedScrollBar = ref(false)
let scrollSyncLock = false

function updateFixedScrollBarWidth() {
  nextTick(() => {
    const wrapper = reportTableScrollWrapper.value
    const inner = fixedScrollBarInner.value
    const bar = fixedScrollBar.value
    if (!wrapper || !inner || !bar) return
    const tableEl = wrapper.querySelector('#reportTable table') || wrapper.querySelector('table')
    let contentWidth = tableEl && tableEl.scrollWidth > 0 ? tableEl.scrollWidth : wrapper.scrollWidth
    if (contentWidth <= 0) contentWidth = wrapper.scrollWidth
    const cw = wrapper.clientWidth
    inner.style.width = contentWidth + 'px'
    showFixedScrollBar.value = contentWidth > cw
    if (showFixedScrollBar.value) {
      const wrapperMax = wrapper.scrollWidth - wrapper.clientWidth
      const barMax = bar.scrollWidth - bar.clientWidth
      scrollSyncLock = true
      if (barMax > 0 && wrapperMax > 0) {
        bar.scrollLeft = (wrapper.scrollLeft / wrapperMax) * barMax
      } else {
        bar.scrollLeft = wrapper.scrollLeft
      }
      scrollSyncLock = false
    }
  })
}

function onTableWrapperScroll() {
  if (scrollSyncLock) return
  const wrapper = reportTableScrollWrapper.value
  const bar = fixedScrollBar.value
  if (!wrapper || !bar) return
  const wrapperMax = wrapper.scrollWidth - wrapper.clientWidth
  const barMax = bar.scrollWidth - bar.clientWidth
  if (wrapperMax <= 0 || barMax <= 0) return
  scrollSyncLock = true
  bar.scrollLeft = (wrapper.scrollLeft / wrapperMax) * barMax
  scrollSyncLock = false
}

function onFixedScrollBarScroll() {
  if (scrollSyncLock) return
  const wrapper = reportTableScrollWrapper.value
  const bar = fixedScrollBar.value
  if (!wrapper || !bar) return
  const wrapperMax = wrapper.scrollWidth - wrapper.clientWidth
  const barMax = bar.scrollWidth - bar.clientWidth
  if (wrapperMax <= 0 || barMax <= 0) return
  scrollSyncLock = true
  wrapper.scrollLeft = (bar.scrollLeft / barMax) * wrapperMax
  scrollSyncLock = false
}

const { amountIn, amountInText, formatAmount } = useAmountIn('Lakh')

const displayFinancialYear = computed(() => {
  const [start, end] = String(selectedFinancialYear.value).split('-')
  if (!start || !end) return selectedFinancialYear.value
  const endFull = end.length === 2 ? `${String(start).slice(0, 2)}${end}` : end
  return `${start}-${endFull}`
})

const formatCell = (value) => formatAmount(value ?? 0, { fractionDigits: 2 })

const formatPct = (value) => {
  if (value === null || value === undefined || Number.isNaN(Number(value))) {
    return '0.00'
  }
  return Number(value).toFixed(2)
}

const clearFilters = () => {
  selectedStateId.value = defaultStateId.value || selectedStateId.value
  selectedFinancialYear.value = '2026-27'
  amountIn.value = 'Lakh'
  fetchReportData()
}

const fetchStates = async () => {
  const response = await fetch('/api/aap-states')
  if (!response.ok) throw new Error('Failed to fetch states')
  const data = await response.json()
  const list = Array.isArray(data) ? data : data.data || []
  states.value = list

  const andhra = list.find(
    (s) => String(s.state_name || '').trim().toLowerCase() === 'andhra pradesh'
  )
  if (andhra) {
    defaultStateId.value = String(andhra.state_id)
    selectedStateId.value = String(andhra.state_id)
  } else if (list.length > 0) {
    defaultStateId.value = String(list[0].state_id)
    selectedStateId.value = String(list[0].state_id)
  }
}

const fetchReportData = async () => {
  if (!selectedStateId.value) {
    rows.value = []
    return
  }

  loading.value = true
  error.value = null
  try {
    const params = new URLSearchParams({
      financial_year: selectedFinancialYear.value,
      state_id: selectedStateId.value,
    })
    const response = await fetch(`/api/pdwise-fund-allocation-release-expenditure-report?${params}`)
    if (!response.ok) throw new Error('Failed to fetch report data')
    const result = await response.json()
    if (!result.success) throw new Error(result.message || 'Failed to load report')

    stateName.value = result.state_name || ''
    rows.value = result.rows || []
    totals.value = result.totals || {
      aap_allocation: 0,
      central_share_released: 0,
      expenditure: 0,
      expenditure_pct: null,
    }
  } catch (err) {
    console.error(err)
    error.value = 'Failed to load PD-wise Fund Allocation, Release and Expenditure report'
    rows.value = []
  } finally {
    loading.value = false
    nextTick(updateFixedScrollBarWidth)
    setTimeout(updateFixedScrollBarWidth, 300)
  }
}

const buildExportRows = () => {
  const headers = [
    'S. No.',
    'Component-Wise',
    'AAP Allocation',
    'Central Share Released (SNA SPARSH)',
    'Exp. against Mother Sanction',
    'Expenditure (%)',
  ]
  const exportRows = [
    [`${stateName.value} (Rs. in ${amountInText.value})`],
    [],
    headers,
  ]

  rows.value.forEach((row) => {
    exportRows.push([
      row.sl_no,
      row.component_name,
      formatCell(row.aap_allocation),
      formatCell(row.central_share_released),
      formatCell(row.expenditure),
      formatPct(row.expenditure_pct),
    ])
  })

  if (rows.value.length > 0) {
    exportRows.push([
      '',
      'Total',
      formatCell(totals.value.aap_allocation),
      formatCell(totals.value.central_share_released),
      formatCell(totals.value.expenditure),
      formatPct(totals.value.expenditure_pct),
    ])
  }

  return exportRows
}

const exportToExcel = () => {
  const data = buildExportRows()
  const worksheet = XLSX.utils.aoa_to_sheet(data)
  const workbook = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(workbook, worksheet, 'PD-wise Fund Report')
  XLSX.writeFile(
    workbook,
    `PD_wise_Fund_Allocation_Release_Expenditure_${stateName.value || 'State'}_${selectedFinancialYear.value}.xlsx`
  )
}

const exportToCSV = () => {
  const data = buildExportRows()
  const csv = data
    .map((row) =>
      row
        .map((cell) => {
          const value = String(cell ?? '')
          return `"${value.replace(/"/g, '""')}"`
        })
        .join(',')
    )
    .join('\n')
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = `PD_wise_Fund_Allocation_Release_Expenditure_${stateName.value || 'State'}_${selectedFinancialYear.value}.csv`
  link.click()
  URL.revokeObjectURL(link.href)
}

onMounted(async () => {
  window.addEventListener('resize', updateFixedScrollBarWidth)
  try {
    await fetchStates()
    await fetchReportData()
  } catch (err) {
    console.error(err)
    error.value = 'Failed to initialize report'
    loading.value = false
  }
})

onUpdated(() => {
  if (!loading.value && !error.value) updateFixedScrollBarWidth()
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', updateFixedScrollBarWidth)
})
</script>

<style scoped>
.pdwise-fund-table {
  font-size: 0.9rem;
  border-color: #000;
  margin-bottom: 0;
}

.pdwise-fund-table th,
.pdwise-fund-table td {
  border-color: #000 !important;
  vertical-align: middle;
  padding: 0.45rem 0.55rem;
  white-space: nowrap;
}

.pdwise-fund-table thead th {
  background: #bdd7ee;
  font-weight: 700;
  color: #000;
}

.pdwise-fund-table thead tr.title-row th {
  background: #bdd7ee;
}

.pdwise-fund-table .state-title {
  font-size: 1rem;
}

.pdwise-fund-table .unit-title {
  font-weight: 600;
  white-space: nowrap;
}

.pdwise-fund-table .col-sl {
  width: 70px;
  min-width: 70px;
}

.pdwise-fund-table .col-component {
  min-width: 180px;
  text-align: left;
}

.pdwise-fund-table thead th:not(.col-sl):not(.col-component) {
  min-width: 160px;
}

.pdwise-fund-table .total-row td {
  font-weight: 700;
  background: #fff;
}

.report-table-scroll-wrapper {
  width: 100%;
  max-width: 100%;
  overflow-x: auto;
  overflow-y: visible;
  border: 1px solid #dee2e6;
  border-radius: 8px;
}

.report-table-scroll-wrapper::-webkit-scrollbar {
  height: 10px;
}

.report-table-scroll-wrapper::-webkit-scrollbar-track {
  background: #f1f3f5;
  border-radius: 0 0 6px 6px;
}

.report-table-scroll-wrapper::-webkit-scrollbar-thumb {
  background: #868e96;
  border-radius: 5px;
}

.report-table-scroll-wrapper::-webkit-scrollbar-thumb:hover {
  background: #495057;
}

.report-table-scroll-wrapper {
  scrollbar-width: thin;
  scrollbar-color: #868e96 #f1f3f5;
}

.report-table-scroll-wrapper .table-responsive {
  margin-bottom: 0;
  min-width: max-content;
  width: max-content;
  overflow-x: visible;
  overflow-y: visible;
}

.report-table-scroll-wrapper table {
  min-width: max-content;
  width: max-content;
}

.fixed-horizontal-scrollbar {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  height: 14px;
  overflow-x: auto;
  overflow-y: hidden;
  background: #f1f3f5;
  z-index: 1030;
}

.fixed-horizontal-scrollbar-inner {
  height: 1px;
}

.fixed-horizontal-scrollbar::-webkit-scrollbar {
  height: 12px;
}

.fixed-horizontal-scrollbar::-webkit-scrollbar-track {
  background: #f1f3f5;
}

.fixed-horizontal-scrollbar::-webkit-scrollbar-thumb {
  background: #868e96;
  border-radius: 6px;
}

.fixed-horizontal-scrollbar::-webkit-scrollbar-thumb:hover {
  background: #495057;
}

.fixed-horizontal-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: #868e96 #f1f3f5;
}
</style>
