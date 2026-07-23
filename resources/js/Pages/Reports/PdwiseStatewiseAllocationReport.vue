<template>
  <div class="wrapper">
    <Sidebar />
    <div class="main-panel">
      <Header />
      <div class="container">
        <div class="page-inner allinsideform">
          <div class="page-header">
            <h3 class="fw-bold mb-3">Reports</h3>
            <ul class="breadcrumbs mb-3">
              <li class="nav-home">
                <a href="#"><i class="icon-home"></i></a>
              </li>
              <li class="separator">
                <i class="icon-arrow-right"></i>
              </li>
              <li class="nav-item">
                <a href="#">PD-wise, State/UT-wise Allocation Report</a>
              </li>
            </ul>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="card">
                <div class="card-header">
                  <div class="card-title">
                    <span>
                      PD-wise, State/UT-wise Allocation, Release &amp; Expenditure Summary
                      for FY {{ selectedFinancialYear }} (₹ In {{ amountInText }})
                    </span>
                  </div>
                </div>

                <div class="card-body">
                  <div v-if="loading" class="text-center">
                    <div class="spinner-border" role="status">
                      <span class="visually-hidden">Loading...</span>
                    </div>
                  </div>

                  <div v-else-if="error" class="alert alert-danger">
                    {{ error }}
                  </div>

                  <div v-else>
                    <!-- Filters -->
                    <div class="row mb-4">
                      <div class="col-12">
                        <div class="card border-primary">
                          <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">
                              <i class="fas fa-filter me-2"></i>Filters
                            </h6>
                          </div>
                          <div class="card-body">
                            <div class="row g-3">
                              <div class="col-md-3">
                                <label for="financialYear" class="form-label fw-bold">Financial Year</label>
                                <select
                                  id="financialYear"
                                  class="form-select"
                                  v-model="selectedFinancialYear"
                                  @change="onFinancialYearChange"
                                >
                                  <option value="2026-27">2026-2027</option>
                                  <option value="2025-26">2025-2026</option>
                                  <option value="2024-25">2024–2025</option>
                                  <option value="2023-24">2023–2024</option>
                                  <option value="2022-23">2022–2023</option>
                                </select>
                              </div>

                              <AmountInFilter v-model="amountIn" col-class="col-md-3" input-id="amountInSelect" />

                              <div class="col-md-4">
                                <label class="form-label fw-bold">State</label>
                                <div class="custom-multiselect-container" @click.stop>
                                  <div
                                    class="custom-multiselect-input form-control"
                                    :class="{ 'is-open': showStateDropdown }"
                                    @click="toggleStateDropdown"
                                  >
                                    <div class="selected-tags-wrapper">
                                      <span
                                        v-for="stateId in selectedStates"
                                        :key="stateId"
                                        class="custom-tag"
                                      >
                                        {{ getStateName(stateId) }}
                                        <span class="tag-remove" @click.stop="removeState(stateId)">×</span>
                                      </span>
                                      <input
                                        type="text"
                                        class="tag-input"
                                        v-model="stateSearchTerm"
                                        :placeholder="selectedStates.length === 0 ? 'All states...' : ''"
                                        @focus="showStateDropdown = true"
                                        @click.stop="showStateDropdown = true"
                                      />
                                    </div>
                                    <div class="dropdown-arrows">
                                      <i class="fas fa-chevron-up" v-if="showStateDropdown"></i>
                                      <i class="fas fa-chevron-down" v-else></i>
                                    </div>
                                  </div>
                                  <div class="custom-dropdown-menu" v-show="showStateDropdown" @click.stop>
                                    <div
                                      v-for="state in filteredAvailableStates"
                                      :key="state.state_id"
                                      class="dropdown-item"
                                      @click="selectState(state.state_id)"
                                    >
                                      {{ state.state_name }}
                                    </div>
                                    <div v-if="filteredAvailableStates.length === 0" class="dropdown-item text-muted">
                                      No states available
                                    </div>
                                  </div>
                                </div>
                              </div>

                              <div class="col-md-4">
                                <label class="form-label fw-bold">Program Division</label>
                                <div class="custom-multiselect-container" @click.stop>
                                  <div
                                    class="custom-multiselect-input form-control"
                                    :class="{ 'is-open': showPdDropdown }"
                                    @click="togglePdDropdown"
                                  >
                                    <div class="selected-tags-wrapper">
                                      <span
                                        v-for="pdId in selectedProgramDivisions"
                                        :key="pdId"
                                        class="custom-tag"
                                      >
                                        {{ getProgramDivisionName(pdId) }}
                                        <span class="tag-remove" @click.stop="removeProgramDivision(pdId)">×</span>
                                      </span>
                                      <input
                                        type="text"
                                        class="tag-input"
                                        v-model="pdSearchTerm"
                                        :placeholder="selectedProgramDivisions.length === 0 ? 'All program divisions...' : ''"
                                        @focus="showPdDropdown = true"
                                        @click.stop="showPdDropdown = true"
                                      />
                                    </div>
                                    <div class="dropdown-arrows">
                                      <i class="fas fa-chevron-up" v-if="showPdDropdown"></i>
                                      <i class="fas fa-chevron-down" v-else></i>
                                    </div>
                                  </div>
                                  <div class="custom-dropdown-menu" v-show="showPdDropdown" @click.stop>
                                    <div
                                      v-for="pd in filteredAvailableProgramDivisions"
                                      :key="pd.division_id"
                                      class="dropdown-item"
                                      @click="selectProgramDivision(pd.division_id)"
                                    >
                                      {{ pd.division_name }}
                                    </div>
                                    <div v-if="filteredAvailableProgramDivisions.length === 0" class="dropdown-item text-muted">
                                      No program divisions available
                                    </div>
                                  </div>
                                </div>
                              </div>

                              <div class="col-md-2 d-flex align-items-end">
                                <button class="btn btn-outline-secondary" @click="clearFilters" title="Clear All Filters">
                                  <i class="fas fa-times"></i> Clear
                                </button>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Export -->
                    <div class="row mb-3">
                      <div class="col-12 d-flex justify-content-end">
                        <button class="btn btn-success btn-sm me-2" @click="exportToExcel" title="Export to Excel">
                          <i class="fas fa-file-excel me-1"></i>EXCEL
                        </button>
                        <button class="btn btn-info btn-sm" @click="exportToCSV" title="Export to CSV">
                          <i class="fas fa-file-csv me-1"></i>CSV
                        </button>
                      </div>
                    </div>

                    <div ref="reportTableScrollWrapper" class="report-table-scroll-wrapper" @scroll="onTableWrapperScroll">
                      <div class="table-responsive" id="reportTable">
                        <table class="table table-bordered align-middle text-center report-matrix">
                          <thead>
                            <tr class="header-row">
                              <th class="fw-sticky state-col">State</th>
                              <th class="fw-sticky-2 particulars-col">Particulars</th>
                              <th
                                v-for="pd in filteredProgramDivisions"
                                :key="pd.division_id"
                                class="pd-col"
                              >
                                {{ shortPdName(pd.division_name) }}
                              </th>
                              <th class="total-col">Total</th>
                            </tr>
                          </thead>
                          <tbody>
                            <template v-for="state in filteredStates" :key="state.state_id">
                              <tr
                                v-for="(particular, pIndex) in particulars"
                                :key="`${state.state_id}-${particular.key}`"
                                :class="rowClass(particular.key)"
                              >
                                <td
                                  v-if="pIndex === 0"
                                  class="fw-bold fw-sticky state-col text-start"
                                  :rowspan="particulars.length"
                                >
                                  {{ state.state_name }}
                                </td>
                                <td class="fw-sticky-2 particulars-col text-start fw-semibold">
                                  {{ particular.label }}
                                </td>
                                <td
                                  v-for="pd in filteredProgramDivisions"
                                  :key="`${state.state_id}-${particular.key}-${pd.division_id}`"
                                >
                                  {{ formatParticularValue(state.state_id, pd.division_id, particular.key) }}
                                </td>
                                <td class="fw-bold total-col">
                                  {{ formatStateParticularTotal(state.state_id, particular.key) }}
                                </td>
                              </tr>
                            </template>

                            <!-- Grand Total -->
                            <template v-for="(particular, pIndex) in particulars" :key="`total-${particular.key}`">
                              <tr class="grand-total-row" :class="rowClass(particular.key)">
                                <td
                                  v-if="pIndex === 0"
                                  class="fw-bold fw-sticky state-col text-start"
                                  :rowspan="particulars.length"
                                >
                                  TOTAL
                                </td>
                                <td class="fw-sticky-2 particulars-col text-start fw-semibold">
                                  {{ particular.label }}
                                </td>
                                <td
                                  v-for="pd in filteredProgramDivisions"
                                  :key="`total-${particular.key}-${pd.division_id}`"
                                >
                                  {{ formatColumnParticularTotal(pd.division_id, particular.key) }}
                                </td>
                                <td class="fw-bold total-col">
                                  {{ formatGrandParticularTotal(particular.key) }}
                                </td>
                              </tr>
                            </template>
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
import { ref, onMounted, onBeforeUnmount, onUpdated, computed, nextTick } from 'vue'
import * as XLSX from 'xlsx'
import Header from '../Common/Header.vue'
import Sidebar from '../Common/Sidebar.vue'
import Footer from '../Common/Footer.vue'
import AmountInFilter from '../../Components/Reports/AmountInFilter.vue'
import { useAmountIn } from '../../Composables/useAmountIn'

