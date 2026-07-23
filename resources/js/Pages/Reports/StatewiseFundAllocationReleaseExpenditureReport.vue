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
                <a href="#">State wise Fund Allocation, Release and Expenditure Report</a>
              </li>
            </ul>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <div class="card-title d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span>
                      KY State and SLS wise Fund Allocation, Release and Expenditure Data
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
                  <div v-if="loading" class="text-center py-5">
                    <div class="spinner-border" role="status">
                      <span class="visually-hidden">Loading...</span>
                    </div>
                  </div>

                  <div v-else-if="error" class="alert alert-danger">
                    {{ error }}
                  </div>

                  <div v-else>
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

                    <div
                      ref="reportTableScrollWrapper"
                      class="report-table-scroll-wrapper"
                      @scroll="onTableWrapperScroll"
                    >
                      <div class="table-responsive" id="reportTable">
                        <table class="table table-bordered fund-alloc-table mb-0">
                          <thead>
                            <tr>
                              <th class="text-center col-sl">#</th>
                              <th class="col-state">State</th>
                              <th class="text-center">BE Allocated</th>
                              <th class="text-center">AAP Allocation</th>
                              <th class="text-center">Mother Sanction Amount (Fund released)</th>
                              <th class="text-center">Total Daily Sanction Amount (Expenditure)</th>
                              <th class="text-center">Release %</th>
                              <th class="text-center">Expenditure % vs release</th>
                              <th class="text-center">Expenditure % vs BE</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-if="rows.length === 0">
                              <td colspan="9" class="text-center text-muted py-4">
                                No data available for the selected financial year.
                              </td>
                            </tr>
                            <tr v-for="row in rows" :key="row.state_id">
                              <td class="text-center">{{ row.sl_no }}</td>
                              <td class="col-state">{{ row.state_name }}</td>
                              <td class="text-center">{{ formatCell(row.be_allocated) }}</td>
                              <td class="text-center">{{ formatCell(row.aap_allocation) }}</td>
                              <td class="text-center">{{ formatCell(row.mother_sanction) }}</td>
                              <td class="text-center">{{ formatCell(row.expenditure) }}</td>
                              <td class="text-center">{{ formatReleasePct(row.release_pct) }}</td>
                              <td class="text-center">{{ formatReleasePct(row.exp_vs_release_pct) }}</td>
                              <td class="text-center">{{ formatBePct(row.exp_vs_be_pct) }}</td>
                            </tr>
                            <tr v-if="rows.length > 0" class="total-row">
                              <td colspan="2" class="text-end fw-bold">Total</td>
                              <td class="text-center fw-bold">{{ formatCell(totals.be_allocated) }}</td>
                              <td class="text-center fw-bold">{{ formatCell(totals.aap_allocation) }}</td>
                              <td class="text-center fw-bold">{{ formatCell(totals.mother_sanction) }}</td>
                              <td class="text-center fw-bold">{{ formatCell(totals.expenditure) }}</td>
                              <td class="text-center fw-bold">{{ formatReleasePct(totals.release_pct) }}</td>
                              <td class="text-center fw-bold">{{ formatReleasePct(totals.exp_vs_release_pct) }}</td>
                              <td class="text-center fw-bold">{{ formatBePct(totals.exp_vs_be_pct) }}</td>
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
const selectedFinancialYear = ref('2026-27')
const rows = ref([])
const totals = ref({
  be_allocated: 0,
  aap_allocation: 0,
  mother_sanction: 0,
  expenditure: 0,
  release_pct: null,
  exp_vs_release_pct: null,
  exp_vs_be_pct: null,
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
const amountFractionDigits = computed(() => 2)

const displayFinancialYear = computed(() => {
  const [start, end] = String(selectedFinancialYear.value).split('-')
  if (!start || !end) return selectedFinancialYear.value
  const endFull = end.length === 2 ? `${String(start).slice(0, 2)}${end}` : end
  return `${start}-${endFull}`
})

const formatCell = (value) =>
  formatAmount(value ?? 0, { fractionDigits: amountFractionDigits.value })

const formatReleasePct = (value) => {
  if (value === null || value === undefined || Number.isNaN(Number(value))) {
    return ''
  }
  return `${Math.round(Number(value))}%`
}

const formatBePct = (value) => {
  if (value === null || value === undefined || Number.isNaN(Number(value))) {
    return ''
  }
  return `${Number(value).toFixed(2)}%`
}

const clearFilters = () => {
  selectedFinancialYear.value = '2026-27'
  amountIn.value = 'Lakh'
  fetchReportData()
}

const fetchReportData = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await fetch(
      `/api/statewise-fund-allocation-release-expenditure-report?financial_year=${encodeURIComponent(selectedFinancialYear.value)}`
    )
    if (!response.ok) throw new Error('Failed to fetch report data')
    const result = await response.json()
    if (!result.success) throw new Error(result.message || 'Failed to load report')

    rows.value = result.rows || []
    totals.value = result.totals || {
      be_allocated: 0,
      aap_allocation: 0,
      mother_sanction: 0,
      expenditure: 0,
      release_pct: null,
      exp_vs_release_pct: null,
      exp_vs_be_pct: null,
    }
  } catch (err) {
    console.error(err)
    error.value = 'Failed to load State wise Fund Allocation, Release and Expenditure report'
    rows.value = []
  } finally {
    loading.value = false
    nextTick(updateFixedScrollBarWidth)
    setTimeout(updateFixedScrollBarWidth, 300)
  }
}

