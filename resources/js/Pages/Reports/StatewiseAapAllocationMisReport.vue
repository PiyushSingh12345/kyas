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
                <a href="#">Statewise AAP Allocation</a>
              </li>
            </ul>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <div class="card-title d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span>
                      AAP Allocation for the FY {{ displayFinancialYear }} (₹ In {{ amountInText }})
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
                        <table class="table table-bordered aap-alloc-table mb-0">
                          <thead>
                            <tr>
                              <th rowspan="3" class="text-center col-sl">Sl.No.</th>
                              <th rowspan="3" class="col-state">State</th>
                              <th
                                v-for="group in columnGroups"
                                :key="'g1-' + group.key"
                                :colspan="group.columns.length"
                                class="text-center"
                              >
                                {{ group.label }}
                              </th>
                              <th rowspan="3" class="text-center col-final">
                                Final Allocation for FY{{ displayFinancialYear }}
                              </th>
                            </tr>
                            <tr>
                              <th
                                v-for="group in columnGroups"
                                :key="'g2-' + group.key"
                                :colspan="group.columns.length"
                                class="text-center fy-row"
                              >
                                FY {{ displayFinancialYear }}
                              </th>
                            </tr>
                            <tr>
                              <template v-for="group in columnGroups" :key="'g3-' + group.key">
                                <th
                                  v-for="col in group.columns"
                                  :key="col.key"
                                  class="text-center sls-name"
                                >
                                  {{ col.label }}
                                </th>
                              </template>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-if="rows.length === 0">
                              <td :colspan="totalColSpan" class="text-center text-muted py-4">
                                No data available for the selected financial year.
                              </td>
                            </tr>
                            <tr v-for="row in rows" :key="row.sl_no + '-' + row.state_name">
                              <td class="text-center">{{ row.sl_no }}</td>
                              <td class="col-state">{{ row.state_name }}</td>
                              <td
                                v-for="key in amountKeys"
                                :key="key"
                                class="text-center"
                              >
                                {{ formatCell(row[key]) }}
                              </td>
                              <td class="text-center fw-semibold">{{ formatCell(row.final_allocation) }}</td>
                            </tr>
                            <tr v-if="rows.length > 0" class="total-row">
                              <td colspan="2" class="text-end fw-bold">Total</td>
                              <td
                                v-for="key in amountKeys"
                                :key="'t-' + key"
                                class="text-center fw-bold"
                              >
                                {{ formatCell(totals[key]) }}
                              </td>
                              <td class="text-center fw-bold">{{ formatCell(totals.final_allocation) }}</td>
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

const columnGroups = ref([])
const amountKeys = computed(() => columnGroups.value.flatMap((g) => (g.columns || []).map((c) => c.key)))

const emptyTotals = () => {
  const t = { final_allocation: 0 }
  amountKeys.value.forEach((k) => {
    t[k] = 0
  })
  return t
}

const loading = ref(true)
const error = ref(null)
const selectedFinancialYear = ref('2026-27')
const rows = ref([])
const totals = ref(emptyTotals())

const reportTableScrollWrapper = ref(null)
const fixedScrollBar = ref(null)
const fixedScrollBarInner = ref(null)
const showFixedScrollBar = ref(false)
let scrollSyncLock = false

const { amountIn, amountInText, formatAmount } = useAmountIn('Lakh')
const amountFractionDigits = computed(() => 5)

const displayFinancialYear = computed(() => {
  const [start, end] = String(selectedFinancialYear.value).split('-')
  if (!start || !end) return selectedFinancialYear.value
  const endFull = end.length === 2 ? `${String(start).slice(0, 2)}${end}` : end
  return `${start}-${endFull}`
})

const totalColSpan = computed(() => 2 + amountKeys.value.length + 1)