const states = ref([])
const programDivisions = ref([])
const reportData = ref({})
const loading = ref(true)
const error = ref(null)

const reportTableScrollWrapper = ref(null)
const fixedScrollBar = ref(null)
const fixedScrollBarInner = ref(null)
const showFixedScrollBar = ref(false)
let scrollSyncLock = false

const selectedFinancialYear = ref('2026-27')
const selectedStates = ref([])
const selectedProgramDivisions = ref([])
const stateSearchTerm = ref('')
const pdSearchTerm = ref('')
const showStateDropdown = ref(false)
const showPdDropdown = ref(false)

const { amountIn, amountInText, formatAmount } = useAmountIn('Lakh')
const amountFractionDigits = computed(() => (amountIn.value === 'Rupees' ? 2 : 5))

const particulars = [
  { key: 'be_allocated', label: 'BE Allocated', type: 'amount' },
  { key: 'aap_approved', label: 'AAP Approved', type: 'amount' },
  { key: 'ms1', label: 'MS 1 (1st Installment)', type: 'amount' },
  { key: 'ms2', label: 'MS 2 (2nd Installment)', type: 'amount' },
  { key: 'total_release', label: 'Total Release', type: 'amount' },
  { key: 'expenditure', label: 'Expenditure', type: 'amount' },
  { key: 'pct_exp_against_release', label: '% Expenditure against Release', type: 'percent' },
  { key: 'pct_exp_against_be', label: '% Expenditure against BE', type: 'percent' },
]