const buildExportRows = () => {
  const headers = [
    '#',
    'State',
    'BE Allocated',
    'AAP Allocation',
    'Mother Sanction Amount (Fund released)',
    'Total Daily Sanction Amount (Expenditure)',
    'Release %',
    'Expenditure % vs release',
    'Expenditure % vs BE',
  ]
  const exportRows = [
    [
      `KY State and SLS wise Fund Allocation, Release and Expenditure Data for FY ${displayFinancialYear.value} (Rs. in ${amountInText.value})`,
    ],
    [],
    headers,
  ]

  rows.value.forEach((row) => {
    exportRows.push([
      row.sl_no,
      row.state_name,
      formatCell(row.be_allocated),
      formatCell(row.aap_allocation),
      formatCell(row.mother_sanction),
      formatCell(row.expenditure),
      formatReleasePct(row.release_pct),
      formatReleasePct(row.exp_vs_release_pct),
      formatBePct(row.exp_vs_be_pct),
    ])
  })

  if (rows.value.length > 0) {
    exportRows.push([
      '',
      'Total',
      formatCell(totals.value.be_allocated),
      formatCell(totals.value.aap_allocation),
      formatCell(totals.value.mother_sanction),
      formatCell(totals.value.expenditure),
      formatReleasePct(totals.value.release_pct),
      formatReleasePct(totals.value.exp_vs_release_pct),
      formatBePct(totals.value.exp_vs_be_pct),
    ])
  }

  return exportRows
}

const exportToExcel = () => {
  const data = buildExportRows()
  const worksheet = XLSX.utils.aoa_to_sheet(data)
  const workbook = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(workbook, worksheet, 'Fund Alloc Release Exp')
  XLSX.writeFile(
    workbook,
    `Statewise_Fund_Allocation_Release_Expenditure_${selectedFinancialYear.value}.xlsx`
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
  link.download = `Statewise_Fund_Allocation_Release_Expenditure_${selectedFinancialYear.value}.csv`
  link.click()
  URL.revokeObjectURL(link.href)
}

onMounted(() => {
  window.addEventListener('resize', updateFixedScrollBarWidth)
  fetchReportData()
})

onUpdated(() => {
  if (!loading.value && !error.value) updateFixedScrollBarWidth()
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', updateFixedScrollBarWidth)
})
</script>

<style scoped>
.fund-alloc-table {
  font-size: 0.9rem;
  border-color: #999;
  margin-bottom: 0;
}

.fund-alloc-table th,
.fund-alloc-table td {
  border-color: #999 !important;
  vertical-align: middle;
  padding: 0.45rem 0.55rem;
  white-space: nowrap;
}

.fund-alloc-table thead th {
  background: #f4b183;
  font-weight: 700;
  color: #000;
}

.fund-alloc-table thead th.col-state {
  background: #bdd7ee;
}

.fund-alloc-table tbody td.col-state {
  background: #ddebf7;
  text-align: left;
  font-weight: 500;
}

.fund-alloc-table tbody td:not(.col-state) {
  background: #d6dce4;
}

.fund-alloc-table .col-sl {
  width: 60px;
  min-width: 60px;
}

.fund-alloc-table .col-state {
  min-width: 160px;
}

.fund-alloc-table thead th:not(.col-sl):not(.col-state) {
  min-width: 140px;
}

.fund-alloc-table .total-row td {
  background: #f4b183 !important;
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