const formatCell = (value) =>
  formatAmount(value ?? 0, { fractionDigits: amountFractionDigits.value })

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
      `/api/vw-statewise-aap-allocation-report?financial_year=${encodeURIComponent(selectedFinancialYear.value)}`
    )
    if (!response.ok) throw new Error('Failed to fetch report data')
    const result = await response.json()
    if (!result.success) throw new Error(result.message || 'Failed to load report')

    rows.value = result.rows || []
    columnGroups.value = result.column_groups || []
    totals.value = result.totals || emptyTotals()
  } catch (err) {
    console.error(err)
    error.value = 'Failed to load Statewise AAP Allocation report'
    rows.value = []
    columnGroups.value = []
    totals.value = emptyTotals()
  } finally {
    loading.value = false
    nextTick(updateFixedScrollBarWidth)
    setTimeout(updateFixedScrollBarWidth, 300)
  }
}

const buildExportRows = () => {
  const title = [
    `AAP Allocation for the FY ${displayFinancialYear.value} (Rs. In ${amountInText.value})`,
  ]
  const header1 = ['Sl.No.', 'State']
  const header2 = ['', '']
  const header3 = ['', '']

  columnGroups.value.forEach((group) => {
    (group.columns || []).forEach((col, idx) => {
      header1.push(idx === 0 ? group.label : '')
      header2.push(idx === 0 ? `FY ${displayFinancialYear.value}` : '')
      header3.push(col.label)
    })
  })
  header1.push(`Final Allocation for FY${displayFinancialYear.value}`)
  header2.push('')
  header3.push('')

  const exportRows = [title, [], header1, header2, header3]

  rows.value.forEach((row) => {
    exportRows.push([
      row.sl_no,
      row.state_name,
      ...amountKeys.value.map((key) => formatCell(row[key])),
      formatCell(row.final_allocation),
    ])
  })

  if (rows.value.length > 0) {
    exportRows.push([
      '',
      'Total',
      ...amountKeys.value.map((key) => formatCell(totals.value[key])),
      formatCell(totals.value.final_allocation),
    ])
  }

  return exportRows
}

const exportToExcel = () => {
  const data = buildExportRows()
  const worksheet = XLSX.utils.aoa_to_sheet(data)
  const workbook = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(workbook, worksheet, 'Statewise AAP Allocation')
  XLSX.writeFile(
    workbook,
    `Statewise_AAP_Allocation_${selectedFinancialYear.value}.xlsx`
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
  link.download = `Statewise_AAP_Allocation_${selectedFinancialYear.value}.csv`
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
.aap-alloc-table {
  font-size: 0.82rem;
  border-color: #999;
  margin-bottom: 0;
}

.aap-alloc-table th,
.aap-alloc-table td {
  border-color: #999 !important;
  vertical-align: middle;
  padding: 0.4rem 0.45rem;
  white-space: nowrap;
}

.aap-alloc-table thead th {
  background: #f4b183;
  font-weight: 700;
  color: #000;
}

.aap-alloc-table thead th.fy-row {
  background: #f8cbad;
  font-weight: 600;
}

.aap-alloc-table thead th.col-state,
.aap-alloc-table thead th.col-sl {
  background: #bdd7ee;
}

.aap-alloc-table thead th.col-final {
  background: #c6efce;
  min-width: 140px;
}

.aap-alloc-table tbody td.col-state {
  background: #ddebf7;
  text-align: left;
  font-weight: 500;
}

.aap-alloc-table tbody td:not(.col-state) {
  background: #fff;
}

.aap-alloc-table .col-sl {
  width: 60px;
  min-width: 60px;
}

.aap-alloc-table .col-state {
  min-width: 160px;
}

.aap-alloc-table thead th:not(.col-sl):not(.col-state) {
  min-width: 110px;
}

.aap-alloc-table thead th.sls-name {
  white-space: normal;
  min-width: 140px;
  max-width: 220px;
  font-size: 0.75rem;
  line-height: 1.25;
}

.aap-alloc-table .total-row td {
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