const pdShortNames = {
  'Sub-mission on Agriculture Extension': 'Agriculture Extension',
  'National Food Security and Nutrition Mission': 'NFSNM',
  'Sub Mission on Seed and Planting': 'SMSP',
  'Mission for Integrated Development of Horticulture': 'MIDH',
  'National Bamboo Mission': 'NBM',
  'Mission Organic Value Chain Development for North East Region': 'MOVCDNER',
  'Digital Agriculture Mission': 'Digital Agriculture',
  'National Mission on Edible Oils-Oil Seeds': 'NMEO-Oil Seed',
  'National Mission on Edible Oils-Oil Palm': 'NMEO-Oil Palm',
  'Mission Pulses': 'Mission Pulse',
  'Integrated Scheme for Agricultural Marketing': 'Marketing',
}

const shortPdName = (name) => pdShortNames[name] || name

const emptyCell = () => ({
  be_allocated: 0,
  aap_approved: 0,
  ms1: 0,
  ms2: 0,
  total_release: 0,
  expenditure: 0,
  pct_exp_against_release: null,
  pct_exp_against_be: null,
})

const getCell = (stateId, pdId) => {
  const stateKey = String(stateId)
  const pdKey = String(pdId)
  return reportData.value?.[stateKey]?.[pdKey] ?? emptyCell()
}

const getAmountValue = (stateId, pdId, key) => {
  const cell = getCell(stateId, pdId)
  if (key === 'be_allocated') return Number(cell.aap_approved || 0) * 0.8
  if (key === 'total_release') return Number(cell.ms1 || 0) + Number(cell.ms2 || 0)
  return Number(cell[key] ?? 0)
}

const getPercentValue = (stateId, pdId, key) => {
  const expenditure = getAmountValue(stateId, pdId, 'expenditure')
  if (key === 'pct_exp_against_release') {
    const release = getAmountValue(stateId, pdId, 'total_release')
    return release > 0 ? (expenditure / release) * 100 : null
  }
  if (key === 'pct_exp_against_be') {
    const be = getAmountValue(stateId, pdId, 'be_allocated')
    return be > 0 ? (expenditure / be) * 100 : null
  }
  return null
}

const formatPercent = (value) => {
  if (value === null || value === undefined || Number.isNaN(value)) return '-'
  return `${Number(value).toFixed(2)}%`
}

const formatParticularValue = (stateId, pdId, key) => {
  const particular = particulars.find((p) => p.key === key)
  if (particular?.type === 'percent') {
    return formatPercent(getPercentValue(stateId, pdId, key))
  }
  return formatAmount(getAmountValue(stateId, pdId, key), {
    fractionDigits: amountFractionDigits.value,
  })
}

const sumAmountAcrossPds = (stateId, key) => {
  return filteredProgramDivisions.value.reduce(
    (sum, pd) => sum + getAmountValue(stateId, pd.division_id, key),
    0
  )
}

const formatStateParticularTotal = (stateId, key) => {
  const particular = particulars.find((p) => p.key === key)
  if (particular?.type === 'percent') {
    const expenditure = sumAmountAcrossPds(stateId, 'expenditure')
    if (key === 'pct_exp_against_release') {
      const release = sumAmountAcrossPds(stateId, 'total_release')
      return formatPercent(release > 0 ? (expenditure / release) * 100 : null)
    }
    const be = sumAmountAcrossPds(stateId, 'be_allocated')
    return formatPercent(be > 0 ? (expenditure / be) * 100 : null)
  }
  return formatAmount(sumAmountAcrossPds(stateId, key), {
    fractionDigits: amountFractionDigits.value,
  })
}

const sumAmountAcrossStates = (pdId, key) => {
  return filteredStates.value.reduce(
    (sum, state) => sum + getAmountValue(state.state_id, pdId, key),
    0
  )
}

const formatColumnParticularTotal = (pdId, key) => {
  const particular = particulars.find((p) => p.key === key)
  if (particular?.type === 'percent') {
    const expenditure = sumAmountAcrossStates(pdId, 'expenditure')
    if (key === 'pct_exp_against_release') {
      const release = sumAmountAcrossStates(pdId, 'total_release')
      return formatPercent(release > 0 ? (expenditure / release) * 100 : null)
    }
    const be = sumAmountAcrossStates(pdId, 'be_allocated')
    return formatPercent(be > 0 ? (expenditure / be) * 100 : null)
  }
  return formatAmount(sumAmountAcrossStates(pdId, key), {
    fractionDigits: amountFractionDigits.value,
  })
}

const formatGrandParticularTotal = (key) => {
  const particular = particulars.find((p) => p.key === key)
  if (particular?.type === 'percent') {
    let expenditure = 0
    let release = 0
    let be = 0
    filteredStates.value.forEach((state) => {
      expenditure += sumAmountAcrossPds(state.state_id, 'expenditure')
      release += sumAmountAcrossPds(state.state_id, 'total_release')
      be += sumAmountAcrossPds(state.state_id, 'be_allocated')
    })
    if (key === 'pct_exp_against_release') {
      return formatPercent(release > 0 ? (expenditure / release) * 100 : null)
    }
    return formatPercent(be > 0 ? (expenditure / be) * 100 : null)
  }

  let total = 0
  filteredStates.value.forEach((state) => {
    total += sumAmountAcrossPds(state.state_id, key)
  })
  return formatAmount(total, { fractionDigits: amountFractionDigits.value })
}

const rowClass = (key) => {
  if (key === 'be_allocated') return 'row-be'
  if (key === 'aap_approved') return 'row-aap'
  if (key === 'ms1' || key === 'ms2') return 'row-ms'
  if (key === 'total_release') return 'row-release'
  if (key === 'expenditure') return 'row-exp'
  if (key.startsWith('pct_')) return 'row-pct'
  return ''
}

const availableStates = computed(() =>
  states.value.filter((state) => !selectedStates.value.includes(state.state_id))
)

const filteredAvailableStates = computed(() => {
  if (!stateSearchTerm.value) return availableStates.value
  const search = stateSearchTerm.value.toLowerCase()
  return availableStates.value.filter((state) =>
    state.state_name.toLowerCase().includes(search)
  )
})

const availableProgramDivisions = computed(() =>
  programDivisions.value.filter((pd) => !selectedProgramDivisions.value.includes(pd.division_id))
)

const filteredAvailableProgramDivisions = computed(() => {
  if (!pdSearchTerm.value) return availableProgramDivisions.value
  const search = pdSearchTerm.value.toLowerCase()
  return availableProgramDivisions.value.filter((pd) =>
    pd.division_name.toLowerCase().includes(search)
  )
})

const filteredStates = computed(() => {
  if (selectedStates.value.length === 0) return states.value
  return states.value.filter((state) => selectedStates.value.includes(state.state_id))
})

const filteredProgramDivisions = computed(() => {
  if (selectedProgramDivisions.value.length === 0) return programDivisions.value
  return programDivisions.value.filter((pd) =>
    selectedProgramDivisions.value.includes(pd.division_id)
  )
})

const toggleStateDropdown = () => {
  showStateDropdown.value = !showStateDropdown.value
  if (showStateDropdown.value) showPdDropdown.value = false
}

const togglePdDropdown = () => {
  showPdDropdown.value = !showPdDropdown.value
  if (showPdDropdown.value) showStateDropdown.value = false
}

const selectState = (stateId) => {
  if (!selectedStates.value.includes(stateId)) selectedStates.value.push(stateId)
  stateSearchTerm.value = ''
  showStateDropdown.value = false
}

const removeState = (stateId) => {
  const index = selectedStates.value.indexOf(stateId)
  if (index > -1) selectedStates.value.splice(index, 1)
}

const selectProgramDivision = (pdId) => {
  if (!selectedProgramDivisions.value.includes(pdId)) selectedProgramDivisions.value.push(pdId)
  pdSearchTerm.value = ''
  showPdDropdown.value = false
}

const removeProgramDivision = (pdId) => {
  const index = selectedProgramDivisions.value.indexOf(pdId)
  if (index > -1) selectedProgramDivisions.value.splice(index, 1)
}

const getStateName = (stateId) =>
  states.value.find((s) => s.state_id === stateId)?.state_name || ''

const getProgramDivisionName = (pdId) =>
  programDivisions.value.find((p) => p.division_id === pdId)?.division_name || ''

const clearFilters = () => {
  selectedFinancialYear.value = '2026-27'
  selectedStates.value = []
  selectedProgramDivisions.value = []
  stateSearchTerm.value = ''
  pdSearchTerm.value = ''
  showStateDropdown.value = false
  showPdDropdown.value = false
  fetchReportData()
}

const onFinancialYearChange = () => fetchReportData()

const handleClickOutside = () => {
  showStateDropdown.value = false
  showPdDropdown.value = false
}

const fetchStates = async () => {
  const response = await fetch('/api/aap-states')
  if (!response.ok) throw new Error('Failed to fetch states')
  states.value = await response.json()
}

const fetchProgramDivisions = async () => {
  const response = await fetch('/api/aap-program-divisions')
  if (!response.ok) throw new Error('Failed to fetch program divisions')
  programDivisions.value = await response.json()
}

const fetchReportData = async () => {
  try {
    const response = await fetch(
      `/api/pdwise-statewise-allocation-report?financial_year=${selectedFinancialYear.value}`
    )
    if (!response.ok) throw new Error('Failed to fetch report data')
    const result = await response.json()
    reportData.value = result.success && result.data ? result.data : {}
  } catch (err) {
    console.error(err)
    error.value = 'Failed to load report data'
  }
}

const buildExportRows = () => {
  const headers = [
    'State',
    'Particulars',
    ...filteredProgramDivisions.value.map((pd) => shortPdName(pd.division_name)),
    'Total',
  ]
  const rows = [headers]

  filteredStates.value.forEach((state) => {
    particulars.forEach((particular, index) => {
      const row = [
        index === 0 ? state.state_name : '',
        particular.label,
        ...filteredProgramDivisions.value.map((pd) =>
          formatParticularValue(state.state_id, pd.division_id, particular.key)
        ),
        formatStateParticularTotal(state.state_id, particular.key),
      ]
      rows.push(row)
    })
  })

  particulars.forEach((particular, index) => {
    rows.push([
      index === 0 ? 'TOTAL' : '',
      particular.label,
      ...filteredProgramDivisions.value.map((pd) =>
        formatColumnParticularTotal(pd.division_id, particular.key)
      ),
      formatGrandParticularTotal(particular.key),
    ])
  })

  return rows
}

const exportToExcel = () => {
  const rows = buildExportRows()
  const worksheet = XLSX.utils.aoa_to_sheet(rows)
  const workbook = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(workbook, worksheet, 'PD State Allocation')
  XLSX.writeFile(
    workbook,
    `PD_wise_State_UT_wise_Allocation_Report_${selectedFinancialYear.value}.xlsx`
  )
}

const exportToCSV = () => {
  const rows = buildExportRows()
  const csv = rows
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
  link.download = `PD_wise_State_UT_wise_Allocation_Report_${selectedFinancialYear.value}.csv`
  link.click()
  URL.revokeObjectURL(link.href)
}

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

onMounted(async () => {
  window.addEventListener('resize', updateFixedScrollBarWidth)
  document.addEventListener('click', handleClickOutside)
  try {
    await Promise.all([fetchStates(), fetchProgramDivisions()])
    await fetchReportData()
  } catch (err) {
    console.error(err)
    error.value = 'Failed to initialize report'
  } finally {
    loading.value = false
    nextTick(updateFixedScrollBarWidth)
    setTimeout(updateFixedScrollBarWidth, 300)
  }
})

onUpdated(() => {
  if (!loading.value && !error.value) updateFixedScrollBarWidth()
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
  window.removeEventListener('resize', updateFixedScrollBarWidth)
})
</script>

<style scoped>
.report-matrix {
  font-size: 0.85rem;
  margin-bottom: 0;
}

.report-matrix th,
.report-matrix td {
  white-space: nowrap;
  vertical-align: middle;
  padding: 0.45rem 0.55rem;
}

.header-row th {
  background: #1f4e79;
  color: #fff;
  font-weight: 600;
}

.state-col {
  min-width: 140px;
  width: 140px;
  max-width: 180px;
  white-space: normal !important;
}

.particulars-col {
  min-width: 210px;
  width: 210px;
  text-align: left !important;
  background: #f8fafc;
}

.pd-col {
  min-width: 110px;
}

.total-col {
  min-width: 110px;
  background: #e8f4fd;
}

.row-be td:not(.state-col):not(.particulars-col) {
  background: #fff7e6;
}

.row-aap td:not(.state-col):not(.particulars-col) {
  background: #eef8ee;
}

.row-ms td:not(.state-col):not(.particulars-col) {
  background: #f3f7ff;
}

.row-release td:not(.state-col):not(.particulars-col) {
  background: #e6f4ea;
}

.row-exp td:not(.state-col):not(.particulars-col) {
  background: #fdecea;
}

.row-pct td:not(.state-col):not(.particulars-col) {
  background: #f5f0ff;
}

.row-be .particulars-col {
  background: #fff7e6;
}

.row-aap .particulars-col {
  background: #eef8ee;
}

.row-ms .particulars-col {
  background: #f3f7ff;
}

.row-release .particulars-col {
  background: #e6f4ea;
}

.row-exp .particulars-col {
  background: #fdecea;
}

.row-pct .particulars-col {
  background: #f5f0ff;
}

.grand-total-row td {
  font-weight: 700;
  border-top: 2px solid #1f4e79;
}

.fw-sticky {
  position: sticky;
  left: 0;
  background-color: #fff;
  z-index: 3;
}

.fw-sticky-2 {
  position: sticky;
  left: 140px;
  background-color: #f8fafc;
  z-index: 3;
  box-shadow: 2px 0 4px rgba(0, 0, 0, 0.06);
}

.header-row .fw-sticky,
.header-row .fw-sticky-2 {
  background: #1f4e79;
  color: #fff;
  z-index: 4;
}

.grand-total-row .fw-sticky {
  background: #fff3cd;
}

.grand-total-row .fw-sticky-2 {
  background: #fff3cd;
}

/* Horizontal scroll wrapper + fixed bar at viewport bottom */
.report-table-scroll-wrapper {
  width: 100%;
  max-width: 100%;
  overflow-x: auto;
  overflow-y: visible;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  background: #fff;
  padding: 0;
  margin-top: 4px;
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
  bottom: 0;
  left: 0;
  right: 0;
  height: 14px;
  overflow-x: auto;
  overflow-y: hidden;
  background: #f1f3f5;
  z-index: 1030;
  border-top: 1px solid #dee2e6;
  box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.06);
}

.fixed-horizontal-scrollbar-inner {
  height: 1px;
  pointer-events: none;
}

.fixed-horizontal-scrollbar::-webkit-scrollbar {
  height: 12px;
}

.fixed-horizontal-scrollbar::-webkit-scrollbar-track {
  background: #e9ecef;
  border-radius: 0;
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
  scrollbar-color: #868e96 #e9ecef;
}

.custom-multiselect-container {
  position: relative;
}

.custom-multiselect-input {
  min-height: 38px;
  padding: 4px 8px;
  display: flex;
  align-items: center;
  cursor: text;
  position: relative;
}

.custom-multiselect-input.is-open {
  border-color: #80bdff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.selected-tags-wrapper {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 4px;
  flex: 1;
  min-width: 0;
}

.custom-tag {
  display: inline-flex;
  align-items: center;
  background-color: #b3d9ff;
  color: #0056b3;
  padding: 2px 6px;
  border-radius: 3px;
  font-size: 0.875rem;
  white-space: nowrap;
}

.tag-remove {
  margin-left: 6px;
  cursor: pointer;
  font-weight: bold;
}

.tag-input {
  border: none;
  outline: none;
  flex: 1;
  min-width: 80px;
  background: transparent;
}

.dropdown-arrows {
  margin-left: 8px;
  color: #6c757d;
}

.custom-dropdown-menu {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  z-index: 1000;
  max-height: 220px;
  overflow-y: auto;
  background: #fff;
  border: 1px solid #ced4da;
  border-radius: 0.25rem;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.custom-dropdown-menu .dropdown-item {
  padding: 0.4rem 0.75rem;
  cursor: pointer;
}

.custom-dropdown-menu .dropdown-item:hover {
  background: #e9ecef;
}
</style>
